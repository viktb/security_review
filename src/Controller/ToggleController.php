<?php

namespace Drupal\security_review\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\security_review\Checklist;

class ToggleController extends ControllerBase {
  public function index($js, $check_id){
    // Determine access type.
    $ajax = $js != 'nojs';

    // Validate token.
    $token = \Drupal::request()->query->get('token');
    if(\Drupal::csrfToken()->validate($token, $check_id)){
      $check = Checklist::getCheckByIdentifier($check_id);
      if($check != null){
        if($check->isSkipped()){
          $check->enable();
        }else{
          $check->skip();
        }
      }
    }

    // Output.
    if($ajax) {
      // TODO: Print json output.
    }else{
      // Redirect back to Run & Review.
      return $this->redirect('security_review');
    }
  }
}
