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
   * Tests the 'logging' setting.
   */
  public function testConfigLogging() {
    $this->assertTrue(SecurityReview::isLogging(), 'Logging enabled by default.');
    SecurityReview::setLogging(false);
    $this->assertFalse(SecurityReview::isLogging(), 'Logging disabled.');
  }

  /**
   * Tests the 'configured' setting.
   */
  public function testConfigConfigured() {
    $this->assertFalse(SecurityReview::isConfigured(), 'Not configured by default.');
    SecurityReview::setConfigured(true);
    $this->assertTrue(SecurityReview::isConfigured(), 'Set to configured.');
  }

  /**
   * Tests the 'untrusted_roles' setting.
   */
  public function testConfigUntrustedRoles() {
    SecurityReview::setUntrustedRoles(array());
    $this->assertEqual(SecurityReview::getUntrustedRoles(), array(), 'untrusted_roles emptied.');

    $roles = array(0, 1, 2, 3, 4);
    SecurityReview::setUntrustedRoles($roles);
    $this->assertEqual($roles, SecurityReview::getUntrustedRoles(), 'untrusted_roles set to test array.');
  }

  /**
   * Tests the 'last_run' setting.
   */
  public function testConfigLastRun() {
    SecurityReview::setLastRun(0);
    $this->assertEqual(0, SecurityReview::getLastRun(), 'last_run set to 0.');
    $time = time();
    SecurityReview::setLastRun($time);
    $this->assertEqual($time, SecurityReview::getLastRun(), 'last_run set to now.');
  }
}
