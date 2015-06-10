<?php

/**
 * @file
 * Contains \Drupal\security_review\Check.
 */

namespace Drupal\security_review;

use Drupal;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\user\Entity\User;

/**
 * Defines a security check.
 */
abstract class Check {
  /**
   * @var \Drupal\security_review\CheckSettingsInterface $settings
   */
  protected $settings;

  /**
   * @var \Drupal\Core\Config\Config $config
   */
  protected $config;

  /**
   * @var array $instances
   */
  protected static $instances = array();

  /**
   * Constructs the check by initializing the configuration storage and the
   * settings interface.
   */
  protected function __construct() {
    $this->config = Drupal::configFactory()
      ->getEditable('security_review.check.' . $this->getUniqueIdentifier());
    $this->settings = new CheckSettings($this, $this->config);

    // Set namespace and id in config.
    if ($this->config->get('namespace') != $this->getMachineNamespace()
      || $this->config->get('title') != $this->getMachineTitle()
    ) {
      $this->config->set('namespace', $this->getMachineNamespace());
      $this->config->set('title', $this->getMachineTitle());
      $this->config->save();
    }
  }

  /**
   * Returns the instance of the extending check.
   *
   * @return Check
   *   The instance of the check.
   */
  public static final function getInstance() {
    $calledClass = get_called_class();

    if (!isset(static::$instances[$calledClass])) {
      static::$instances[$calledClass] = new $calledClass();
    }

    return static::$instances[$calledClass];
  }

  /**
   * Returns the namespace of the check. Usually it's the same as the module's
   * name.
   *
   * Naming rules (if overridden):
   *   - All characters should be lowerspace.
   *   - Use characters only from the english alphabet.
   *   - Don't use spaces (use "_" instead).
   *
   * @return string
   *   Machine namespace of the check.
   */
  public function getMachineNamespace() {
    $namespace = strtolower($this->getNamespace());
    $namespace = preg_replace("/[^a-z0-9 ]/", '', $namespace);
    $namespace = str_replace(' ', '_', $namespace);

    return $namespace;
  }

  /**
   * Returns the namespace of the check. Usually it's the same as the module's
   * name.
   *
   * @return string
   *   Human-readable namespace of the check.
   */
  public abstract function getNamespace();

  /**
   * Returns the machine name of the check.
   *
   * Naming rules (if overridden):
   *   - All characters should be lowerspace.
   *   - Use characters only from the english alphabet.
   *   - Don't use spaces (use "_" instead).
   *
   * @return string
   *   ID of check.
   */
  public function getMachineTitle() {
    $title = strtolower($this->getTitle());
    $title = preg_replace("/[^a-z0-9 ]/", '', $title);
    $title = str_replace(' ', '_', $title);

    return $title;
  }

  /**
   * Returns the human-readable title of the check.
   *
   * @return string
   *   Title of check.
   */
  public abstract function getTitle();

  /**
   * Returns the unique identifier constructed using the namespace, id pair.
   *
   * @return string
   *   Unique identifier of the check.
   */
  public final function getUniqueIdentifier() {
    return $this->getMachineNamespace() . '-' . $this->getMachineTitle();
  }

  /**
   * Returns whether Security Review should store the findings or reproduce them
   * when needed.
   *
   * The only case when this function should return false is if the check can
   * generate a lot of findings (like the File permissions check for example).
   * Turning this off for checks that don't generate findings at all or just a
   * few of them actually means more overhead as the check has to be re-run
   * in order to get its last result.
   *
   * @return boolean
   *   Boolean indicating whether findings will be stored.
   */
  public function storesFindings() {
    return TRUE;
  }

  /**
   * @return \Drupal\security_review\CheckSettingsInterface
   *   The settings interface of the check.
   */
  public function settings() {
    return $this->settings;
  }

  /**
   * The actual procedure of carrying out the check.
   *
   * @return CheckResult
   *   The result of running the check.
   */
  public abstract function run();

  /**
   * Returns the check-specific help page.
   *
   * @return array
   *   The render array of the check's help page.
   */
  public abstract function help();

  /**
   * Returns the evaluation page of a result. Usually this is a list of the
   * findings and an explanation.
   *
   * @param \Drupal\security_review\CheckResult $result
   *   The check result to evaluate.
   *
   * @return array
   *   The render array of the evaluation page.
   */
  public function evaluate(CheckResult $result) {
    return array();
  }

  /**
   * Converts a result integer to a human-readable result message.
   *
   * @param int $resultConst
   *   The result integer.
   *
   * @return string
   *   The human-readable result message.
   */
  public abstract function getMessage($resultConst);

  /**
   * Returns the last stored result of the check or null if no results have been
   * stored yet.
   *
   * @return \Drupal\security_review\CheckResult|null
   *   The last stored result (or null).
   */
  public function lastResult() {
    $result = $this->config->get('last_result.result');
    $findings = $this->config->get('last_result.findings');
    $time = $this->config->get('last_result.time');

    $validResult = is_int($result)
      && $result >= CheckResult::SUCCESS
      && $result <= CheckResult::INFO;
    $validFindings = is_array($findings);
    $validTime = is_int($time) && $time > 0;

    if (!$validResult || !$validFindings || !$validTime) {
      return NULL;
    }

    $lastResult = new CheckResult($this, $result, $findings, $time);

    if ($this->storesFindings()) {
      return $lastResult;
    }
    else {
      // Run the check to get the findings.
      $freshResult = $this->run();

      // If it malfunctioned return the last known good result.
      if (!($freshResult instanceof CheckResult)) {
        return $lastResult;
      }

      if ($freshResult->result() != $lastResult->result()) {
        // If the result is not the same store the new result and return it.
        $this->storeResult($freshResult);
        SecurityReview::logCheckResult($freshResult);
        return $freshResult;
      }
      else {
        // Else return the old result with the fresh one's findings.
        return CheckResult::combine($lastResult, $this->run());
      }
    }
  }

  /**
   * Returns the timestamp the check was last run, or 0 if it hasn't been run
   * yet.
   *
   * @return int
   *   The timestamp of the last stored result.
   */
  public function lastRun() {
    $lastResultTime = $this->config->get('last_result.time');

    if (!is_int($lastResultTime)) {
      return 0;
    }
    return $lastResultTime;
  }

  /**
   * Returns whether the check is skipped. Checks are not skipped by default.
   *
   * @return bool
   *   Boolean indicating whether the check is skipped.
   */
  public function isSkipped() {
    $isSkipped = $this->config->get('skipped');

    if (!is_bool($isSkipped)) {
      return FALSE;
    }
    return $isSkipped;
  }

  /**
   * Returns the user the check was skipped by, or null if it hasn't been
   * skipped yet or the user that skipped the check is not valid anymore.
   *
   * @return null|User
   *   The user the check was last skipped by (or null).
   */
  public function skippedBy() {
    $skippedBy = $this->config->get('skipped_by');

    if (!is_int($skippedBy)) {
      return NULL;
    }
    return User::load($skippedBy);
  }

  /**
   * Returns the timestamp the check was last skipped on, or 0 if it hasn't been
   * skipped yet.
   *
   * @return int
   *   The UNIX timestamp the check was last skipped on (or 0).
   */
  public function skippedOn() {
    $skippedOn = $this->config->get('skipped_on');

    if (!is_int($skippedOn)) {
      return 0;
    }
    return $skippedOn;
  }

  /**
   * Enables the check. Has no effect if the check was not skipped.
   */
  public function enable() {
    if ($this->isSkipped()) {
      $this->config->set('skipped', FALSE);
      $this->config->save();

      // Log.
      $context = array(
        'name' => $this->getTitle()
      );
      SecurityReview::log($this, '!name check no longer skipped', $context, RfcLogLevel::NOTICE);
    }
  }

  /**
   * Marks the check as skipped. It still can be ran manually, but will remain
   * skipped on the Run & Review page.
   */
  public function skip() {
    if (!$this->isSkipped()) {
      $this->config->set('skipped', TRUE);
      $this->config->set('skipped_by', Drupal::currentUser()->id());
      $this->config->set('skipped_on', time());
      $this->config->save();

      // Log.
      $context = array(
        'name' => $this->getTitle()
      );
      SecurityReview::log($this, '!name check skipped', $context, RfcLogLevel::NOTICE);
    }
  }

  /**
   * Stores a result in the configuration system.
   *
   * @param \Drupal\security_review\CheckResult $result
   *   The result to store.
   */
  public function storeResult(CheckResult $result = null) {
    if ($result == null) {
      $context = array(
        '!reviewcheck' => $this->getTitle(),
        '!namespace' => $this->getNamespace()
      );
      SecurityReview::log($this, 'Unable to store check !reviewcheck for !namespace', $context, RfcLogLevel::CRITICAL);
      return;
    }

    $this->config->set('last_result.result', $result->result());
    $this->config->set('last_result.time', $result->time());
    if ($this->storesFindings()) {
      $this->config->set('last_result.findings', $result->findings());
    }
    else {
      $this->config->set('last_result.findings', array());
    }
    $this->config->save();
  }

  /**
   * Creates a new CheckResult for this Check.
   *
   * @param $result
   *   The result integer (see the constants defined in CheckResult).
   * @param array $findings
   *   The findings.
   * @param null $time
   *   The time the test was run.
   *
   * @return \Drupal\security_review\CheckResult
   *   The created CheckResult.
   */
  public function createResult($result, array $findings = array(), $time = NULL) {
    return new CheckResult($this, $result, $findings, $time);
  }
}
