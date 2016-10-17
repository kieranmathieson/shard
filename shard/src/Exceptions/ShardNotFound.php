<?php
/**
 * @file
 * Exception class, used when a shard is not found.
 *
 * @author kieran Mathieson
 */

namespace Drupal\shard\Exceptions;


class ShardNotFoundException extends ShardException  {

  /**
   * Constructs a ShardNotFoundException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Shard not found: %s.', $message);
    parent::__construct($message);
  }
}