<?php

/**
 * @file
 * Contains \Drupal\security_review\Form\SettingsForm.
 */

namespace Drupal\security_review\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;

/**
 * File test form class.
 *
 * @ingroup email_example
 */
class SettingsForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'security-review-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save configuration'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message(t('The configuration options have been saved.'), 'status');
  }
}
