<?php

/**
 * @file
 * Contains \Drupal\security_review\Form\SettingsForm.
 */

namespace Drupal\security_review\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\security_review\Checklist;
use Drupal\security_review\Security;
use Drupal\security_review\SecurityReview;

/**
 * Settings page for Security Review.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'security-review-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the list of checks.
    $checks = Checklist::getChecks();

    // Get the user roles.
    $roles = user_roles();
    $options = array();
    foreach ($roles as $rid => $role) {
      $options[$rid] = SafeMarkup::checkPlain($role->label());
    }

    // Notify the user if anonymous users can create accounts.
    $message = '';
    if (in_array(AccountInterface::AUTHENTICATED_ROLE, Security::defaultUntrustedRoles())) {
      $message = 'You have allowed anonymous users to create accounts without approval so the authenticated role defaults to untrusted.';
    }

    // Show the untrusted roles form element.
    $form['untrusted_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Untrusted roles'),
      '#description' => t('Define which roles are for less trusted users. The anonymous role defaults to untrusted. @message Most Security Review checks look for resources usable by untrusted roles.',
        array(
          '@message' => $message,
        )),
      '#options' => $options,
      '#default_value' => Security::untrustedRoles(),
    );

    $form['advanced'] = array(
      '#type' => 'details',
      '#title' => t('Advanced'),
      '#open' => TRUE,
    );

    // Show the logging setting.
    $form['advanced']['logging'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log checklist results and skips'),
      '#description' => t('The result of each check and skip can be logged to watchdog for tracking.'),
      '#default_value' => SecurityReview::isLogging(),
    );

    // Skipped checks.
    $values = array();
    $options = array();
    foreach ($checks as $check) {
      // Determine if check is being skipped.
      if ($check->isSkipped()) {
        $values[] = $check->id();
        $label = t('!name <em>skipped by UID !uid on !date</em>', array(
          '!name' => $check->getTitle(),
          '!uid' => $check->skippedBy()->id(),
          '!date' => format_date($check->skippedOn()),
        ));
      }
      else {
        $label = $check->getTitle();
      }
      $options[$check->id()] = $label;
    }
    $form['advanced']['skip'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Checks to skip'),
      '#description' => t('Skip running certain checks. This can also be set on the <em>Run & review</em> page. It is recommended that you do not skip any checks unless you know the result is wrong or the process times out while running.'),
      '#options' => $options,
      '#default_value' => $values,
    );

    // Iterate through checklist and get check-specific setting pages.
    foreach ($checks as $check) {
      // Get the check's setting form.
      $check_form = $check->settings()->buildForm();

      // If not empty, add it to the form.
      if (!empty($check_form)) {
        // If this is the first non-empty setting page initialize the 'details'
        if (!isset($form['advanced']['check_specific'])) {
          $form['advanced']['check_specific'] = array(
            '#type' => 'details',
            '#title' => t('Check-specific settings'),
            '#open' => FALSE,
            '#tree' => TRUE,
          );
        }

        // Add the form.
        $sub_form = &$form['advanced']['check_specific'][$check->id()];

        $title = $check->getTitle();
        // If it's an external check, tell the user its namespace.
        if ($check->getMachineNamespace() != 'security_review') {
          $title .= t('<em>%namespace</em>', array(
            '%namespace' => $check->getNamespace(),
          ));
        }
        $sub_form = array(
          '#type' => 'details',
          '#title' => $title,
          '#open' => TRUE,
          '#tree' => TRUE,
          'form' => $check_form,
        );
      }
    }

    // Return the finished form.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (isset($form['advanced']['check_specific'])) {
      $check_specific_values = $form_state->getValue('check_specific');
      foreach (Checklist::getChecks() as $check) {
        $check_form = &$form['advanced']['check_specific'][$check->id()];
        if (isset($check_form)) {
          $check->settings()
            ->validateForm($check_form, $check_specific_values[$check->id()]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Frequently used configuration items.
    $check_settings = $this->config('security_review.checks');

    // Save that the module has been configured.
    SecurityReview::setConfigured(TRUE);

    // Save the new untrusted roles.
    $untrusted_roles = array_keys(array_filter($form_state->getValue('untrusted_roles')));
    SecurityReview::setUntrustedRoles($untrusted_roles);

    // Save the new logging setting.
    $logging = $form_state->getValue('logging') == 1;
    SecurityReview::setLogging($logging);

    // Skip selected checks.
    $skipped = array_keys(array_filter($form_state->getValue('skip')));
    foreach (Checklist::getChecks() as $check) {
      if (in_array($check->id(), $skipped)) {
        $check->skip();
      }
      else {
        $check->enable();
      }
    }

    // Save the check-specific settings.
    if (isset($form['advanced']['check_specific'])) {
      $check_specific_values = $form_state->getValue('check_specific');
      foreach ($check_specific_values as $id => $values) {
        // Get corresponding Check.
        $check = Checklist::getCheckById($id);

        // Submit parameters.
        $check_form = &$form['advanced']['check_specific'][$id]['form'];
        $check_form_values = $check_specific_values[$id]['form'];

        // Submit.
        $check->settings()->submitForm($check_form, $check_form_values);
      }
    }

    // Commit the settings.
    $check_settings->save();

    // Finish submitting the form.
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['security_review.checks'];
  }

}
