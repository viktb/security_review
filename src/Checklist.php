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
   * Array of cached Checks.
   *
   * @var \Drupal\security_review\Check[]
   */
  private static $cachedChecks = array();

  /**
   * Clears the cached checks.
   */
  public static function clearCache() {
    static::$cachedChecks = array();
  }

  /**
   * Returns the checks that are returned by hook_security_review_checks.
   *
   * @return \Drupal\security_review\Check[]
   *   Array of Checks.
   */
  public static function getChecks() {
    $checks = &static::$cachedChecks;
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

    // Sort the checks.
    usort($checks, "static::compareChecks");

    return $checks;
  }

  /**
   * Returns the enabled checks that are returned by hook_security_review_checks.
   *
   * @return \Drupal\security_review\Check[]
   *   Array of enabled Checks.
   */
  public static function getEnabledChecks() {
    $enabled = array();

    foreach (static::getChecks() as $check) {
      if (!$check->isSkipped()) {
        $enabled[] = $check;
      }
    }

    return $enabled;
  }

  /**
   * @param \Drupal\security_review\Check[] $checks
   *   The array of Checks to group.
   *
   * @return array
   *   Array containing Checks grouped by their namespaces.
   */
  public static function groupChecksByNamespace(array $checks) {
    $output = array();

    foreach ($checks as $check) {
      $output[$check->getMachineNamespace()][] = $check;
    }

    return $output;
  }

  /**
   * @param \Drupal\security_review\Check[] $checks
   *   The array of Checks to run.
   * @param bool $cli
   *   Whether to call runCli() instead of run().
   *
   * @return \Drupal\security_review\CheckResult[]
   *   The array of CheckResults generated.
   */
  public static function runChecks(array $checks, $cli = FALSE) {
    $results = array();

    foreach ($checks as $check) {
      if ($cli) {
        $result = $check->runCli();
      }
      else {
        $result = $check->run();
      }
      SecurityReview::logCheckResult($result);
      $results[] = $result;
    }

    return $results;
  }

  /**
   * @param \Drupal\security_review\CheckResult[] $results
   *   The CheckResults to store.
   */
  public static function storeResults(array $results) {
    foreach ($results as $result) {
      $result->check()->storeResult($result);
    }
  }

  /**
   * @param string $namespace
   *   The machine namespace of the requested check.
   * @param string $title
   *   The machine title of the requested check.
   *
   * @return null|\Drupal\security_review\Check
   *   The Check or null if it doesn't exist.
   */
  public static function getCheck($namespace, $title) {
    foreach (static::getChecks() as $check) {
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
   * @return null|\Drupal\security_review\Check
   *   The Check or null if it doesn't exist.
   */
  public static function getCheckByIdentifier($uniqueIdentifier) {
    foreach (static::getChecks() as $check) {
      if ($check->id() == $uniqueIdentifier) {
        return $check;
      }
    }

    return NULL;
  }

  /**
   * Helper function for sorting checks.
   *
   * @param \Drupal\security_review\Check $a
   *   Check A.
   * @param \Drupal\security_review\Check $b
   *   Check B.
   *
   * @return int
   *   The comparison's result.
   */
  public static function compareChecks(Check $a, Check $b) {
    // If one comes from security_review and the other doesn't, prefer the one
    // with the security_review namespace.
    if ($a->getMachineNamespace() == 'security_review' && $b->getMachineNamespace() != 'security_review') {
      return -1;
    }
    elseif ($a->getMachineNamespace() != 'security_review' && $b->getMachineNamespace() == 'security_review') {
      return 1;
    }
    else {
      if ($a->getNamespace() == $b->getNamespace()) {
        // If the namespaces match, sort by title.
        return strcmp($a->getTitle(), $b->getTitle());
      }
      else {
        // If the namespaces don't mach, sort by namespace.
        return strcmp($a->getNamespace(), $b->getNamespace());
      }
    }
  }

}
