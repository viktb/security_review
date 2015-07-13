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
   * @var \Drupal\security_review\Check $check
   */
  private $check;

  /**
   * @var int $result
   */
  private $result;

  /**
   * @var array $findings
   */
  private $findings;

  /**
   * @var int $time
   */
  private $time;

  /**
   * @param \Drupal\security_review\Check $check
   *   The Check that created this result.
   * @param $result
   *   The result integer (see the constants defined above).
   * @param array $findings
   *   The findings.
   * @param null $time
   *   The time the test was run.
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
   * @return \Drupal\security_review\Check
   *   The Check that created this result.
   */
  public function check() {
    return $this->check;
  }

  /**
   * @return int
   *   The result integer.
   */
  public function result() {
    return $this->result;
  }

  /**
   * @return array
   *   The findings. Contents of this depends on the actual check.
   */
  public function findings() {
    return $this->findings;
  }

  /**
   * @return int
   *   The time the result was generated.
   */
  public function time() {
    return $this->time;
  }

  /**
   * @return string
   *   The result message for this result.
   */
  public function resultMessage() {
    return $this->check->getMessage($this->result);
  }

}
