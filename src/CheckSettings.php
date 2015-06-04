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
   * @var string $uniqueIdentifier
   */
  protected $uniqueIdentifier;

  /**
   * @param string $uniqueIdentifier
   * @param \Drupal\Core\Config\Config $config
   */
  public function __construct($uniqueIdentifier, Config &$config){
    $this->uniqueIdentifier = $uniqueIdentifier;
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return $this->config->get('settings.' . $key);
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
