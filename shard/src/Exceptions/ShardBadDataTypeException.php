<?php
/**
 * @file
 * Exception class, used when slothy data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\shard\Exceptions;


class ShardBadDataTypeException extends ShardException  {

  /**
   * Constructs an ShardBadDataTypeException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Bad data type: %s.', $message);
    parent::__construct($message);
  }
}