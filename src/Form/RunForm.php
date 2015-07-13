<?php

/**
 * @file
 * Contains \Drupal\security_review\Form\RunForm.
 */

namespace Drupal\security_review\Form;

use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\security_review\SecurityReview;

/**
 * 'Run' form class.
 */
class RunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'security-review-run';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!Drupal::currentUser()->hasPermission('run security checks')) {
      return array();
    }

    $form['run_form'] = array(
      '#type' => 'details',
      '#title' => t('Run'),
      '#description' => t('Click the button below to run the security checklist and review the results.<br />'),
      '#open' => TRUE,
    );

    $form['run_form']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Run checklist'),
    );

    // Return the finished form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    SecurityReview::runChecklist();
  }

}
