<?php

/**
 * @file
 * Contains \Drupal\security_review\Tests\CheckTest
 */

namespace Drupal\security_review\Tests;

use Drupal\security_review\CheckResult;
use Drupal\simpletest\KernelTestBase;

/**
 * Contains tests for Checks.
 *
 * @group security_review
 */
class CheckTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('security_review', 'security_review_test');

  /**
   * The security checks defined by Security Review and Security Review Test.
   *
   * @var \Drupal\security_review\Check[]
   */
  protected $checks;

  /**
   * The security checks defined by Security Review.
   *
   * @var \Drupal\security_review\Check[]
   */
  protected $realChecks;

  /**
   * The security checks defined by Security Review Test.
   *
   * @var \Drupal\security_review\Check[]
   */
  protected $testChecks;

  /**
   * Sets up the environment, populates the $checks variable.
   */
  protected function setUp() {
    parent::setUp();
    $this->realChecks = security_review_security_review_checks();
    $this->testChecks = security_review_test_security_review_checks();
    $this->checks = array_merge($this->realChecks, $this->testChecks);
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
   * Tests some check's results on a clean install of Drupal.
   */
  public function testDefaultResults() {
    $defaults = array(
      'security_review-field' => CheckResult::SUCCESS,
    );

    foreach ($this->checks as $check) {
      if (array_key_exists($check->id(), $defaults)) {
        $result = $check->run();
        $this->assertEqual($result->result(), $defaults[$check->id()], $check->getTitle() . ' produced the right result.');
      }
    }
  }

  /**
   * Tests the storing of a check result on every check. The lastResult() should
   * return the same as the result that got stored.
   */
  public function testStoreResult() {
    foreach ($this->testChecks as $check) {
      // Run the check and store its result.
      $result = $check->run();
      $check->storeResult($result);

      // Compare lastResult() with $result.
      $lastResult = $check->lastResult(TRUE);
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
    foreach ($this->testChecks as $check) {
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
        $lastResult = $check->lastResult(TRUE);
        $this->assertEqual($lastResult->result(), $result->result(), 'Invalid result got updated.');
      }
    }
  }

}
