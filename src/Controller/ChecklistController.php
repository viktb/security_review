<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\ChecklistController.
 */

namespace Drupal\security_review\Controller;

use Drupal\Core\Url;
use Drupal\security_review\Checklist;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
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
      if(SecurityReview::getLastRun() > 0){
        $run_form['run_form']['#open'] = FALSE;
      }
    }

    if (SecurityReview::getLastRun() > 0) {
      $checks = array();
      foreach(Checklist::getChecks() as $check){
        /** @var Check $check */
        $checkInfo = array(
          'result' => CheckResult::SKIPPED,
          'message' => 'The check hasn\'t been run yet.'
        );
        $lastResult = $check->lastResult();
        if($lastResult != null){
          $checkInfo['result'] = $check->isSkipped() ? CheckResult::SKIPPED : $lastResult->result();
          $checkInfo['message'] = $check->getMessage($lastResult->result());
        }
        $checkInfo['help_url'] = Url::fromRoute('security_review.help',
          array(
            'namespace' => $check->getMachineNamespace(),
            'check_name' => $check->getMachineTitle()
          )
        );
        // TODO: Skip buttons.

        $checks[] = $checkInfo;
      }
      $stored_results = array(
        '#theme' => 'results',
        '#date' => SecurityReview::getLastRun(),
        '#checks' => $checks
      );
    } else {
      // If they haven't configured the site, prompt them to do so.
      if (!SecurityReview::isConfigured()) {
        drupal_set_message(t('It appears this is your first time using the Security Review checklist. Before running the checklist please review the settings page at !link to set which roles are untrusted.',
          array('!link' => \Drupal::l('admin/reports/security-review/settings', Url::fromRoute('security_review.settings')))
        ), 'warning');
      }
    }

    return array($run_form, $stored_results);
  }
}
