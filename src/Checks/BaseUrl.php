<?php

namespace Drupal\security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\CheckSettings\BaseUrlSettings;

class BaseUrl extends Check {
  /**
   * {@inheritdoc}
   */
  public function __construct(){
    parent::__construct();
    $this->settings = new BaseUrlSettings($this, $this->config);
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Drupal base URL';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'base_url_set';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // TODO: Implement run() method.
    return new CheckResult($this, CheckResult::SUCCESS, array());
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    // TODO: Implement help() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($resultConst) {
    switch($resultConst){
      case CheckResult::SUCCESS:
        return t('Base URL is set in settings.php.');
      case CheckResult::FAIL:
        return t('Base URL is not set in settings.php.');
      default:
        return "Unexpected result.";
    }
  }
}
