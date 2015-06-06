<?php

/**
 * @file
 * Contains \Drupal\security_review\SecurityReview.
 */

namespace Drupal\security_review;
use Drupal\Core\Access\AccessException;

/**
 * A class containing static methods regarding the module's configuration.
 */
class SecurityReview {
  /**
   * Private constructor for disabling instantiation of the static class.
   */
  private function __construct() {}

  /**
   * @var \Drupal\Core\Config\Config $config
   */
  private static $config = null;

  /**
   * @return \Drupal\Core\Config\Config
   */
  private static function config() {
    if(static::$config == null){
      static::$config = \Drupal::configFactory()->getEditable('security_review.settings');
    }

    return static::$config;
  }

  /**
   * If the module has been configured on the settings page this function
   * returns true. Otherwise it returns false.
   *
   * @return bool
   *   A boolean indicating whether the module has been configured.
   */
  public static function isConfigured() {
    return static::config()->get('configured') === TRUE;
  }

  /**
   * Returns true if logging is enabled, otherwise returns false.
   *
   * @return bool
   *   A boolean indicating whether logging is enabled.
   */
  public static function isLogging() {
    return static::config()->get('log') === TRUE;
  }

  /**
   * Returns the last time Security Review has been run.
   *
   * @return int
   *   The last time Security Review has been run.
   */
  public static function getLastRun() {
    return static::config()->get('last_run');
  }

  /**
   * Returns the IDs of the stored untrusted roles.
   *
   * @return array
   *   Stored untrusted roles' IDs.
   */
  public static function getUntrustedRoles() {
    return static::config()->get('untrusted_roles');
  }

  /**
   * Sets the 'configured' flag.
   *
   * @param bool $configured
   *   The new value of the 'configured' setting.
   */
  public static function setConfigured($configured) {
    static::config()->set('configured', $configured);
    static::config()->save();
  }

  /**
   * Sets the 'logging' flag.
   *
   * @param bool $logging
   *   The new value of the 'logging' setting.
   */
  public static function setLogging($logging) {
    static::config()->set('log', $logging);
    static::config()->save();
  }

  /**
   * Sets the 'last_run' value.
   *
   * @param int $lastRun
   *   The new value for 'last_run'.
   */
  public static function setLastRun($lastRun) {
    static::config()->set('last_run', $lastRun);
    static::config()->save();
  }

  /**
   * Stores the given 'untrusted_roles' setting.
   *
   * @param array $untrustedRoles
   *   The new untrusted roles' IDs.
   */
  public static function setUntrustedRoles(array $untrustedRoles) {
    static::config()->set('untrusted_roles', $untrustedRoles);
    static::config()->save();
  }

  /**
   * Runs enabled checks and stores their results.
   */
  public static function runChecklist(){
    if (\Drupal::currentUser()->hasPermission('run security checks')) {
      $checks = Checklist::getEnabledChecks();
      $results = Checklist::runChecks($checks);
      Checklist::storeResults($results);
      SecurityReview::setLastRun(time());
    } else {
      throw new AccessException();
    }
  }
}
