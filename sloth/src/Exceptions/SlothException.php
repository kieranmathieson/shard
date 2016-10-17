<?php
/**
 * @file
 * Exception class, used when slothy data is missing.
 *
 * @author kieran Mathieson
 */

namespace Drupal\sloth\Exceptions;


class SlothException extends \Exception {

  /**
   * Constructs an SlothException.
   *
   * @param string $message Message about the bad thing.
   */
  public function __construct($message) {
    $message = sprintf(
      "Sloth problem! %s", $message);
    parent::__construct($message);
  }
}