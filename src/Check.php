<?php

/**
 * @file
 * Contains \Drupal\security_review\Check.
 */

namespace Drupal\security_review;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;


/**
 * Defines a security check.
 */
abstract class Check {

  use LinkGeneratorTrait;
  use UrlGeneratorTrait;
  use StringTranslationTrait;

  /**
   * The configuration storage for this check.
   *
   * @var \Drupal\Core\Config\Config $config
   */
  protected $config;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The security_review.security service.
   *
   * @var \Drupal\security_review\Security
   */
  protected $security;

  /**
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected $securityReview;

  /**
   * Settings handler for this check.
   *
   * @var \Drupal\security_review\CheckSettingsInterface $settings
   */
  protected $settings;

  /**
   * The state storage.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The check's prefix in the State system.
   *
   * @var string
   */
  protected $statePrefix;

  /**
   * Initializes the configuration storage and the settings handler.
   */
  public function __construct() {
    $container = \Drupal::getContainer();

    // Not injected because of easier instantiation.
    $config_factory = $container->get('config.factory');
    $current_user = $container->get('current_user');
    $security = $container->get('security_review.security');
    $security_review = $container->get('security_review');
    $state = $container->get('state');

    $this->config = $config_factory
      ->getEditable('security_review.check.' . $this->id());
    $this->currentUser = $current_user;
    $this->security = $security;
    $this->securityReview = $security_review;
    $this->settings = new CheckSettings($this, $this->config);
    $this->state = $state;
    $this->statePrefix = 'security_review.check.' . $this->id() . '.';

    // Set check ID in config.
    if ($this->config->get('id') != $this->id()) {
      $this->config->set('id', $this->id());
      $this->config->save();
    }
  }

  /**
   * Returns the namespace of the check.
   *
   * Usually it's the same as the module's name.
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
   * Returns the namespace of the check.
   *
   * Usually it's the same as the module's name.
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
   * Returns the identifier constructed using the namespace and title values.
   *
   * @return string
   *   Unique identifier of the check.
   */
  public final function id() {
    return $this->getMachineNamespace() . '-' . $this->getMachineTitle();
  }

  /**
   * Returns whether the findings should be stored or reproduced when needed.
   *
   * The only case when this function should return false is if the check can
   * generate a lot of findings (like the File permissions check for example).
   * Turning this off for checks that don't generate findings at all or just a
   * few of them actually means more overhead as the check has to be re-run
   * in order to get its last result.
   *
   * @return bool
   *   Boolean indicating whether findings will be stored.
   */
  public function storesFindings() {
    return TRUE;
  }

  /**
   * Returns the check-specific settings' handler.
   *
   * @return \Drupal\security_review\CheckSettingsInterface
   *   The settings interface of the check.
   */
  public function settings() {
    return $this->settings;
  }

  /**
   * The actual procedure of carrying out the check.
   *
   * @return \Drupal\security_review\CheckResult
   *   The result of running the check.
   */
  public abstract function run();

  /**
   * Same as run(), but used in CLI context such as Drush.
   *
   * @return \Drupal\security_review\CheckResult
   *   The result of running the check.
   */
  public function runCli() {
    return $this->run();
  }

  /**
   * Returns the check-specific help page.
   *
   * @return array
   *   The render array of the check's help page.
   */
  public abstract function help();

  /**
   * Returns the evaluation page of a result.
   *
   * Usually this is a list of the findings and an explanation.
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
   * Evaluates a CheckResult and returns a plaintext output.
   *
   * @param \Drupal\security_review\CheckResult $result
   *   The check result to evaluate.
   *
   * @return string
   *   The evaluation string.
   */
  public function evaluatePlain(CheckResult $result) {
    return '';
  }

  /**
   * Converts a result integer to a human-readable result message.
   *
   * @param int $result_const
   *   The result integer.
   *
   * @return string
   *   The human-readable result message.
   */
  public abstract function getMessage($result_const);

  /**
   * Returns the last stored result of the check.
   *
   * Returns null if no results have been stored yet.
   *
   * @param bool $get_findings
   *   Whether to get the findings too.
   *
   * @return \Drupal\security_review\CheckResult|null
   *   The last stored result (or null).
   */
  public function lastResult($get_findings = FALSE) {
    $state_prefix = $this->statePrefix . 'last_result.';
    $result = $this->state->get($state_prefix . 'result');
    if ($get_findings) {
      $findings = $this->state->get($state_prefix . 'findings');
    }
    else {
      $findings = array();
    }
    $time = $this->state->get($state_prefix . 'time');

    $valid_result = is_int($result)
      && $result >= CheckResult::SUCCESS
      && $result <= CheckResult::HIDE;
    $valid_findings = is_array($findings);
    $valid_time = is_int($time) && $time > 0;

    if (!$valid_result || !$valid_findings || !$valid_time) {
      return NULL;
    }

    $last_result = new CheckResult($this, $result, $findings, $time);

    if ($get_findings && !$this->storesFindings()) {
      // Run the check to get the findings.
      $fresh_result = $this->run();

      // If it malfunctioned return the last known good result.
      if (!($fresh_result instanceof CheckResult)) {
        return $last_result;
      }

      if ($fresh_result->result() != $last_result->result()) {
        // If the result is not the same store the new result and return it.
        $this->storeResult($fresh_result);
        $this->securityReview->logCheckResult($fresh_result);
        return $fresh_result;
      }
      else {
        // Else return the old result with the fresh one's findings.
        return CheckResult::combine($last_result, $fresh_result);
      }
    }

    return $last_result;
  }

  /**
   * Returns the timestamp the check was last run.
   *
   * Returns 0 if it has not been run yet.
   *
   * @return int
   *   The timestamp of the last stored result.
   */
  public function lastRun() {
    $last_result_time = $this->state
      ->get($this->statePrefix . 'last_result.time');

    if (!is_int($last_result_time)) {
      return 0;
    }
    return $last_result_time;
  }

  /**
   * Returns whether the check is skipped. Checks are not skipped by default.
   *
   * @return bool
   *   Boolean indicating whether the check is skipped.
   */
  public function isSkipped() {
    $is_skipped = $this->config->get('skipped');

    if (!is_bool($is_skipped)) {
      return FALSE;
    }
    return $is_skipped;
  }

  /**
   * Returns the user the check was skipped by.
   *
   * Returns null if it hasn't been skipped yet or the user that skipped the
   * check is not valid anymore.
   *
   * @return \Drupal\user\Entity\User|null
   *   The user the check was last skipped by (or null).
   */
  public function skippedBy() {
    $skipped_by = $this->config->get('skipped_by');

    if (!is_int($skipped_by)) {
      return NULL;
    }
    return User::load($skipped_by);
  }

  /**
   * Returns the timestamp the check was last skipped on.
   *
   * Returns 0 if it hasn't been skipped yet.
   *
   * @return int
   *   The UNIX timestamp the check was last skipped on (or 0).
   */
  public function skippedOn() {
    $skipped_on = $this->config->get('skipped_on');

    if (!is_int($skipped_on)) {
      return 0;
    }
    return $skipped_on;
  }

  /**
   * Enables the check. Has no effect if the check was not skipped.
   */
  public function enable() {
    if ($this->isSkipped()) {
      $this->config->set('skipped', FALSE);
      $this->config->save();

      // Log.
      $context = array('!name' => $this->getTitle());
      $this->securityReview->log($this, '!name check no longer skipped', $context, RfcLogLevel::NOTICE);
    }
  }

  /**
   * Marks the check as skipped.
   *
   * It still can be ran manually, but will remain skipped on the Run & Review
   * page.
   */
  public function skip() {
    if (!$this->isSkipped()) {
      $this->config->set('skipped', TRUE);
      $this->config->set('skipped_by', $this->currentUser->id());
      $this->config->set('skipped_on', time());
      $this->config->save();

      // Log.
      $context = array('!name' => $this->getTitle());
      $this->securityReview->log($this, '!name check skipped', $context, RfcLogLevel::NOTICE);
    }
  }

  /**
   * Stores a result in the state system.
   *
   * @param \Drupal\security_review\CheckResult $result
   *   The result to store.
   */
  public function storeResult(CheckResult $result = NULL) {
    if ($result == NULL) {
      $context = array(
        '!reviewcheck' => $this->getTitle(),
        '!namespace' => $this->getNamespace(),
      );
      $this->securityReview->log($this, 'Unable to store check !reviewcheck for !namespace', $context, RfcLogLevel::CRITICAL);
      return;
    }

    $findings = $this->storesFindings() ? $result->findings() : array();
    $this->state->setMultiple(array(
      $this->statePrefix . 'last_result.result' => $result->result(),
      $this->statePrefix . 'last_result.time' => $result->time(),
      $this->statePrefix . 'last_result.findings' => $findings,
    ));
  }

  /**
   * Creates a new CheckResult for this Check.
   *
   * @param int $result
   *   The result integer (see the constants defined in CheckResult).
   * @param array $findings
   *   The findings.
   * @param int $time
   *   The time the test was run.
   *
   * @return \Drupal\security_review\CheckResult
   *   The created CheckResult.
   */
  public function createResult($result, array $findings = array(), $time = NULL) {
    return new CheckResult($this, $result, $findings, $time);
  }

}
