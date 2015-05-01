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
   * @return array Stored untrusted roles.
   */
  public static function untrustedRoles(){
    return \Drupal::config('security_review.settings')->get('untrusted_roles');
  }

  /**
   * @return array Default untrusted roles.
   */
  public static function defaultUntrustedRoles(){
    $roles = array(AccountInterface::ANONYMOUS_ROLE => t('anonymous user'));

    $user_register = \Drupal::config('user.settings')->get('register');
    if ($user_register !== USER_REGISTER_ADMINISTRATORS_ONLY) {
      // If visitors are allowed to create accounts they are considered untrusted.
      $roles[AccountInterface::AUTHENTICATED_ROLE] = t('authenticated user');
    }
    return $roles;
  }
}
