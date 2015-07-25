<?php

/**
 * @file
 * Contains \Drupal\security_review\Form\RunForm.
 */

namespace Drupal\security_review\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\security_review\Checklist;

/**
 * Provides implementation for the Run form.
 */
class RunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'security-review-run';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->currentUser()->hasPermission('run security checks')) {
      return array();
    }

    $form['run_form'] = array(
      '#type' => 'details',
      '#title' => $this->t('Run'),
      '#description' => $this->t('Click the button below to run the security checklist and review the results.') . '<br />',
      '#open' => TRUE,
    );

    $form['run_form']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Run checklist'),
    );

    // Return the finished form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $batch = array(
      'operations' => array(),
      'finished' => '_security_review_batch_run_finished',
      'title' => $this->t('Performing Security Review'),
      'init_message' => $this->t('Security Review is starting.'),
      'progress_message' => $this->t('Progress @current out of @total.'),
      'error_message' => $this->t('An error occurred. Rerun the process or consult the logs.'),
    );

    foreach (Checklist::getEnabledChecks() as $check) {
      $batch['operations'][] = array(
        '_security_review_batch_run_op',
        array($check),
      );
    }

    batch_set($batch);
  }

}
