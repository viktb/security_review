<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\ViewsAccess.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\views\Entity\View;

/**
 * Checks for Views that do not check access.
 */
class ViewsAccess extends Check {

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
    return 'Views access';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // If views is not enabled return with INFO.
    if (!$this->moduleHandler()->moduleExists('views')) {
      return $this->createResult(CheckResult::INFO);
    }

    $result = CheckResult::SUCCESS;
    $findings = array();

    $views = entity_load_multiple('view');
    /** @var View[] $views */

    foreach ($views as $view) {
      if ($view->status()) {
        foreach ($view->get('display') as $display_name => $display) {
          $access = &$display['display_options']['access'];
          if (isset($access) && $access['type'] == 'none') {
            $findings[$view->id()][] = $display_name;
          }
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
    $paragraphs[] = "Views can check if the user is allowed access to the content. It is recommended that all Views implement some amount of access control, at a minimum checking for the permission 'access content'.";

    return array(
      '#theme' => 'check_help',
      '#title' => 'Views access',
      '#paragraphs' => $paragraphs,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return array();
    }

    $paragraphs = array();
    $paragraphs[] = "The following View displays do not check access.";

    $items = array();
    foreach ($findings as $view_id => $displays) {
      $view = entity_load('view', $view_id);
      foreach ($displays as $display) {
        $items[] = $this->l(
          $view->label() . ': ' . $display,
          Url::fromRoute(
            'entity.view.edit_display_form',
            array(
              'view' => $view_id,
              'display_id' => $display,
            )
          )
        );
      }
    }

    return array(
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $items,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $findings = $result->findings();
    if (empty($findings)) {
      return '';
    }

    $output = $this->t('Views without access check:') . ":\n";
    foreach ($findings as $view_id => $displays) {
      $output .= "\t" . $view_id . ": " . implode(', ', $displays) . "\n";
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return $this->t('Views are access controlled.');

      case CheckResult::FAIL:
        return $this->t('There are Views that do not provide any access checks.');

      case CheckResult::INFO:
        return $this->t('Module views is not enabled.');

      default:
        return $this->t('Unexpected result.');
    }
  }

}
