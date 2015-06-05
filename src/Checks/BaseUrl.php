<?php

namespace Drupal\security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckSettings\BaseUrlSettings;
use Drupal\security_review\CheckResult;

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
    return t('Security Review');
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return t('Base URL');
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // TODO: Implement run() method.
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    // TODO: Implement help() method.
  }
}
