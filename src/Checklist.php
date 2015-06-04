<?php

/**
 * @file
 * Contains \Drupal\security_review\Checklist.
 */

namespace Drupal\security_review;

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
   * @param string $namespace
   *   The namespace to filter checks by.
   *
   * @return array
   *   Array of Checks.
   */
  public static function checks($namespace = null) {
    // Get checks.
    $raw_checks = \Drupal::moduleHandler()->invokeAll('security_review_checks');

    // Filter invalid checks.
    $checks = array();
    foreach($raw_checks as $raw_check){
      if($raw_check instanceof Check){
        if($namespace == null || $raw_check->getNamespace() == $namespace){
          $checks[] = $raw_check;
        }
      }
    }

    return $checks;
  }

  /**
   * @param array $checks
   *   The array of Checks to group.
   *
   * @return array
   *   Array containing Checks grouped by their namespaces.
   */
  public static function groupChecksByNamespace(array $checks){
    $output = array();

    foreach($checks as $check){
      $output[$check->getNamespace()][] = $check;
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

    foreach($checks as $check){
      $results[] = $check->run();
    }

    return $results;
  }

  /**
   * @param array $results
   *   The CheckResults to store.
   */
  public static function storeResults(array $results) {
    foreach($results as $result){
      $result->check()->storeResult($result);
    }
  }

  /**
   * @param $namespace
   *   The namespace of the requested check.
   * @param $id
   *   The ID of the requested check.
   *
   * @return null|Check
   *   The Check or null if it doesn't exist.
   */
  public static function getCheck($namespace, $id) {
    foreach(static::checks($namespace) as $check){
      if($check->getId() == $id){
        return $check;
      }
    }

    return null;
  }
}
