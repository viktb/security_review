<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\BaseUrl.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\DrupalKernel;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\CheckSettings\BaseUrlSettings;
use Drupal\security_review\Security;

/**
 * Defines the Drupal Base URL check.
 */
class BaseUrl extends Check {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->settings = new BaseUrlSettings($this, $this->config);
  }

  /**
   * {@inheritdoc}
   */
  public function getNamespace() {
    return 'Security Review';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return 'Drupal base URL';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'base_url_set';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $settingsPath = Security::sitePath() . '/settings.php';
    $result = CheckResult::FAIL;
    $findings = array();

    if (file_exists($settingsPath)) {
      if ($this->settings()->get('method', 'token') === 'token') {
        $content = file_get_contents($settingsPath);
        $tokens = token_get_all($content);

        foreach ($tokens as $token) {
          if (is_array($token) && $token[0] === T_VARIABLE && $token[1] == '$base_url') {
            $result = CheckResult::SUCCESS;
            break;
          }
        }
      }
      else {
        include $settingsPath;

        if (isset($base_url)) {
          $result = CheckResult::SUCCESS;
        }
      }

      global $base_url;
      if ($result === CheckResult::FAIL) {
        $findings[] = t(
          'Your site is available at the following URL: !url.',
          array('!url' => $base_url));
        $findings[] = t(
          "If your site should only be available at that URL it is recommended that you set it as the \$base_url variable in the settings.php file at !file",
          array('!file' => $settingsPath)
        );
        $findings[] = t(
          "Or, if you are using Drupal's multi-site functionality then you should set the \$base_url variable for the appropriate settings.php for your site."
        );
      }

      return $this->createResult($result, $findings);
    }
    else {
      return $this->createResult(CheckResult::INFO, array(t("Couldn't determine settings.php's path.")));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = array();
    $paragraphs[] = "Setting Drupal's \$base_url in settings.php can help protect against attackers manipulating links to your site. For example, an attacker could exploit a missing \$base_url setting to carry out a phishing attack that may lead to the theft of your site's private user data.";

    return array(
      '#theme' => 'check_help',
      '#title' => 'Drupal base URL',
      '#paragraphs' => $paragraphs
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    if ($result->result() == CheckResult::SUCCESS) {
      return array();
    }

    return array(
      '#theme' => 'check_evaluation',
      '#paragraphs' => $result->findings(),
      '#items' => array()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($resultConst) {
    switch ($resultConst) {
      case CheckResult::SUCCESS:
        return t('Base URL is set in settings.php.');
      case CheckResult::FAIL:
        return t('Base URL is not set in settings.php.');
      default:
        return "Unexpected result.";
    }
  }

}
