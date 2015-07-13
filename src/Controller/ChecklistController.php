<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\ChecklistController.
 */

namespace Drupal\security_review\Controller;

use Drupal;
use Drupal\Core\Url;
use Drupal\security_review\Checklist;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityReview;

/**
 * The class of the 'Run & Review' page's controller.
 */
class ChecklistController {

  /**
   * Creates the Run & Review page.
   *
   * @return array
   *   The 'Run & Review' page's render array.
   */
  public function index() {
    $run_form = array();

    // If the user has the required permissions, show the RunForm.
    if (Drupal::currentUser()->hasPermission('run security checks')) {
      // Get the Run form.
      $run_form = Drupal::formBuilder()
        ->getForm('Drupal\security_review\Form\RunForm');

      // Close the Run form if there are results.
      if (SecurityReview::getLastRun() > 0) {
        $run_form['run_form']['#open'] = FALSE;
      }
    }

    // Print the results if any.
    if (SecurityReview::getLastRun() <= 0) {
      // If they haven't configured the site, prompt them to do so.
      if (!SecurityReview::isConfigured()) {
        drupal_set_message(t('It appears this is your first time using the Security Review checklist. Before running the checklist please review the settings page at !link to set which roles are untrusted.',
          array('!link' => Drupal::l('admin/reports/security-review/settings', Url::fromRoute('security_review.settings')))
        ), 'warning');
      }
    }

    return array($run_form, $this->results());
  }

  /**
   * Creates the results' table.
   *
   * @return array
   *   The render array for the result table.
   */
  public function results() {
    // If there are no results return.
    if (SecurityReview::getLastRun() <= 0) {
      return array();
    }

    $checks = array();
    foreach (Checklist::getChecks() as $check) {
      // Initialize with defaults.
      $checkInfo = array(
        'result' => CheckResult::SKIPPED,
        'message' => t(
          'The check "!name" hasn\'t been run yet.',
          array('!name' => $check->getTitle())
        ),
        'skipped' => $check->isSkipped()
      );

      // Get last result.
      $lastResult = $check->lastResult();
      if ($lastResult != NULL) {
        if ($lastResult->result() == CheckResult::HIDE) {
          continue;
        }
        $checkInfo['result'] = $lastResult->result();
        $checkInfo['message'] = $lastResult->resultMessage();
      }

      // Determine help link.
      $checkInfo['help_link'] = Drupal::l(
        'Details',
        Url::fromRoute(
          'security_review.help',
          array(
            'namespace' => $check->getMachineNamespace(),
            'title' => $check->getMachineTitle()
          )
        )
      );

      // Add toggle button.
      $toggle_text = $check->isSkipped() ? 'Enable' : 'Skip';
      $checkInfo['toggle_link'] = Drupal::l($toggle_text, Url::fromRoute('security_review.toggle',
        array(
          'check_id' => $check->id()
        ),
        array(
          'query' => array('token' => Drupal::csrfToken()->get($check->id()))
        )
      ));

      // Add to array of completed checks.
      $checks[] = $checkInfo;
    }

    return array(
      '#theme' => 'run_and_review',
      '#date' => SecurityReview::getLastRun(),
      '#checks' => $checks,
      '#attached' => array(
        'library' => array('security_review/run_and_review'),
      )
    );
  }

}
