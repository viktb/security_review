<?php

/**
 * @file
 * Contains \Drupal\security_review\Check.
 */

namespace Drupal\security_review;

use Drupal\user\Entity\User;

/**
 * Defines a security check.
 */
abstract class Check {
  /**
   * @var \Drupal\security_review\CheckSettings $settings
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
  protected function __construct(){
    $this->config = \Drupal::configFactory()->getEditable('security_review.check.' . $this->getUniqueIdentifier());
    $this->settings = new CheckSettings($this->config);
  }

  /**
   * Returns the instance of the extending check.
   *
   * @return Check
   *   The instance of the check.
   */
  public static final function getInstance(){
    $calledClass = get_called_class();

    if (!isset(static::$instances[$calledClass]))
    {
      static::$instances[$calledClass] = new $calledClass();
    }

    return static::$instances[$calledClass];
  }

  /**
   * Returns the namespace of the check. Usually it's the same as the module's
   * name.
   *
   * Naming rules:
   *   - All characters should be lowerspace.
   *   - Use characters only from the english alphabet.
   *   - Don't use spaces (use "_" instead).
   *
   * @return string
   *   Namespace of check.
   */
  public abstract function getNamespace();

  /**
   * Returns the machine name of the check.
   *
   * Naming rules:
   *   - All characters should be lowerspace.
   *   - Use characters only from the english alphabet.
   *   - Don't use spaces (use "_" instead).
   *
   * @return string
   *   ID of check.
   */
  public abstract function getId();

  /**
   * Returns the unique identifier constructed using the namespace, id pair.
   *
   * @return string
   *   Unique identifier of the check.
   */
  public final function getUniqueIdentifier(){
    return strtolower($this->getNamespace() . '-' . $this->getId());
  }

  /**
   * Returns the human-readable title of the check.
   *
   * @return string
   *   Title of check.
   */
  public abstract function getTitle();

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
  public abstract function storesFindings();

  /**
   * @return \Drupal\security_review\CheckSettingsInterface
   *   The settings interface of the check.
   */
  public function settings(){
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
   * Returns the HTML contents of the check-specific help page.
   *
   * @return string
   *   The contents of the check's help page.
   */
  public abstract function help();

  /**
   * Returns the HTML evaluation page of a result. Usually this is a list of the
   * findings and an explanation.
   *
   * @param \Drupal\security_review\CheckResult $result
   *   The check result to evaluate.
   *
   * @return string
   *   The contents of the evaluation page.
   */
  public function evaluate(CheckResult $result){
    $output = '';
    $output .= '<strong>' . t('Findings') . '</strong><br />';
    $output .= '<ul>';
    foreach($result->findings() as $finding){
      $output .= '<li>' . $finding . '</li>';
    }
    $output .= '</ul>';

    return $output;
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
  public function getMessage($resultConst){
    switch($resultConst){
      case CheckResult::SUCCESS:
        return "The check was successful.";
      case CheckResult::FAIL:
        return "The check failed.";
      default:
        return "The check returned an unexpected result";
    }
  }

  /**
   * Returns the last stored result of the check or null if no results have been
   * stored yet.
   *
   * @return \Drupal\security_review\CheckResult|null
   *   The last stored result (or null).
   */
  public function lastResult() {
    if($this->lastRun() > 0){
      $lastResult = new CheckResult(
        $this,
        $this->config->get('last_result.result'),
        $this->config->get('last_result.findings'),
        $this->config->get('last_result.time')
      );

      if($this->storesFindings()){
        return $lastResult;
      }else{
        return CheckResult::combine($lastResult, $this->run($lastResult));
      }
    }

    return null;
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

    if($lastResultTime == null){
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

    if($isSkipped == null){
      return false;
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

    if($skippedBy == null){
      return null;
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

    if($skippedOn == null){
      return 0;
    }
    return $skippedOn;
  }

  /**
   * Enables the check. Has no effect if the check was not skipped.
   */
  public function enable() {
    $this->config->set('skipped', false);
    $this->config->save();
  }

  /**
   * Marks the check as skipped. It still can be ran manually, but will remain
   * skipped on the Run & Review page.
   */
  public function skip() {
    $this->config->set('skipped', true);
    $this->config->set('skipped_by', \Drupal::currentUser()->id());
    $this->config->set('skipped_on', time());
    $this->config->save();
  }

  /**
   * Stores a result in the configuration system.
   *
   * @param \Drupal\security_review\CheckResult $result
   *   The result to store.
   */
  public function storeResult(CheckResult $result) {
    $this->config->set('last_result.result', $result->result());
    $this->config->set('last_result.time', $result->time());
    if($this->storesFindings()){
      $this->config->set('last_result.findings', $result->findings());
    }else{
      $this->config->set('last_result.findings', array());
    }
    $this->config->save();
  }
}
