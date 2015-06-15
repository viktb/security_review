<?php

/**
 * @file
 * Contains \Drupal\security_review\Tests\ChecklistTest
 */

namespace Drupal\security_review\Tests;

use Drupal\security_review\Check;
use Drupal\security_review\Checklist;
use Drupal\simpletest\KernelTestBase;

/**
 * Contains test for Checklist.
 *
 * @group security_review
 */
class ChecklistTest extends KernelTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('security_review');

  /**
   * The security checks defined by Security Review.
   *
   * @var array
   */
  protected $checks;

  /**
   * Array of the IDs of $checks.
   *
   * @var array
   */
  protected $checkIDs;

  /**
   * Sets up the environment, populates the $checks variable.
   */
  protected function setUp() {
    parent::setUp();

    $this->checks = security_review_security_review_checks();
    Checklist::clearCache();
    $this->checkIDs = array();
    foreach ($this->checks as $check) {
      /** @var Check $check */
      $this->checkIDs[] = $check->id();
    }
  }

  /**
   * Tests whether getChecks() contains all the checks that
   * security_review_security_review_checks() returns.
   */
  public function testChecksProvided() {
    foreach (Checklist::getChecks() as $check) {
      /** @var Check $check */
      $this->assertTrue(in_array($check->id(), $this->checkIDs), $check->getTitle() . ' found.');
    }
  }

  /**
   * Tests whether checks returned by getEnabledChecks() are all enabled.
   */
  public function testEnabledChecks() {
    foreach (Checklist::getEnabledChecks() as $check) {
      /** @var Check $check */
      $this->assertFalse($check->isSkipped(), $check->getTitle() . ' is enabled.');

      // Disable check.
      $check->skip();
    }
    Checklist::clearCache();
    $this->assertEqual(count(Checklist::getEnabledChecks()), 0, 'Disabled all checks.');
  }

  /**
   * Tests the search functions of Checklist:
   *   getCheck().
   *   getCheckByIdentifier().
   */
  public function testCheckSearch() {
    foreach (Checklist::getChecks() as $check) {
      /** @var Check $check */
      // getCheck().
      $found = Checklist::getCheck($check->getMachineNamespace(), $check->getMachineTitle());
      $this->assertNotNull($found, 'Found a check.');
      $this->assertEqual($check->id(), $found->id(), 'Found ' . $check->getTitle() . '.');

      // getCheckByIdentifier().
      $found = Checklist::getCheckByIdentifier($check->id());
      $this->assertNotNull($found, 'Found a check.');
      $this->assertEqual($check->id(), $found->id(), 'Found ' . $check->getTitle() . '.');
    }
  }
}
