<?php

/**
 * @file
 * Contains \Drupal\security_review\Controller\HelpController.
 */

namespace Drupal\security_review\Controller;

use Drupal;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\Checklist;
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
   * @param $title
   *   The name of the check.
   * @return array
   *   The requested help page's contents.
   */
  public function index($namespace, $title) {
    // If no namespace is set, print the general help page
    if ($namespace === NULL) {
      return $this->generalHelp();
    }

    // Print check-specific help
    return $this->checkHelp($namespace, $title);
  }

  /**
   * Returns the general help page.
   *
   * @return array
   *   The general help page's content.
   */
  private function generalHelp() {
    $paragraphs = array();

    $paragraphs[] = t('You should take the security of your site very seriously.
      Fortunately, Drupal is fairly secure by default.
      The Security Review module automates many of the easy-to-make mistakes that render your site insecure, however it does not automatically make your site impenetrable.
      You should give care to what modules you install and how you configure your site and server.
      Be mindful of who visits your site and what features you expose for their use.');
    $paragraphs[] = t('You can read more about securing your site in the !drupal_org and on !cracking_drupal.
      There are also additional modules you can install to secure or protect your site. Be aware though that the more modules you have running on your site the greater (usually) attack area you expose.',
      array(
        '!drupal_org' => Drupal::l('drupal.org handbooks', Url::fromUri('http://drupal.org/security/secure-configuration')),
        '!cracking_drupal' => Drupal::l('CrackingDrupal.com', Url::fromUri('http://crackingdrupal.com')),
      ));
    $paragraphs[] = Drupal::l(t('Drupal.org Handbook: Introduction to security-related contrib modules'), Url::fromUri('http://drupal.org/node/382752'));

    $checks = array();
    foreach (Checklist::getChecks() as $check) {
      /** @var Check $check */

      // Get the namespace array's reference.
      $check_namespace = &$checks[$check->getMachineNamespace()];

      // Set up the namespace array if not set.
      if (!isset($check_namespace)) {
        $check_namespace['namespace'] = $check->getNamespace();
        $check_namespace['check_links'] = array();
      }

      // Add the link pointing to the check-specific help.
      $check_namespace['check_links'][] = Drupal::l(
        t($check->getTitle()),
        Url::fromRoute('security_review.help', array(
          'namespace' => $check->getMachineNamespace(),
          'title' => $check->getMachineTitle()
        ))
      );
    }

    return array(
      '#theme' => 'general_help',
      '#paragraphs' => $paragraphs,
      '#checks' => $checks
    );
  }

  /**
   * Returns a check-specific help page.
   *
   * @param $namespace
   *   The namespace of the check.
   * @param $title
   *   The name of the check.
   *
   * @return array
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  private function checkHelp($namespace, $title) {
    // Get the requested check.
    $check = Checklist::getCheck($namespace, $title);

    // If the check doesn't exist, throw 404.
    if ($check == NULL) {
      throw new NotFoundHttpException();
    }

    // Print the help page.
    $output = array();
    $output[] = array(
      '#type' => 'markup',
      '#markup' => '<h3>' . t($check->getTitle()) . '</h3>'
    );
    $output[] = $check->help();

    // Evaluate last result, if any.
    $lastResult = $check->lastResult();
    if ($lastResult instanceof CheckResult) {
      // Separator.
      $output[] = array(
        '#type' => 'markup',
        '#markup' => '<div />'
      );

      // Evaluation page.
      $output[] = $check->evaluate($lastResult);
    }

    // Return the completed page.
    return $output;
  }
}
