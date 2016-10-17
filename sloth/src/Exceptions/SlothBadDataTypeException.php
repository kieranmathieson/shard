<?php
/**
 * @file
 * Exception class, used when slothy data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\sloth\Exceptions;


class SlothBadDataTypeException extends SlothException  {

  /**
   * Constructs an SlothBadDataTypeException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Bad data type: %s.', $message);
    parent::__construct($message);
  }
}