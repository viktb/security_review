<?php

/**
 * @file
 * Contains \Drupal\security_review\Tests\SecurityReviewTest
 */

namespace Drupal\security_review\Tests;

use Drupal\security_review\SecurityReview;
use Drupal\simpletest\KernelTestBase;

/**
 * Contains tests related to the SecurityReview class.
 *
 * @group security_review
 */
class SecurityReviewTest extends KernelTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('security_review');

  /**
   * Sets up the testing environment.
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(static::$modules);
  }

  /**
   * Tests the 'logging' setting.
   */
  public function testConfigLogging() {
    $this->assertTrue(SecurityReview::isLogging(), 'Logging enabled by default.');
    SecurityReview::setLogging(FALSE);
    $this->assertFalse(SecurityReview::isLogging(), 'Logging disabled.');
  }

  /**
   * Tests the 'configured' setting.
   */
  public function testConfigConfigured() {
    $this->assertFalse(SecurityReview::isConfigured(), 'Not configured by default.');
    SecurityReview::setConfigured(TRUE);
    $this->assertTrue(SecurityReview::isConfigured(), 'Set to configured.');
  }

  /**
   * Tests the 'untrusted_roles' setting.
   */
  public function testConfigUntrustedRoles() {
    $this->assertEqual(SecurityReview::getUntrustedRoles(), array(), 'untrusted_roles empty by default.');

    $roles = array(0, 1, 2, 3, 4);
    SecurityReview::setUntrustedRoles($roles);
    $this->assertEqual($roles, SecurityReview::getUntrustedRoles(), 'untrusted_roles set to test array.');
  }

  /**
   * Tests the 'last_run' setting.
   */
  public function testConfigLastRun() {
    $this->assertEqual(0, SecurityReview::getLastRun(), 'last_run is 0 by default.');
    $time = time();
    SecurityReview::setLastRun($time);
    $this->assertEqual($time, SecurityReview::getLastRun(), 'last_run set to now.');
  }
}
