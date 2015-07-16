<?php

/**
 * @file
 * Contains \Drupal\security_review_test\Test.
 */

namespace Drupal\security_review_test;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * A test security check for testing extensibility.
 */
class Test extends Check {

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
    $findings = array();
    for ($i = 0; $i < 20; ++$i) {
      $findings[] = rand(0, 1) ? rand(0, 10) : 'string';
    }

    return $this->createResult(CheckResult::HIDE, $findings);
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
