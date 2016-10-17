<?php
/**
 * @file
 * Exception class, used when slothy data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\sloth\Exceptions;


class SlothDatatbaseException extends SlothException  {

  /**
   * Constructs an SlothDatatbaseException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Database problem: %s.', $message);
    parent::__construct($message);
  }
}