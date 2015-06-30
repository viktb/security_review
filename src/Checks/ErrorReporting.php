<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\BaseUrl.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Defines a security check that checks the error reporting setting.
 */
class ErrorReporting extends Check {

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
    return 'Error reporting';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $error_level = Drupal::config('system.logging')->get('error_level');
    if (is_null($error_level) || $error_level != 'hide') {
      $result = CheckResult::FAIL;
    }
    else {
      $result = CheckResult::SUCCESS;
    }

    return $this->createResult($result, array('level' => $error_level));
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = array();
    $paragraphs[] = 'As a form of hardening your site you should avoid information disclosure. Drupal by default prints errors to the screen and writes them to the log. Error messages disclose the full path to the file where the error occured.';

    return array(
      '#theme' => 'check_help',
      '#title' => 'Error reporting',
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

    $paragraphs = array();
    $paragraphs[] = t('You have error reporting set to both the screen and the log.');
    $paragraphs[] = Drupal::l(
      'Alter error reporting settings.',
      Url::fromRoute('system.logging_settings')
    );

    return array(
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => array()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    if ($result->result() == CheckResult::SUCCESS) {
      return '';
    }

    if (isset($result->findings()['level'])) {
      return t('error level: !level', array(
        '!level' => $result->findings()['level']
      ));
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($resultConst) {
    switch ($resultConst) {
      case CheckResult::SUCCESS:
        return 'Error reporting set to log only.';
      case CheckResult::FAIL:
        return 'Errors are written to the screen.';
      default:
        return 'Unexpected result.';
    }
  }

}
