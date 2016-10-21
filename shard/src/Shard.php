<?php
/**
 * @file
 * Represents a shard.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;


class Shard {
  /**
   * The nid of the node where the shard is being inserted.
   *
   * @var integer
   */
  protected $hostNid;

  /**
   * The nid of the shard being inserted.
   *
   * @var integer
   */
  protected $guestNid;

  /**
   * The name of the field the shard is inserted into.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Which value of the field has the shard inserted.
   * Fields can be multivalued.
   *
   * @var integer
   */
  protected $delta;

  /**
   * The approximate location of the shard tag in the host field's content.
   *
   * @var integer
   */
  protected $location;

  /**
   * Which view mode is used to display the shard.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * Content local to the insertion.
   *
   * @var string
   */
  protected $localContent;

  /**
   * @return int
   */
  public function getHostNid() {
    return $this->hostNid;
  }

  /**
   * @param int $hostNid
   * @return Shard
   */
  public function setHostNid($hostNid) {
    $this->hostNid = $hostNid;
    return $this;
  }

  /**
   * @return int
   */
  public function getGuestNid() {
    return $this->guestNid;
  }

  /**
   * @param int $guestNid
   * @return Shard
   */
  public function setGuestNid($guestNid) {
    $this->guestNid = $guestNid;
    return $this;
  }

  /**
   * @return string
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * @param string $fieldName
   * @return Shard
   */
  public function setFieldName($fieldName) {
    $this->fieldName = $fieldName;
    return $this;
  }

  /**
   * @return int
   */
  public function getDelta() {
    return $this->delta;
  }

  /**
   * @param int $delta
   * @return Shard
   */
  public function setDelta($delta) {
    $this->delta = $delta;
    return $this;
  }

  /**
   * @return int
   */
  public function getLocation() {
    return $this->location;
  }

  /**
   * @param int $location
   * @return Shard
   */
  public function setLocation($location) {
    $this->location = $location;
    return $this;
  }

  /**
   * @return string
   */
  public function getViewMode() {
    return $this->viewMode;
  }

  /**
   * @param string $viewMode
   * @return Shard
   */
  public function setViewMode($viewMode) {
    $this->viewMode = $viewMode;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getLocalContent() {
    return $this->localContent;
  }

  /**
   * @param mixed $localContent
   * @return Shard
   */
  public function setLocalContent($localContent) {
    $this->localContent = $localContent;
    return $this;
  }

}