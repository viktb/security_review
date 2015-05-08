<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\ChecklistController.
 */

namespace Drupal\security_review\Controller;

use Drupal\Core\Url;
use Drupal\security_review\SecurityReview;

/**
 * The class of the 'Run & Review' page's controller.
 */
class ChecklistController {
  /**
   * @return array
   *   The 'Run & Review' page.
   */
  public function index() {
    $run_form = array();
    $stored_results = array();

    // If the user has the required permissions, show the RunForm.
    if (\Drupal::currentUser()->hasPermission('run security checks')) {
      $run_form = \Drupal::formBuilder()
        ->getForm('Drupal\security_review\Form\RunForm');
    }

    $checks = array();
    if (!empty($checks)) {
      // TODO: If there are stored results, display them.
    }
    else {
      // If they haven't configured the site, prompt them to do so.
      if (!SecurityReview::configured()) {
        drupal_set_message(t('It appears this is your first time using the Security Review checklist. Before running the checklist please review the settings page at !link to set which roles are untrusted.',
          array('!link' => \Drupal::l('admin/reports/security-review/settings', Url::fromRoute('security_review.settings')))
        ), 'warning');
      }
    }

    return array($run_form, $stored_results);
  }
}
