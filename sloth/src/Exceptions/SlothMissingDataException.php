<?php
/**
 * @file
 * Exception class, used when slothy data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\sloth\Exceptions;


class SlothMissingDataException extends SlothException  {

  /**
   * Constructs an SlothMissingDataException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Missing data: %s.', $message);
    parent::__construct($message);
  }
}