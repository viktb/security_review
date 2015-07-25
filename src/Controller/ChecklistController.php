<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\ChecklistController.
 */

namespace Drupal\security_review\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\security_review\Checklist;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityReview;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class of the 'Run & Review' page's controller.
 */
class ChecklistController extends ControllerBase {

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   */
  protected $csrfToken;

  /**
   * Constructs a ChecklistController.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfToken
   *   The CSRF Token generator.
   */
  public function __construct(CsrfTokenGenerator $csrfToken) {
    $this->csrfToken = $csrfToken;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('csrf_token')
    );
  }

  /**
   * Creates the Run & Review page.
   *
   * @return array
   *   The 'Run & Review' page's render array.
   */
  public function index() {
    $run_form = array();

    // If the user has the required permissions, show the RunForm.
    if ($this->currentUser()->hasPermission('run security checks')) {
      // Get the Run form.
      $run_form = $this->formBuilder()
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
        drupal_set_message($this->t('It appears this is your first time using the Security Review checklist. Before running the checklist please review the settings page at !link to set which roles are untrusted.',
          array('!link' => $this->l('admin/reports/security-review/settings', Url::fromRoute('security_review.settings')))
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
      $check_info = array(
        'result' => CheckResult::SKIPPED,
        'message' => $this->t(
          'The check "!name" hasn\'t been run yet.',
          array('!name' => $check->getTitle())
        ),
        'skipped' => $check->isSkipped(),
      );

      // Get last result.
      $last_result = $check->lastResult();
      if ($last_result != NULL) {
        if ($last_result->result() == CheckResult::HIDE) {
          continue;
        }
        $check_info['result'] = $last_result->result();
        $check_info['message'] = $last_result->resultMessage();
      }

      // Determine help link.
      $check_info['help_link'] = $this->l(
        'Details',
        Url::fromRoute(
          'security_review.help',
          array(
            'namespace' => $check->getMachineNamespace(),
            'title' => $check->getMachineTitle(),
          )
        )
      );

      // Add toggle button.
      $toggle_text = $check->isSkipped() ? 'Enable' : 'Skip';
      $check_info['toggle_link'] = $this->l($toggle_text,
        Url::fromRoute(
          'security_review.toggle',
          array('check_id' => $check->id()),
          array(
            'query' => array('token' => $this->csrfToken->get($check->id())),
          )
        )
      );

      // Add to array of completed checks.
      $checks[] = $check_info;
    }

    return array(
      '#theme' => 'run_and_review',
      '#date' => SecurityReview::getLastRun(),
      '#checks' => $checks,
      '#attached' => array(
        'library' => array('security_review/run_and_review'),
      ),
    );
  }

}
