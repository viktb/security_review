<?php

/**
 * @file
 * Contains \Drupal\security_review\Checklist.
 */

namespace Drupal\security_review;

use Drupal;

/**
 * Contains static functions for handling checks throughout every module.
 */
class Checklist {

  /**
   * Private constructor for disabling instantiation of the static class.
   */
  private function __construct() {
  }

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
   * Returns every Check.
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
   * Returns the enabled Checks.
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
   * Groups an array of checks by their namespaces.
   *
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
   * Runs an array of checks.
   *
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
   * Stores an array of CheckResults.
   *
   * @param \Drupal\security_review\CheckResult[] $results
   *   The CheckResults to store.
   */
  public static function storeResults(array $results) {
    foreach ($results as $result) {
      $result->check()->storeResult($result);
    }
  }

  /**
   * Finds a check by its namespace and title.
   *
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
      $same_namespace = $check->getMachineNamespace() == $namespace;
      $same_title = $check->getMachineTitle() == $title;
      if ($same_namespace && $same_title) {
        return $check;
      }
    }

    return NULL;
  }

  /**
   * Finds a Check by its id.
   *
   * @param string $id
   *   The machine namespace of the requested check.
   *
   * @return null|\Drupal\security_review\Check
   *   The Check or null if it doesn't exist.
   */
  public static function getCheckById($id) {
    foreach (static::getChecks() as $check) {
      if ($check->id() == $id) {
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
    $a_is_local = $a->getMachineNamespace() == 'security_review';
    $b_is_local = $b->getMachineNamespace() == 'security_review';
    if ($a_is_local && !$b_is_local) {
      return -1;
    }
    elseif (!$a_is_local && $b_is_local) {
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
