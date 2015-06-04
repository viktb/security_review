<?php

/**
 * @file
 * Contains \Drupal\security_review\Form\SettingsForm.
 */

namespace Drupal\security_review\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\security_review\SecurityReview;
use Drupal\security_review\Security;

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
          '@DrupalScout' => \Drupal::l('DrupalScout.com', Url::fromUri('http://drupalscout.com/knowledge-base/importance-user-roles-and-permissions-site-security'))
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

    // TODO: Skipped checks. Old: security_review.pages.inc:177-197.

    // TODO: Iterate through checklist and get check-specific setting pages.
    $check_settings = array();

    if(!empty($check_settings)){
      $form['advanced']['check_settings'] = array(
        '#type' => 'details',
        '#title' => t('Check-specific settings'),
        '#open' => TRUE,
        '#tree' => TRUE,
      );

      // TODO: Include check-specific setting pages.
    }

    // Return the finished form.
    return parent::buildForm($form, $form_state);
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

    // TODO: Save the check-specific settings.

    // Commit the settings.
    $check_settings->save();

    // Show the default 'The configuration options have been saved.' message.
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['security_review.checks'];
  }
}
