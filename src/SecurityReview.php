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
    if(!static::configured())
      return static::defaultUntrustedRoles();

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
}
