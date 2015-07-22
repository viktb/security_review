<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\UploadExtensions.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\Security;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Checks for unsafe extensions in the allowed extensions settings of fields.
 */
class UploadExtensions extends Check {

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
    return 'Allowed upload extensions';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'upload_extensions';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    // If field is not enabled return with INFO.
    if (!Drupal::moduleHandler()->moduleExists('field')) {
      return $this->createResult(CheckResult::INFO);
    }

    $result = CheckResult::SUCCESS;
    $findings = array();

    // Check field configuration entities.
    $entities = entity_load_multiple('field_config');
    foreach ($entities as $entity) {
      /** @var FieldConfig $entity */
      $extensions = $entity->getSetting('file_extensions');
      if ($extensions != NULL) {
        $extensions = explode(' ', $extensions);
        $intersect = array_intersect($extensions, Security::unsafeExtensions());
        // $intersect holds the unsafe extensions this entity allows.
        foreach ($intersect as $unsafe_extension) {
          $findings[$entity->id()][] = $unsafe_extension;
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
    $paragraphs[] = t(
      'File and image fields allow for uploaded files. Some extensions are considered dangerous because the files can be evaluated and then executed in the browser. A malicious user could use this opening to gain control of your site. Review !fields_report.',
      array(
        '!fields_report' => Drupal::l(
          'all fields on your site',
          Url::fromRoute('entity.field_storage_config.collection')
        ),
      )
    );

    return array(
      '#theme' => 'check_help',
      '#title' => 'Allowed upload extensions',
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
    $paragraphs[] = 'The following extensions are considered unsafe and should be removed or limited from use. Or, be sure you are not granting untrusted users the ability to upload files.';

    $items = array();
    foreach ($findings as $entity_id => $unsafe_extensions) {
      $entity = entity_load('field_config', $entity_id);
      /** @var FieldConfig $entity */

      foreach ($unsafe_extensions as $extension) {
        $item = t(
          'Review @type in <em>@field</em> field on @bundle',
          array(
            '@type' => $extension,
            '@field' => $entity->label(),
            '@bundle' => $entity->getTargetBundle(),
          )
        );

        // Try to get an edit url.
        try {
          $url_params = array('field_config' => $entity->id());
          if ($entity->getTargetEntityTypeId() == 'node') {
            $url_params['node_type'] = $entity->getTargetBundle();
          }
          $url = Url::fromRoute(
            'entity.field_config.' . $entity->getTargetEntityTypeId() . '_field_edit_form',
            $url_params
          );
          $items[] = Drupal::l($item, $url);
        }
        catch (RouteNotFoundException $e) {
          $items[] = $item;
        }
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

    $output = '';
    foreach ($findings as $entity_id => $unsafe_extensions) {
      $entity = entity_load('field_config', $entity_id);
      /** @var FieldConfig $entity */

      $output .= t(
        '!bundle: field !field',
        array(
          '!bundle' => $entity->getTargetBundle(),
          '!field' => $entity->label(),
        )
      );
      $output .= "\n\t" . implode(', ', $unsafe_extensions) . "\n";
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($result_const) {
    switch ($result_const) {
      case CheckResult::SUCCESS:
        return 'Only safe extensions are allowed for uploaded files and images.';

      case CheckResult::FAIL:
        return 'Unsafe file extensions are allowed in uploads.';

      case CheckResult::INFO:
        return 'Module field is not enabled.';

      default:
        return 'Unexpected result.';
    }
  }

}
