<?php
/**
 * @file
 * Represents a single insertion of a sloth into a host node.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\sloth;


class SlothReferenceBag {
  /**
   * The nid of the node where the sloth is being inserted.
   *
   * @var integer
   */
  protected $host_nid;

  /**
   * The nid of the sloth being inserted.
   *
   * @var integer
   */
  protected $sloth_nid;

  /**
   * The name of the field the sloth is inserted into.
   *
   * @var string
   */
  protected $field_name;

  /**
   * Which value of the field has the sloth inserted.
   * Fields can be multivalued.
   *
   * @var integer
   */
  protected $delta;

  /**
   * The approximate location of the sloth tag in the host field's content.
   *
   * @var integer
   */
  protected $location;

  /**
   * Which view mode is used to display the sloth.
   *
   * @var string
   */
  protected $view_mode;

  /**
   * Content local to the insertion.
   *
   * @var string
   */
  protected $local_content;

  /**
   * Field HTML, with sloth tags in CKEditor format.
   *
   * @var string
   */
  protected $ck_html;

  /**
   * Field HTML, with sloth tags in DB format.
   *
   * @var string
   */
  protected $db_html;

  /**
   * @return int
   */
  public function getHostNid() {
    return $this->host_nid;
  }

  /**
   * @param int $host_nid
   * @return SlothReferenceBag
   */
  public function setHostNid($host_nid) {
    $this->host_nid = $host_nid;
    return $this;
  }

  /**
   * @return int
   */
  public function getSlothNid() {
    return $this->sloth_nid;
  }

  /**
   * @param int $sloth_nid
   * @return SlothReferenceBag
   */
  public function setSlothNid($sloth_nid) {
    $this->sloth_nid = $sloth_nid;
    return $this;
  }

  /**
   * @return string
   */
  public function getFieldName() {
    return $this->field_name;
  }

  /**
   * @param string $field_name
   * @return SlothReferenceBag
   */
  public function setFieldName($field_name) {
    $this->field_name = $field_name;
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
   * @return SlothReferenceBag
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
   * @return SlothReferenceBag
   */
  public function setLocation($location) {
    $this->location = $location;
    return $this;
  }

  /**
   * @return string
   */
  public function getViewMode() {
    return $this->view_mode;
  }

  /**
   * @param string $view_mode
   * @return SlothReferenceBag
   */
  public function setViewMode($view_mode) {
    $this->view_mode = $view_mode;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getLocalContent() {
    return $this->local_content;
  }

  /**
   * @param mixed $local_content
   * @return SlothReferenceBag
   */
  public function setLocalContent($local_content) {
    $this->local_content = $local_content;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getCkHtml() {
    return $this->ck_html;
  }

  /**
   * @param mixed $ck_html
   * @return SlothReferenceBag
   */
  public function setCkHtml($ck_html) {
    $this->ck_html = $ck_html;
    return $this;
  }

  /**
   * @return string
   */
  public function getDbHtml() {
    return $this->db_html;
  }

  /**
   * @param string $db_html
   * @return SlothReferenceBag
   */
  public function setDbHtml($db_html) {
    $this->db_html = $db_html;
    return $this;
  }

}