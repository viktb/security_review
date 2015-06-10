<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\ToggleController.
 */

namespace Drupal\security_review\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\security_review\Checklist;

/**
 * Responsible for handling the toggle links on the Run & Review page.
 */
class ToggleController extends ControllerBase {
  public function index($js, $check_id) {
    // Determine access type.
    $ajax = $js != 'nojs';

    // Validate token.
    $token = Drupal::request()->query->get('token');
    if (Drupal::csrfToken()->validate($token, $check_id)) {
      $check = Checklist::getCheckByIdentifier($check_id);
      if ($check != NULL) {
        if ($check->isSkipped()) {
          $check->enable();
          drupal_set_message(t($check->getTitle() . ' check no longer skipped.'));
        }
        else {
          $check->skip();
          drupal_set_message(t($check->getTitle() . ' check skipped.'));
        }
      }
    }

    // Output.
    if ($ajax) {
      // TODO: Print json output.
    }
    else {
      // Redirect back to Run & Review.
      return $this->redirect('security_review');
    }
  }
}
