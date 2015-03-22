<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\HelpController.
 */

namespace Drupal\security_review\Controller;

class HelpController {
  public function index($check_name){
    if($check_name === null){
      return array(
        '#markup' => t("This is the general help page")
      );
    }
    return array(
      '#markup' => t("This is the help page for $check_name.")
    );
  }
}
