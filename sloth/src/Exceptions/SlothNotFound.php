<?php
/**
 * @file
 * Exception class, used when slothy data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\sloth\Exceptions;


class SlothNotFoundException extends SlothException  {

  /**
   * Constructs a SlothNotFoundException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf('Sloth not found: %s.', $message);
    parent::__construct($message);
  }
}