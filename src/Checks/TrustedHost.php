<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\TrustedHost.
 */

namespace Drupal\security_review\Checks;

use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\CheckSettings\TrustedHostSettings;

/**
 * Checks for base_url and trusted_host_patterns settings in settings.php.
 */
class TrustedHost extends Check {

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct();
    $this->settings = new TrustedHostSettings($this, $this->config);
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
    return 'Trusted host';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::FAIL;
    $base_url_set = FALSE;
    $trusted_host_patterns_set = FALSE;
    $findings = array();
    $settings_php = $this->security()->sitePath() . '/settings.php';

    if (!file_exists($settings_php)) {
      return $this->createResult(CheckResult::HIDE);
    }

    if ($this->settings()->get('method', 'token') === 'token') {
      // Use tokenization.
      $content = file_get_contents($settings_php);
      $tokens = token_get_all($content);

      $prev_settings_line = -1;
      foreach ($tokens as $token) {
        if (is_array($token)) {
          // Get information about the current token.
          $line = $token[2];
          $is_variable = $token[0] === T_VARIABLE;
          $is_string = $token[0] === T_CONSTANT_ENCAPSED_STRING;
          $is_settings = $is_variable ? $token[1] == '$settings' : FALSE;
          $is_base_url = $token[1] == '$base_url';
          $is_thp = trim($token[1], "\"'") == 'trusted_host_patterns';
          $is_after_settings = $line == $prev_settings_line;

          // Check for $base_url.
          if ($is_variable && $is_base_url) {
            $base_url_set = TRUE;
            $result = CheckResult::SUCCESS;
          }

          // Check for $settings['trusted_host_patterns'].
          if ($is_after_settings && $is_string && $is_thp) {
            $trusted_host_patterns_set = TRUE;
            $result = CheckResult::SUCCESS;
          }

          // If found both settings stop the review.
          if ($base_url_set && $trusted_host_patterns_set) {
            // Got everything we need.
            break;
          }

          // Store last $settings line.
          if ($is_settings) {
            $prev_settings_line = $line;
          }
        }
      }
    }
    else {
      // Use inclusion.
      include $settings_php;
      $base_url_set = isset($base_url);
      $trusted_host_patterns_set = isset($settings['trusted_host_patterns']);
    }

    if ($result === CheckResult::FAIL) {
      // Provide information if the check failed.
      global $base_url;
      $findings['base_url'] = $base_url;
      $findings['settings'] = $settings_php;
      $findings['base_url_set'] = $base_url_set;
      $findings['trusted_host_patterns_set'] = $trusted_host_patterns_set;
    }

    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = array();

    // @todo Help text.
    $paragraphs[] = $this->t('');

    return array(
      '#theme' => 'check_help',
      '#title' => $this->t('Drupal trusted hosts'),
      '#paragraphs' => $paragraphs,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    if ($result->result() !== CheckResult::FAIL) {
      return array();
    }

    $paragraphs = array();

    // @todo Evaluation text.
    $paragraphs[] = $this->t('');

    $items = array();

    return array(
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $items,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Either $base_url or trusted_host_patterns is set.');

      case CheckResult::FAIL:
        return $this->t('Neither $base_url nor trusted_host_patterns is set.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
