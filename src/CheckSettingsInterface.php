<?php

/**
 * @file
 * Contains \Drupal\security_review\CheckSettingsInterface.
 */

namespace Drupal\security_review;

/**
 * Defines an interface for accessing check-specific settings and creating forms
 * that can alter these settings.
 */
interface CheckSettingsInterface {
  // TODO: A sensible way of generating a subform.

  /**
   * Gets a check-specific setting value identified by $key.
   *
   * @param $key
   *   The key.
   *
   * @return mixed
   *   The value of the stored setting.
   */
  public function get($key);

  /**
   * Sets a check-specific setting value identified by $key.
   *
   * @param $key
   *   The key.
   * @param $value
   *   The new value.
   *
   * @return CheckSettingsInterface
   *   Returns itself.
   */
  public function set($key, $value);
}
