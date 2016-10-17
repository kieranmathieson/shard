<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 9/20/16
 * Time: 4:45 PM
 */

namespace Drupal\sloth\Form;


class CandidateField {
  protected $machineName;
  protected $displayName;
  protected $inContentTypes;

  /**
   * CandidateField constructor.
   * @param $machineName
   * @param $displayName
   */
  public function __construct($machineName, $displayName) {
    $this->machineName = $machineName;
    $this->displayName = $displayName;
  }

  /**
   * @return mixed
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * @param mixed $machineName
   * @return CandidateField
   */
  public function setMachineName($machineName) {
    $this->machineName = $machineName;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getDisplayName() {
    return $this->displayName;
  }

  /**
   * @param mixed $displayName
   * @return CandidateField
   */
  public function setDisplayName($displayName) {
    $this->displayName = $displayName;
    return $this;
  }

  public function addContentType($contentType) {
    $this->inContentTypes[] = $contentType;
    return $this;
  }

  public function getContentTypeListString() {
    $list = implode(', ', $this->inContentTypes);
    return $list;
  }

}