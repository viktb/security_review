<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\BaseUrl.
 */

namespace Drupal\security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\CheckSettings\BaseUrlSettings;

/**
 * Checks whether $base_url is set in settings.php.
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
    $settings_php = $this->security()->sitePath() . '/settings.php';
    $result = CheckResult::FAIL;
    $findings = array();

    if (!file_exists($settings_php)) {
      return $this->createResult(CheckResult::INFO, array(t("Couldn't determine settings.php's path.")));
    }

    if ($this->settings()->get('method', 'token') === 'token') {
      // Determine the $base_url setting using tokenization.
      $content = file_get_contents($settings_php);
      $tokens = token_get_all($content);

      foreach ($tokens as $token) {
        if (is_array($token) && $token[0] === T_VARIABLE && $token[1] == '$base_url') {
          $result = CheckResult::SUCCESS;
          break;
        }
      }
    }
    else {
      // Determine the $base_url setting by including settings.php.
      include $settings_php;
      if (isset($base_url)) {
        $result = CheckResult::SUCCESS;
      }
    }

    if ($result === CheckResult::FAIL) {
      // Provide information if the check failed.
      global $base_url;
      $findings['base_url'] = $base_url;
      $findings['settings'] = $settings_php;
    }

    return $this->createResult($result, $findings);
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
      '#paragraphs' => $paragraphs,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    if ($result->result() == CheckResult::SUCCESS) {
      return array();
    }

    $findings = $result->findings();
    $paragraphs = array();
    $paragraphs[] =$this->t(
      'Your site is available at the following URL: !url.',
      array('!url' => $findings['base_url']));
    $paragraphs[] =$this->t(
      "If your site should only be available at that URL it is recommended that you set it as the \$base_url variable in the settings.php file at !file",
      array('!file' => $findings['settings'])
    );
    $paragraphs[] =$this->t(
      "Or, if you are using Drupal's multi-site functionality then you should set the \$base_url variable for the appropriate settings.php for your site."
    );

    return array(
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return$this->t('Base URL is set in settings.php.');

      case CheckResult::FAIL:
        return$this->t('Base URL is not set in settings.php.');

      default:
        return "Unexpected result.";
    }
  }

}
