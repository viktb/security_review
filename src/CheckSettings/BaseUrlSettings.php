<?php

namespace Drupal\security_review\CheckSettings;

use Drupal\security_review\CheckSettings;

class BaseUrlSettings extends CheckSettings{
  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    $form = array();
    $form['method'] = array(
      '#type' => 'radios',
      '#title' => t('Base URL check method'),
      '#description' => t('Detecting the $base_url in settings.php can be done via PHP tokenization (recommend) or including the file. Note that if you have custom functionality in your settings.php it will be executed if the file is included. Including the file can result in a more accurate $base_url check if you wrap the setting in conditional statements.'),
      '#options' => array(
        'token' => t('Tokenize settings.php (recommended)'),
        'include' => t('Include settings.php'),
      ),
      '#default_value' => $this->get('method', 'token'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, $values) {
    $this->set('method', $values['method']);
  }
}
