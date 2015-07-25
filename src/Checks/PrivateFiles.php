<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\PrivateFiles.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;

/**
 * Checks whether the private files' directory is under the web root.
 */
class PrivateFiles extends Check {

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
    return 'Private files';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $file_directory_path = PrivateStream::basePath();
    if (empty($file_directory_path)) {
      // Private files feature is not enabled.
      $result = CheckResult::INFO;
    }
    elseif (strpos(realpath($file_directory_path), DRUPAL_ROOT) === 0) {
      // Path begins at root.
      $result = CheckResult::FAIL;
    }
    else {
      // The private files directory is placed correctly.
      $result = CheckResult::SUCCESS;
    }
    return $this->createResult($result, array('path' => $file_directory_path));
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = array();
    $paragraphs[] = "If you have Drupal's private files feature enabled you should move the files directory outside of the web server's document root. Drupal will secure access to files that it renders the link to, but if a user knows the actual system path they can circumvent Drupal's private files feature. You can protect against this by specifying a files directory outside of the webserver root.";

    return array(
      '#theme' => 'check_help',
      '#title' => 'Private files',
      '#paragraphs' => $paragraphs,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    if ($result->result() != CheckResult::FAIL) {
      return array();
    }

    $paragraphs = array();
    $paragraphs[] = $this->t('Your files directory is not outside of the server root.');
    $paragraphs[] = $this->l(
      $this->t('Edit the files directory path.'),
      Url::fromRoute('system.file_system_settings')
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
  public function evaluatePlain(CheckResult $result) {
    if ($result->result() != CheckResult::FAIL) {
      return '';
    }

    return $this->t('Private files directory: !path', array('!path' => $result->findings()['path']));
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return 'Private files directory is outside the web server root.';

      case CheckResult::FAIL:
        return 'Private files is enabled but the specified directory is not secure outside the web server root.';

      case CheckResult::INFO:
        return 'Private files feature is not enabled.';

      default:
        return 'Unexpected result.';
    }
  }

}
