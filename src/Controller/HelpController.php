<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\HelpController.
 */

namespace Drupal\security_review\Controller;

use Drupal\Core\Url;
use Drupal\security_review\Checklist;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The class of the Help pages' controller.
 */
class HelpController {
  /**
   * Serves as an entry point for the help pages.
   *
   * @param $namespace
   *   The namespace of the check (null if general page).
   * @param $check_name
   *   The name of the check.
   * @return array
   *   The requested help page's contents.
   */
  public function index($namespace, $check_name) {
    // If no namespace is set, print the general help page
    if ($namespace === NULL) {
      return $this->generalHelp();
    }

    // Print check-specific help
    return $this->checkHelp($namespace, $check_name);
  }

  /**
   * Returns a check-specific help page.
   *
   * @param $namespace
   *   The namespace of the check.
   * @param $check_name
   *   The name of the check.
   *
   * @return array
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  private function checkHelp($namespace, $check_name) {
    // Get the requested check.
    $check = Checklist::getCheck($namespace, $check_name);

    // If the check doesn't exist, throw 404.
    if($check == null){
      throw new NotFoundHttpException();
    }

    // Print the help page.
    $output = '';
    $output .= '<div>';
    $output .= $check->help();
    $output .= '</div>';

    // Evaluate last result, if any.
    $lastResult = $check->lastResult();
    if($lastResult instanceof CheckResult){
      $output .= '<div>';
      $output .= $check->evaluate($lastResult);
      $output .= '</div>';
    }

    // Return the completed page.
    return array(
      '#type' => 'markup',
      '#markup' => $output
    );
  }

  /**
   * Returns the general help page.
   *
   * @return array
   *   The general help page's content.
   */
  private function generalHelp() {
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
      array(
        '!do' => 'http://drupal.org/security/secure-configuration',
        '!cd' => 'http://crackingdrupal.com'
      ));
    $output .= '</p>';
    $output .= '<p>' . \Drupal::l(t('Drupal.org Handbook: Introduction to security-related contrib modules'), Url::fromUri('http://drupal.org/node/382752')) . '</p>';
    $output .= '<h3>' . t('Check-specfic help') . '</h3>';
    $output .= '<p>' . t("Details and help on the security review checks. Checks are not always perfectly correct in their procedure and result. Refer to drupal.org handbook documentation if you are unsure how to make the recommended alterations to your configuration or consult the module's README.txt for support.") . '</p>';

    // Iterate through checklists and print their links.
    $checks = Checklist::groupChecksByNamespace(Checklist::getChecks());
    foreach($checks as $checkNamespace){
      $output .= '<h4>' . t($checkNamespace[0]->getNamespace()) . '</h4>';
      $output .= '<div class="details-wrapper"><ul>';
      foreach($checkNamespace as $check){
        /** @var Check $check */
        $url = Url::fromRoute('security_review.help', array(
          'namespace' => $check->getMachineNamespace(),
          'check_name' => $check->getMachineTitle(),
        ));
        $link = \Drupal::l(t($check->getTitle()), $url);
        $output .= "<li>$link</li>";
      }
      $output .= '</ul></div>';
    }

    return array(
      '#type' => 'markup',
      '#markup' => $output
    );
  }
}
