<?php

/**
 * @file
 * Contains \Drupal\security_review\SecurityReview.
 */

namespace Drupal\security_review;

use Drupal\Core\Session\AccountInterface;

/**
 * A class containing static methods regarding configuration, and frequently
 * used data.
 */
class SecurityReview {
  /**
   * Private constructor for disabling instantiation of the static class.
   */
  private function __construct(){
    /*
     * Note: This class might become singleton in the future, depending on the
     * rest of the architecture.
     */
  }

  /**
   * If the module has been configured on the settings page this function
   * returns true. Otherwise it returns false.
   *
   * @return bool
   *   A boolean indicating whether the module has been configured.
   */
  public static function configured() {
    return \Drupal::config('security_review.settings')
      ->get('configured') === TRUE;
  }

  /**
   * Returns true if logging is enabled, otherwise returns false.
   *
   * @return bool
   *   A boolean indicating whether logging is enabled.
   */
  public static function logEnabled() {
    return \Drupal::config('security_review.settings')->get('log') === TRUE;
  }

  /**
   * Returns the IDs of untrusted roles.
   *
   * If the module hasn't been configured yet, it returns the default untrusted
   * roles.
   *
   * @return array
   *   Stored untrusted roles' IDs.
   */
  public static function untrustedRoles() {
    // If the module hasn't been manually configured yet, return the untrusted
    // roles depending on Drupal's actual configuration.
    if (!static::configured()) {
      return static::defaultUntrustedRoles();
    }

    // Else return the stored untrusted roles.
    return \Drupal::config('security_review.settings')->get('untrusted_roles');
  }

  /**
   * Returns the trusted roles depending on the stored/default untrusted roles.
   *
   * @return array
   *   Trusted roles' IDs.
   */
  public static function trustedRoles() {
    // Get the stored/default untrusted roles.
    $untrusted_roles = static::untrustedRoles();

    // Iterate through all the roles, and store which are not untrusted.
    $trusted = array();
    foreach (user_roles() as $role) {
      if (!in_array($role->id(), $untrusted_roles)) {
        $trusted[] = $role->id();
      }
    }

    // Return the trusted roles.
    return $trusted;
  }

  /**
   * Returns the default untrusted roles.
   *
   * The default untrusted roles are:
   *   Anonymous      : always
   *   Authenticated  : if visitors are allowed to create accounts.
   *
   * @return array
   *   Default untrusted roles' IDs.
   */
  public static function defaultUntrustedRoles() {
    // Add the Anonymous role to the output array.
    $roles = array(AccountInterface::ANONYMOUS_ROLE);

    // Check whether visitors can create accounts.
    $user_register = \Drupal::config('user.settings')->get('register');
    if ($user_register !== USER_REGISTER_ADMINISTRATORS_ONLY) {
      // If visitors are allowed to create accounts they are considered untrusted.
      $roles[] = AccountInterface::AUTHENTICATED_ROLE;
    }

    // Return the untrusted roles.
    return $roles;
  }

  /**
   * Returns the permission strings that untrusted roles have.
   *
   * @param bool $groupByRoleId
   *   Choose whether to group permissions by role ID.
   * @return array
   *   An array of the permissions untrusted roles have. If $groupByRoleId is
   *   true, the array key is the role ID, the value is the array of permissions
   *   the role has.
   */
  public static function untrustedPermissions($groupByRoleId = FALSE) {
    // Get the permissions untrusted roles have, grouped by roles.
    $permissions_grouped = user_role_permissions(static::untrustedRoles());

    if ($groupByRoleId) {
      // If the result should be grouped, we have nothing else to do.
      return $permissions_grouped;
    }
    else {
      // Merge the grouped permissions into $untrusted_permissions.
      $untrusted_permissions = array();
      foreach ($permissions_grouped as $rid => $permissions) {
        $untrusted_permissions = array_merge($untrusted_permissions, $permissions);
      }

      // Remove duplicate elements and fix indexes.
      $untrusted_permissions = array_values(array_unique($untrusted_permissions));
      return $untrusted_permissions;
    }
  }
}
