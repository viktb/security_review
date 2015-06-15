<?php

/**
 * @file
 * Contains \Drupal\security_review\Tests\SecurityReviewWebTest
 */

namespace Drupal\security_review\Tests;

use Drupal\security_review\SecurityReview;
use Drupal\security_review\Check;
use Drupal\security_review\Checklist;
use Drupal\simpletest\WebTestBase;

/**
 * Contains tests related to the SecurityReview class.
 *
 * @group security_review
 */
class SecurityReviewWebTest extends WebTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('security_review');

  /**
   * The test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * The security checks defined by Security Review.
   *
   * @var array
   */
  protected $checks;

  /**
   * Sets up the testing environment.
   */
  protected function setUp() {
    parent::setUp();

    // Login.
    $this->user = $this->drupalCreateUser(
      array(
        'run security checks',
        'access security review list',
        'access administration pages',
        'administer site configuration',
      )
    );
    $this->drupalLogin($this->user);

    // Populate $checks.
    $this->checks = security_review_security_review_checks();

    // Clear cache.
    Checklist::clearCache();
  }

  /**
   * Tests whether the checks hasn't been run yet, then runs them and checks
   * that their lastRun value is not 0.
   */
  public function testRun() {
    foreach($this->checks as $check) {
      /** @var Check $check */
      $this->assertEqual(0, $check->lastRun(), $check->getTitle() . ' has not been run yet.');
    }
    SecurityReview::runChecklist();
    foreach($this->checks as $check) {
      /** @var Check $check */
      $this->assertNotEqual(0, $check->lastRun(), $check->getTitle() . ' has been run.');
    }
  }

  /**
   * Skips all checks then runs the checklist. No checks should be ran.
   */
  public function testSkippedRun() {
    foreach($this->checks as $check) {
      /** @var Check $check */
      $check->skip();
    }
    SecurityReview::runChecklist();
    foreach($this->checks as $check) {
      /** @var Check $check */
      $this->assertEqual(0, $check->lastRun(), $check->getTitle() . ' has not been run.');
    }
  }
}
