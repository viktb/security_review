<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\Field.
 */

namespace Drupal\security_review\Checks;

use Drupal;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;

/**
 * Checks for Javascript and PHP in submitted content.
 */
class Field extends Check {

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
    return 'Content';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'field';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = array();

    $tags = array(
      'Javascript' => 'script',
      'PHP' => '?php'
    );

    // Load all of the entities.
    $entities = array();
    $bundleInfo = Drupal::entityManager()->getAllBundleInfo();
    foreach ($bundleInfo as $entity_type_id => $bundles) {
      $entities = array_merge($entities, entity_load_multiple($entity_type_id));
    }

    // Search for text fields.
    $textItems = array();
    foreach ($entities as $entity) {
      if ($entity instanceof FieldableEntityInterface) {
        /** @var FieldableEntityInterface $entity */
        foreach ($entity->getFields() as $fieldList) {
          foreach ($fieldList as $fieldItem) {
            if ($fieldItem instanceof TextItemBase) {
              /** @var TextItemBase $item */
              // Text field found.
              $textItems[] = $fieldItem;
            }
          }
        }
      }
    }

    // Scan the text items for vulnerabilities.
    foreach ($textItems as $item) {
      $entity = $item->getEntity();
      foreach ($item->getProperties() as $property) {
        /** @var TypedDataInterface $property */
        $value = $property->getValue();
        if (is_string($value)) {
          $fieldName = $property->getDataDefinition()->getLabel();
          foreach ($tags as $vulnerability => $tag) {
            if (strpos($value, '<' . $tag) !== FALSE) {
              // Vulnerability found.
              $findings[$entity->getEntityTypeId()][$entity->id()][$fieldName][] = $vulnerability;
            }
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
    $paragraphs[] = "Script and PHP code in content does not align with Drupal best practices and may be a vulnerability if an untrusted user is allowed to edit such content. It is recommended you remove such contents.";

    return array(
      '#theme' => 'check_help',
      '#title' => 'Dangerous tags in content',
      '#paragraphs' => $paragraphs
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
    $paragraphs[] = "The following items potentially have dangerous tags.";

    $items = array();
    foreach ($findings as $entity_type_id => $entities) {
      foreach ($entities as $entity_id => $fields) {
        $entity = entity_load($entity_type_id, $entity_id);
        foreach ($fields as $field => $finding) {
          $url = $entity->urlInfo('edit-form');
          if ($url === NULL) {
            $url = $entity->urlInfo();
          }
          $items[] = t(
            '@vulnerabilities found in <em>@field</em> field of !link',
            array(
              '@vulnerabilities' => implode(' and ', $finding),
              '@field' => $field,
              '!link' => Drupal::l(
                $entity->label(),
                $url
              )
            )
          );
        }
      }
    }

    return array(
      '#theme' => 'check_evaluation',
      '#paragraphs' => $paragraphs,
      '#items' => $items
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
    foreach ($findings as $entity_type_id => $entities) {
      foreach ($entities as $entity_id => $fields) {
        $entity = entity_load($entity_type_id, $entity_id);
        foreach ($fields as $field => $finding) {
          $url = $entity->urlInfo('edit-form');
          if ($url === NULL) {
            $url = $entity->url();
          }
          $output .= "\t" . t(
              '@vulnerabilities in @field of !link',
              array(
                '@vulnerabilities' => implode(' and ', $finding),
                '@field' => $field,
                '!link' => $url->toString()
              )
            ) . "\n";
        }
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
        return 'Dangerous tags were not found in any submitted content (fields).';
      case CheckResult::FAIL:
        return 'Dangerous tags were found in submitted content (fields).';
      default:
        return 'Unexpected result.';
    }
  }

}
