<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\ChecklistController.
 */

namespace Drupal\security_review\Controller;

class ChecklistController {
  public function index() {
    return array(
      '#markup' => t('Hello World!')
    );
  }
}
