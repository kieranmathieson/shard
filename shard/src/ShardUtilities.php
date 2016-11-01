<?php
/**
 * @file
 * Useful utilities for shard processing.
 */

namespace Drupal\shard;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ShardUtilities {

  public function __construct() {
  }

  public static function create(ContainerInterface $container) {
    return new static(
    );
  }

  public static function currentUserHasRole($roleNamesToCheck) {
    $user = \Drupal::currentUser();
    $definedRoles = $user->getRoles();
    if ( is_array($roleNamesToCheck) ) {
      //Got an array of roles to check.
      foreach($roleNamesToCheck as $roleNameToCheck) {
        if ( self::inStringArray($roleNameToCheck, $definedRoles) ) {
          return TRUE;
        }
      }
      return FALSE;
    }
    //Didn't get an array of roles.
    return self::inStringArray($roleNamesToCheck, $definedRoles);
  }

  /**
   * Check whether a value is in a string array. Case and outside whitespace
   * don't matter.
   * @param string $valueToCheck
   * @param string[] $valueArray
   * @return bool
   */
  public static function inStringArray($valueToCheck, $valueArray) {
    foreach($valueArray as $value) {
      if ( trim(strtolower($valueToCheck)) == trim(strtolower($value)) ) {
        return TRUE;
      }
    }
    return FALSE;
  }

}