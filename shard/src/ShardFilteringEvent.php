<?php
/**
 * @file
 * Object passed around when firing an after filtering event. The event
 * is fired after the shard filter has done its conversion, but before
 * it has passed the result to the renderer.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Symfony\Component\EventDispatcher\Event;

class ShardFilteringEvent extends Event {

  protected $html;

  public function __construct($html) {
    $this->html = $html;
  }

  /**
   * @return mixed
   */
  public function getHtml() {
    return $this->html;
  }

  /**
   * @param mixed $html
   */
  public function setHtml($html) {
    $this->html = $html;
  }

}