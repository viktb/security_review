<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\QueryErrors.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks for abundant query errors.
 */
class QueryErrors extends Check {

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Query errors';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // If dblog is not enabled return with INFO.
    if (!Drupal::moduleHandler()->moduleExists('dblog')) {
      return $this->createResult(CheckResult::INFO);
    }

    $result = CheckResult::SUCCESS;
    $findings = array();
    $lastResult = $this->lastResult();

    // Prepare the query.
    $query = Drupal::database()->select('watchdog', 'w');
    $query->fields('w', array(
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'hostname',
    ));
    $query->condition('type', 'php')
      ->condition('severity', RfcLogLevel::ERROR);
    if ($lastResult instanceof CheckResult) {
      $query->condition('timestamp', $lastResult->time(), '>=');
    }

    // Execute the query.
    $dbResult = $query->execute();

    // Count the number of query errors per IP.
    $entries = array();
    foreach ($dbResult as $row) {
      $message = t(
        $row->message,
        unserialize($row->variables)
      );
      $ip = $row->hostname;

      if (strpos($message, 'SQL') !== FALSE && strpos($message, 'SELECT') !== FALSE) {
        $entryForIP = &$entries[$ip];

        if (!isset($entryForIP)) {
          $entryForIP = 0;
        }
        $entryForIP++;
      }
    }

    // Filter the IPs with more than 10 query errors.
    if (!empty($entries)) {
      foreach ($entries as $ip => $count) {
        if ($count > 10) {
          $findings[] = $ip;
        }
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = array();
    $paragraphs[] = "Database errors triggered from the same IP may be an artifact of a malicious user attempting to probe the system for weaknesses like SQL injection or information disclosure.";

    return array(
      '#theme' => 'check_help',
      '#title' => 'Abundant query errors from the same IP',
      '#paragraphs' => $paragraphs
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return array();
    }

    $paragraphs = array();
    $paragraphs[] = "The following IPs were observed with an abundance of query errors.";

    return array(
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $result->findings()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return '';
    }

    $output = t('Suspicious IP addresses:') . ":\n";
    foreach ($findings as $ip) {
      $output .= "\t" . $ip . "\n";
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($resultConst) {
    switch ($resultConst) {
      case CheckResult::SUCCESS:
        return 'Normal amount of query errors from the same IP.';
      case CheckResult::FAIL:
        return 'Query errors from the same IP. These may be a SQL injection attack or an attempt at information disclosure.';
      case CheckResult::INFO:
        return 'Module dblog is not enabled.';
      default:
        return 'Unexpected result.';
    }
  }

}
