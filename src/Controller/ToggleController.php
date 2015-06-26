<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\ToggleController.
 */

namespace Drupal\security_review\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\security_review\Checklist;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Responsible for handling the toggle links on the Run & Review page.
 */
class ToggleController extends ControllerBase {

  public function index($check_id) {
    // Determine access type.
    $ajax = Drupal::request()->query->get('js') == 1;

    // Validate token.
    $token = Drupal::request()->query->get('token');
    if (Drupal::csrfToken()->validate($token, $check_id)) {
      $check = Checklist::getCheckByIdentifier($check_id);
      if ($check != NULL) {
        if ($check->isSkipped()) {
          $check->enable();
        }
        else {
          $check->skip();
        }
      }
      // Output.
      if ($ajax) {
        return new JsonResponse(array(
          'skipped' => $check->isSkipped(),
          'toggle_text' => $check->isSkipped() ? 'Enable' : 'Skip',
          'toggle_href' => Url::fromRoute('security_review.toggle',
            array(
              'check_id' => $check->id()
            ),
            array(
              'query' => array(
                'token' => Drupal::csrfToken()
                  ->get($check->id()),
                'js' => 1
              )
            )
          )
        ));
      }
      else {
        // Set message.
        if ($check->isSkipped()) {
          drupal_set_message(t($check->getTitle() . ' check skipped.'));
        }
        else {
          drupal_set_message(t($check->getTitle() . ' check no longer skipped.'));
        }

        // Redirect back to Run & Review.
        return $this->redirect('security_review');
      }
    }

    // Go back to Run & Review if the access was wrong.
    return $this->redirect('security_review');
  }

}
