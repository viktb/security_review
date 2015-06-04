<?php

namespace Drupal\security_review;

use Drupal\Core\Config\Config;

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
}
