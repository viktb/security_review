<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\FailedLogins.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks for abundant failed logins.
 */
class FailedLogins extends Check {

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
    return 'Failed logins';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // If dblog is not enabled return with INFO.
    if (!Drupal::moduleHandler()->moduleExists('dblog')) {
      return $this->createResult(CheckResult::INFO);
    }

    $result = CheckResult::HIDE;
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
    $query->condition('type', 'user')
      ->condition('severity', RfcLogLevel::NOTICE)
      ->condition('message', 'Login attempt failed from %ip.');
    if ($lastResult instanceof CheckResult) {
      // Only check entries that got recorded since the last run of the check.
      $query->condition('timestamp', $lastResult->time(), '>=');
    }

    // Execute the query.
    $dbResult = $query->execute();

    // Count the number of failed logins per IP.
    $entries = array();
    foreach ($dbResult as $row) {
      $ip = unserialize($row->variables)['%ip'];
      $entryForIP = &$entries[$ip];

      if (!isset($entryForIP)) {
        $entryForIP = 0;
      }
      $entryForIP++;
    }

    // Filter the IPs with more than 10 failed logins.
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
    $paragraphs[] = "Failed login attempts from the same IP may be an artifact of a malicious user attempting to brute-force their way onto your site as an authenticated user to carry out nefarious deeds.";

    return array(
      '#theme' => 'check_help',
      '#title' => 'Abundant failed logins from the same IP',
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
    $paragraphs[] = "The following IPs were observed with an abundance of failed login attempts.";

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
      case CheckResult::FAIL:
        return 'Failed login attempts from the same IP. These may be a brute-force attack to gain access to your site.';
      case CheckResult::INFO:
        return 'Module dblog is not enabled.';
      default:
        return 'Unexpected result.';
    }
  }

}
