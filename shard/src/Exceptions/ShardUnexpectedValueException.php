<?php
/**
 * @file
 * Exception class, used when shard data has bad values.
 *
 * @author kieran Mathieson
 */

namespace Drupal\shard\Exceptions;


class ShardUnexpectedValueException extends ShardException  {

  /**
   * Constructs a ShardUnexpectedValueException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Unexpected value: %s.', $message);
    parent::__construct($message);
  }
}