<?php

/**
 * @file
 * Contains \Drupal\security_review\Checks\AdminPermissions.
 */

namespace Drupal\security_review\Checks;

use Drupal\Core\Url;
use Drupal\security_review\Check;
use Drupal\security_review\CheckResult;
use Drupal\security_review\Security;
use Drupal\user\Entity\Role;

class AdminPermissions extends Check {

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
    return 'Drupal permissions';
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineTitle() {
    return 'admin_permissions';
  }

  /**
   * {@inheritdoc}
   */
  public function run() {
    $result = CheckResult::SUCCESS;
    $findings = array();

    // Collect permissions marked as for trusted users only.
    $all_permissions = Security::permissions(TRUE);
    $all_keys = array_keys($all_permissions);

    // Get permissions for untrusted roles.
    $untrusted_permissions = Security::untrustedPermissions(TRUE);
    foreach ($untrusted_permissions as $rid => $permissions) {
      $intersect = array_intersect($all_keys, $permissions);
      foreach ($intersect as $permission) {
        if (isset($all_permissions[$permission]['restrict access'])) {
          $findings[$rid][] = $permission;
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
    $output = '';
    $output .= '<p>';
    $output .= t("Drupal's permission system is extensive and allows for varying degrees of control. Certain permissions would allow a user total control, or the ability to escalate their control, over your site and should only be granted to trusted users.");
    $output .= '</p>';

    return array(
      '#type' => 'markup',
      '#markup' => $output
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($resultConst) {
    switch ($resultConst) {
      case CheckResult::SUCCESS:
        return 'Untrusted roles do not have administrative or trusted Drupal permissions.';
      case CheckResult::FAIL:
        return 'Untrusted roles have been granted administrative or trusted Drupal permissions.';
      default:
        return "Unexpected result.";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(CheckResult $result) {
    $output = '';

    foreach ($result->findings() as $rid => $permissions) {
      $role = Role::load($rid);
      /** @var Role $role */
      $output .= t(
        "<p>!role has the following restricted permissions:</p>",
        array(
          '!role' => \Drupal::l(
            $role->label(),
            Url::fromRoute(
              'entity.user_role.edit_permissions_form',
              array('user_role' => $role->id())
            )
          )
        )
      );

      $output .= "<ul>";
      foreach ($permissions as $permission) {
        $output .= t('<li>@permission</li>', array(
          '@permission' => $permission
        ));
      }
      $output .= "</ul>";
    }

    return array(
      '#type' => 'markup',
      '#markup' => $output
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluatePlain(CheckResult $result) {
    $output = '';

    foreach ($result->findings() as $rid => $permissions) {
      $role = Role::load($rid);
      /** @var Role $role */

      $output .= t(
        '!role has !permissions',
        array(
          '!role' => $role->label(),
          '!permissions' => implode(', ', $permissions)
        )
      );
      $output .= "\n";
    }

    return $output;
  }

}
