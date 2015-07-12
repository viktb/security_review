<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\InputFormats.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\Security;

/**
 * Check for formats that either do not have HTML filter that can be used by
 * untrusted users, or if they do check if unsafe tags are allowed.
 */
class InputFormats extends Check {

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
    return 'Text formats';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'input_formats';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = array();

    $formats = filter_formats();
    $untrusted_roles = Security::untrustedRoles();
    $unsafe_tags = Security::unsafeTags();

    foreach ($formats as $format) {
      $format_roles = array_keys(filter_get_roles_by_format($format));
      $intersect = array_intersect($format_roles, $untrusted_roles);

      if (!empty($intersect)) {
        // Untrusted users can use this format.
        // Check format for enabled HTML filter.
        if ($format->filters()->has('filter_html') &&
          $format->filters('filter_html')->getConfiguration()['status']
        ) {
          $filter = $format->filters('filter_html');

          // Check for unsafe tags in allowed tags.
          $allowed_tags = array_keys($filter->getHTMLRestrictions()['allowed']);
          foreach (array_intersect($allowed_tags, $unsafe_tags) as $tag) {
            // Found an unsafe tag.
            $findings['tags'][$format->id()] = $tag;
          }
        }
        elseif (!$format->filters()->has('filter_html_escape') ||
          !$format->filters('filter_html_escape')->getConfiguration()['status']
        ) {
          // Format is usable by untrusted users but does not contain the HTML Filter or the HTML escape.
          $findings['formats'][$format->id()] = $format->label();
        }
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }
    return $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function help() {
    $paragraphs = array();
    $paragraphs[] = "Certain HTML tags can allow an attacker to take control of your site. Drupal's input format system makes use of a set filters to run on incoming text. The 'HTML Filter' strips out harmful tags and Javascript events and should be used on all formats accessible by untrusted users.";
    $paragraphs[] = Drupal::l(
      t("Read more about Drupal's input formats in the handbooks."),
      Url::fromUri('http://drupal.org/node/224921')
    );

    return array(
      '#theme' => 'check_help',
      '#title' => 'Allowed HTML tags in text formats',
      '#paragraphs' => $paragraphs
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $output = array();

    if (!empty($result->findings()['tags'])) {
      $paragraphs = array();
      $paragraphs[] = Drupal::l(
        t('Review your text formats.'),
        Url::fromRoute('filter.admin_overview')
      );
      $paragraphs[] = t('It is recommended you remove the following tags from roles accessible by untrusted users.');
      $output[] = array(
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $result->findings()['tags']
      );
    }

    if (!empty($result->findings()['formats'])) {
      $paragraphs = array();
      $paragraphs[] = t('The following formats are usable by untrusted roles and do not filter or escape allowed HTML tags.');
      $output[] = array(
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $result->findings()['formats']
      );
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $output = '';

    if (!empty($result->findings()['tags'])) {
      $output .= t('Tags') . "\n";
      foreach ($result->findings()['tags'] as $tag) {
        $output .= "\t$tag\n";
      }
    }

    if (!empty($result->findings()['formats'])) {
      $output .= t('Formats') . "\n";
      foreach ($result->findings()['formats'] as $format) {
        $output .= "\t$format\n";
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($resultConst) {
    switch ($resultConst) {
      case CheckResult::SUCCESS:
        return 'Untrusted users are not allowed to input dangerous HTML tags.';
      case CheckResult::FAIL:
        return 'Untrusted users are allowed to input dangerous HTML tags.';
      default:
        return 'Unexpected result.';
    }
  }

}
