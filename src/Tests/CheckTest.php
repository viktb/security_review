<?php

/**
 * @file
 * Contains \Drupal\security_review\Tests\CheckTest
 */

namespace Drupal\security_review\Tests;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\simpletest\KernelTestBase;

/**
 * Class CheckTest
 *
 * @group security_review
 */
class CheckTest extends KernelTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('security_review');

  /**
   * The security checks defined by Security Review.
   *
   * @var \Drupal\security_review\Check[]
   */
  protected $checks;

  /**
   * Sets up the environment, populates the $checks variable.
   */
  protected function setUp() {
    parent::setUp();
    $this->checks = security_review_security_review_checks();
  }

  /**
   * Tests whether $checks is empty.
   */
  protected function testChecksExist() {
    $this->assertFalse(empty($this->checks), 'Checks found.');
  }

  /**
   * Every check should be enabled by default.
   */
  public function testEnabledByDefault() {
    foreach ($this->checks as $check) {
      $this->assertFalse($check->isSkipped(), $check->getTitle() . ' is enabled by default.');
    }
  }

  /**
   * Tests the storing of a check result on every check. The lastResult() should
   * return the same as the result that got stored.
   */
  public function testStoreResult() {
    foreach ($this->checks as $check) {
      // Run the check and store its result.
      $result = $check->run();
      $check->storeResult($result);

      // Compare lastResult() with $result.
      $lastResult = $check->lastResult();
      $this->assertEqual($result->result(), $lastResult->result(), 'Result stored.');
      $this->assertEqual($result->time(), $lastResult->time(), 'Time stored.');
      if ($check->storesFindings()) {
        // If storesFindings() is set to FALSE, then these could differ.
        $this->assertEqual($result->findings(), $lastResult->findings(), 'Findings stored.');
      }
    }
  }

  /**
   * Tests the case when the check doesn't store its findings, and the new result
   * that got produced when calling lastResult() overwrites the old one if the
   * result integer is not the same.
   */
  public function testLastResultUpdate() {
    foreach ($this->checks as $check) {
      if (!$check->storesFindings()) {
        // Get the real result.
        $result = $check->run();

        // Build the fake result.
        $newResultInt = $result->result() == CheckResult::SUCCESS ? CheckResult::FAIL : CheckResult::SUCCESS;
        $newResult = new CheckResult(
          $check,
          $newResultInt,
          array()
        );

        // Store it.
        $check->storeResult($newResult);

        // Check if lastResult()'s result integer is the same as $result's.
        $lastResult = $check->lastResult();
        $this->assertEqual($lastResult->result(), $result->result(), 'Invalid result got updated.');
      }
    }
  }
}
