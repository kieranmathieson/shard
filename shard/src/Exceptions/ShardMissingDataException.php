<?php
/**
 * @file
 * Exception class, used when shard data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\shard\Exceptions;


class ShardMissingDataException extends ShardException  {

  /**
   * Constructs an ShardMissingDataException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Missing data: %s.', $message);
    parent::__construct($message);
  }
}