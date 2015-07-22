<?php

/**
 * @file
 * Contains \Drupal\security_review\CheckResult.
 */

namespace Drupal\security_review;

/**
 * Used to define the result of a Check.
 */
class CheckResult {

  const SKIPPED = -1;
  const SUCCESS = 0;
  const FAIL = 1;
  const WARN = 2;
  const INFO = 3;
  const HIDE = 4;

  /**
   * Stores the parent Check.
   *
   * @var \Drupal\security_review\Check $check
   */
  private $check;

  /**
   * Stores the outcome of the check.
   *
   * @var int $result
   */
  private $result;

  /**
   * Stores findings.
   *
   * @var array $findings
   */
  private $findings;

  /**
   * Stores the timestamp of the check run.
   *
   * @var int $time
   */
  private $time;

  /**
   * Constructs an immutable CheckResult.
   *
   * @param \Drupal\security_review\Check $check
   *   The Check that created this result.
   * @param int $result
   *   The result integer (see the constants defined above).
   * @param array $findings
   *   The findings.
   * @param int $time
   *   The timestamp of the check run.
   */
  public function __construct(Check $check, $result, array $findings, $time = NULL) {
    // Set the parent check.
    $this->check = $check;

    // Set the result value.
    $result = intval($result);
    if ($result < self::SUCCESS || $result > self::HIDE) {
      $result = self::INFO;
    }
    $this->result = $result;

    // Set the findings.
    $this->findings = $findings;

    // Set the timestamp.
    if (!is_int($time)) {
      $this->time = time();
    }
    else {
      $this->time = intval($time);
    }
  }

  /**
   * Combines two CheckResults.
   *
   * Combines two CheckResults and returns a new one with the old one's fields
   * except for the findings which are copied from the fresh result.
   *
   * @param \Drupal\security_review\CheckResult $old
   *   The old result to clone.
   * @param \Drupal\security_review\CheckResult $fresh
   *   The new result to copy the findings from.
   *
   * @return \Drupal\security_review\CheckResult
   *   The combined result.
   */
  public static function combine(CheckResult $old, CheckResult $fresh) {
    return new CheckResult($old->check, $old->result, $fresh->findings, $old->time);
  }

  /**
   * Returns the parent Check.
   *
   * @return \Drupal\security_review\Check
   *   The Check that created this result.
   */
  public function check() {
    return $this->check;
  }

  /**
   * Returns the outcome of the check.
   *
   * @return int
   *   The result integer.
   */
  public function result() {
    return $this->result;
  }

  /**
   * Returns the findings.
   *
   * @return array
   *   The findings. Contents of this depends on the actual check.
   */
  public function findings() {
    return $this->findings;
  }

  /**
   * Returns the timestamp.
   *
   * @return int
   *   The timestamp the result was created on.
   */
  public function time() {
    return $this->time;
  }

  /**
   * Returns the result message.
   *
   * @return string
   *   The result message for this result.
   */
  public function resultMessage() {
    return $this->check->getMessage($this->result);
  }

}
