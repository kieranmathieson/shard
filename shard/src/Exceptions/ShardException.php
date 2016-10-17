<?php
/**
 * @file
 * Exception class, used when slothy data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\shard\Exceptions;


class ShardException extends \Exception {

  /**
   * Constructs an ShardException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf(
      "Shard problem! %s", $message);
    parent::__construct($message);
  }
}