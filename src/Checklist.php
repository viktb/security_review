<?php

/**
 * @file
 * Contains \Drupal\security_review\Checklist.
 */

namespace Drupal\security_review;

use Drupal;

/**
 * Contains static functions for handling checks throughout all modules.
 */
class Checklist {
  /**
   * Private constructor for disabling instantiation of the static class.
   */
  private function __construct() {}

  /**
   * Returns the checks that are returned by hook_security_review_checks.
   *
   * @return array
   *   Array of Checks.
   */
  public static function getChecks() {
    // Cache checks.
    static $checks = array();

    if (!empty($checks)) {
      return $checks;
    }

    // Get checks.
    $raw_checks = Drupal::moduleHandler()->invokeAll('security_review_checks');

    // Filter invalid checks.
    $checks = array();
    foreach ($raw_checks as $raw_check) {
      if ($raw_check instanceof Check) {
        $checks[] = $raw_check;
      }
    }

    return $checks;
  }

  /**
   * Returns the enabled checks that are returned by hook_security_review_checks.
   *
   * @return array
   *   Array of enabled Checks.
   */
  public static function getEnabledChecks() {
    $enabled = array();

    foreach (static::getChecks() as $check) {
      /** @var Check $check */
      if (!$check->isSkipped()) {
        $enabled[] = $check;
      }
    }

    return $enabled;
  }

  /**
   * @param array $checks
   *   The array of Checks to group.
   *
   * @return array
   *   Array containing Checks grouped by their namespaces.
   */
  public static function groupChecksByNamespace(array $checks) {
    $output = array();

    foreach ($checks as $check) {
      /** @var Check $check */
      $output[$check->getMachineNamespace()][] = $check;
    }

    return $output;
  }

  /**
   * @param array $checks
   *   The array of Checks to run.
   *
   * @return array
   *   The array of CheckResults generated.
   */
  public static function runChecks(array $checks) {
    $results = array();

    foreach ($checks as $check) {
      /** @var Check $check */

      $result = $check->run();
      SecurityReview::logCheckResult($result);
      $results[] = $result;
    }

    return $results;
  }

  /**
   * @param array $results
   *   The CheckResults to store.
   */
  public static function storeResults(array $results) {
    foreach ($results as $result) {
      /** @var CheckResult $result */
      $result->check()->storeResult($result);
    }
  }

  /**
   * @param string $namespace
   *   The machine namespace of the requested check.
   * @param string $title
   *   The machine title of the requested check.
   *
   * @return null|Check
   *   The Check or null if it doesn't exist.
   */
  public static function getCheck($namespace, $title) {
    foreach (static::getChecks() as $check) {
      /** @var Check $check */
      if ($check->getMachineNamespace() == $namespace
        && $check->getMachineTitle() == $title) {
        return $check;
      }
    }

    return NULL;
  }

  /**
   * @param string $uniqueIdentifier
   *   The machine namespace of the requested check.
   *
   * @return null|Check
   *   The Check or null if it doesn't exist.
   */
  public static function getCheckByIdentifier($uniqueIdentifier) {
    foreach (static::getChecks() as $check) {
      /** @var Check $check */
      if ($check->id() == $uniqueIdentifier) {
        return $check;
      }
    }

    return NULL;
  }
}
