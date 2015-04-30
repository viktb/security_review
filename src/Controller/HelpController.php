<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\HelpController.
 */

namespace Drupal\security_review\Controller;

use Drupal\Core\Url;

class HelpController {
  public function index($namespace, $check_name) {
    // If no namespace is set, print the general help page
    if ($namespace === NULL) {
      return $this->generalHelp();
    }

    // Print check-specific help
    return $this->checkHelp($namespace, $check_name);
  }
  private function checkHelp($namespace, $check_name){
    // TODO: print check specific help
    return array(
      '#markup' => "ns: $namespace cn: $check_name"
    );
  }
  private function generalHelp(){
    $output = '';
    $output .= '<p>';
    $output .= t('You should take the security of your site very seriously.
      Fortunately, Drupal is fairly secure by default.
      The Security Review module automates many of the easy-to-make mistakes that render your site insecure, however it does not automatically make your site impenetrable.
      You should give care to what modules you install and how you configure your site and server.
      Be mindful of who visits your site and what features you expose for their use.');
    $output .= '</p>';
    $output .= '<p>';
    $output .= t('You can read more about securing your site in the <a href="!do">drupal.org handbooks</a> and on <a href="!cd">CrackingDrupal.com</a>.
      There are also additional modules you can install to secure or protect your site. Be aware though that the more modules you have running on your site the greater (usually) attack area you expose.',
      array('!do' => 'http://drupal.org/security/secure-configuration', '!cd' => 'http://crackingdrupal.com'));
    $output .= '</p>';
    $output .= '<p>' . \Drupal::l(t('Drupal.org Handbook: Introduction to security-related contrib modules'), Url::fromUri('http://drupal.org/node/382752')) . '</p>';
    $output .= '<h3>' . t('Check-specfic help') . '</h3>';
    $output .= '<p>' . t("Details and help on the security review checks. Checks are not always perfectly correct in their procedure and result. Refer to drupal.org handbook documentation if you are unsure how to make the recommended alterations to your configuration or consult the module's README.txt for support.") . '</p>';

    // TODO: iterate through checklists and print their own links

    return array(
      '#markup' => $output
    );
  }
}
