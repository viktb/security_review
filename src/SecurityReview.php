<?php

namespace Drupal\security_review;

use Drupal\Core\Session\AccountInterface;

class SecurityReview {
  public static function untrustedRoles(){
    return \Drupal::config('security_review.settings')->get('untrusted_roles');
  }

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
