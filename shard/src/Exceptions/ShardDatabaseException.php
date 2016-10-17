<?php
/**
 * @file
 * Exception class, used when there's a DB issue.
 *
 * @author kieran Mathieson
 */

namespace Drupal\shard\Exceptions;


class ShardDatabaseException extends ShardException  {

  /**
   * Constructs an ShardDatabaseException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Database problem: %s.', $message);
    parent::__construct($message);
  }
}