<?php

/**
 * @file
 * Contains \Drupal\security_review_test\TestCheck.
 */

namespace Drupal\security_review_test;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * A test security check for testing extensibility.
 */
class TestCheck extends Check {

  /**
   * @inheritDoc
   */
  public function getNamespace() {
    return 'Security Review Test';
  }

  /**
   * @inheritDoc
   */
  public function getTitle() {
    return 'Test';
  }

  /**
   * @inheritDoc
   */
  public function run() {
    return $this->createResult(CheckResult::INFO);
  }

  /**
   * @inheritDoc
   */
  public function help() {
    return array();
  }

  /**
   * @inheritDoc
   */
  public function getMessage($resultConst) {
    return 'The test ran.';
  }

}
