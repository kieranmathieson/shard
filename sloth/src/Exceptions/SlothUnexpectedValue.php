<?php
/**
 * @file
 * Exception class, used when slothy data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\sloth\Exceptions;


class SlothUnexptectedValueException extends SlothException  {

  /**
   * Constructs a SlothUnexptectedValueException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Unexpected value: %s.', $message);
    parent::__construct($message);
  }
}