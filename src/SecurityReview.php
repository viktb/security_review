<?php

namespace Drupal\security_review;

use Drupal\Core\Session\AccountInterface;

class SecurityReview {
  /**
   * @return bool Returns whether the module has been configured.
   */
  public static function configured(){
    return \Drupal::config('security_review.settings')->get('configured') === true;
  }

  /**
   * @return bool Returns whether logging is enabled.
   */
  public static function logEnabled(){
    return \Drupal::config('security_review.settings')->get('log') === true;
  }

  /**
   * @return array Stored untrusted roles' IDs.
   */
  public static function untrustedRoles(){
    // If the module hasn't been manually configured yet, return the untrusted
    // roles depending on Drupal's actual configuration.
    if(!static::configured()){
      return static::defaultUntrustedRoles();
    }

    // Else return the stored untrusted roles.
    return \Drupal::config('security_review.settings')->get('untrusted_roles');
  }

  /**
   * @return array Trusted roles' IDs.
   */
  public static function trustedRoles(){
    $untrusted_roles = static::untrustedRoles();

    $trusted = array();
    foreach(user_roles() as $role){
      if(!in_array($role->id(), $untrusted_roles)){
        $trusted[] = $role->id();
      }
    }

    return $trusted;
  }

  /**
   * @return array Default untrusted roles' IDs.
   */
  public static function defaultUntrustedRoles(){
    $roles = array(AccountInterface::ANONYMOUS_ROLE);

    $user_register = \Drupal::config('user.settings')->get('register');
    if ($user_register !== USER_REGISTER_ADMINISTRATORS_ONLY) {
      // If visitors are allowed to create accounts they are considered untrusted.
      $roles[] = AccountInterface::AUTHENTICATED_ROLE;
    }

    return $roles;
  }

  /**
   * @param bool $groupByRoleId Choose whether to group permissions by role ID.
   * @return array An array of the permissions untrusted roles have. If
   * $groupByRoleId is set to true, the array key is the role ID, the value is
   * the array of permissions the role has.
   */
  public static function untrustedPermissions($groupByRoleId = false){
    $permissions_grouped = user_role_permissions(static::untrustedRoles());

    if($groupByRoleId){
      // If the result should be grouped, we have nothing else to do.
      return $permissions_grouped;
    }else{
      // Merge the grouped permissions into $untrusted_permissions.
      $untrusted_permissions = array();
      foreach($permissions_grouped as $rid => $permissions){
        $untrusted_permissions = array_merge($untrusted_permissions, $permissions);
      }
      // Remove duplicate elements and fix indexes.
      $untrusted_permissions = array_values(array_unique($untrusted_permissions));
      return $untrusted_permissions;
    }
  }
}
