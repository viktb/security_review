<?php

/**
 * @file
 * Contains \Drupal\security_review\Form\RunForm.
 */

namespace Drupal\security_review\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

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
    $form['run_form'] = array(
      '#type' => 'details',
      '#title' => t('Run'),
      '#description' => t('Click the button below to run the security checklist and review the results.'),
      // TODO: If there are stored check results, set #open to FALSE.
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
    if (\Drupal::currentUser()->hasPermission('run security checks')) {
      // TODO: Run the checks.
    } else {
      drupal_set_message('You don\'t have the permission to run security checks!', 'error');
    }
  }
}
