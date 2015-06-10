<?php

/**
 * @file
 * Contains \Drupal\security_review\Form\SettingsForm.
 */

namespace Drupal\security_review\Form;

use Drupal;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\security_review\Check;
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
  public function getFormID() {
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
      '#description' => t('Mark which roles are not trusted. The anonymous role defaults to untrusted. @message Read more about the idea behind trusted and untrusted roles on @DrupalScout. Most Security Review checks look for resources usable by untrusted roles.',
        array(
          '@message' => $message,
          '@DrupalScout' => Drupal::l('DrupalScout.com', Url::fromUri('http://drupalscout.com/knowledge-base/importance-user-roles-and-permissions-site-security'))
        )),
      '#options' => $options,
      '#default_value' => Security::untrustedRoles(),
    );

    // TODO: Report inactive namespaces. Old: security_review.pages.inc:146-161.

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
      /** @var Check $check */
      // Determine if check is being skipped.
      if ($check->isSkipped()) {
        $values[] = $check->getUniqueIdentifier();
        $label = t('!name <em>skipped by UID !uid on !date</em>', array(
          '!name' => $check->getTitle(),
          '!uid' => $check->skippedBy()->id(),
          '!date' => format_date($check->skippedOn())
        ));
      }
      else {
        $label = $check->getTitle();
      }
      $options[$check->getUniqueIdentifier()] = $label;
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
      /** @var Check $check */

      // Get the check's setting form.
      $checkForm = $check->settings()->buildForm();

      // If not empty, add it to the form.
      if (!empty($checkForm)) {
        // If this is the first non-empty setting page initialize the 'details'
        if (!isset($form['advanced']['check_specific'])) {
          $form['advanced']['check_specific'] = array(
            '#type' => 'details',
            '#title' => t('Check-specific settings'),
            '#open' => TRUE,
            '#tree' => TRUE,
          );
        }

        // Add the form.
        $subForm = &$form['advanced']['check_specific'][$check->getUniqueIdentifier()];

        $title = $check->getTitle();
        // If it's an external check, tell the user its namespace.
        if ($check->getMachineNamespace() != 'security_review') {
          $title .= ' <em>(' . $check->getNamespace() . ')</em>';
        }
        $subForm = array(
          '#type' => 'details',
          '#title' => t($title),
          '#open' => TRUE,
          '#tree' => TRUE,
          'form' => $checkForm
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
      $checkSpecificValues = $form_state->getValue('check_specific');
      foreach (Checklist::getChecks() as $check) {
        /** @var Check $check */
        $checkForm = &$form['advanced']['check_specific'][$check->getUniqueIdentifier()];
        if (isset($checkForm)) {
          $check->settings()->validateForm($checkForm, $checkSpecificValues[$check->getUniqueIdentifier()]);
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
      /** @var Check $check */
      if (in_array($check->getUniqueIdentifier(), $skipped)) {
        $check->skip();
      }
      else {
        $check->enable();
      }
    }

    // Save the check-specific settings.
    if (isset($form['advanced']['check_specific'])) {
      $checkSpecificValues = $form_state->getValue('check_specific');
      foreach ($checkSpecificValues as $checkIdentifier => $values) {
        // Get corresponding Check.
        $check = Checklist::getCheckByIdentifier($checkIdentifier);

        // Submit parameters.
        $checkForm = &$form['advanced']['check_specific'][$checkIdentifier]['form'];
        $checkFormValues = $checkSpecificValues[$checkIdentifier]['form'];

        // Submit.
        $check->settings()->submitForm($checkForm, $checkFormValues);
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
