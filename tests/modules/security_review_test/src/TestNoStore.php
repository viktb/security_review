<?php

/**
 * @file
 * Contains \Drupal\security_review_test\TestNoStore.
 */

namespace Drupal\security_review_test;

/**
 * A test security check for testing extensibility.
 */
class TestNoStore extends Test {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Test without storing findings';
  }

  /**
   * {@inheritdoc}
   */
  public function storesFindings() {
    return FALSE;
  }

}
