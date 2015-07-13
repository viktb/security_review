<?php

/**
 * @file
 * Contains \Drupal\security_review\CheckSettings.
 */

namespace Drupal\security_review;

use Drupal\Core\Config\Config;

/**
 * Defines a default implementation of CheckSettingsInterface which will be
 * enough for most checks.
 */
class CheckSettings implements CheckSettingsInterface {

  /**
   * @var \Drupal\Core\Config\Config $config
   */
  protected $config;

  /**
   * @var \Drupal\security_review\Check
   */
  protected $check;

  /**
   * @param \Drupal\security_review\Check $check
   * @param \Drupal\Core\Config\Config $config
   */
  public function __construct(Check $check, Config &$config) {
    $this->check = $check;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key, $defaultValue = NULL) {
    $value = $this->config->get('settings.' . $key);

    if ($value == NULL) {
      return $defaultValue;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->config->set('settings.' . $key, $value);
    $this->config->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, $values) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, $values) {
    // Handle submission.
  }

}
