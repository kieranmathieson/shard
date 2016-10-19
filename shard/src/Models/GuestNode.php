<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 10/17/16
 * Time: 1:47 PM
 */

namespace Drupal\shard\Models;

use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;


class GuestNode extends Node {

  protected $shards = [];

  /**
   * @return int
   */
  public function getNid() {
    return $this->nid;
  }

  /**
   * @param int $nid
   * @return $this For chaining.
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setNid($nid) {
    if ( ! $this->isValidNid($nid) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Nid not valid: %s', $nid)
      );
    }
    $this->nid = $nid;
    return $this;
  }

  /**
   * @return null
   */
  public function getContentType() {
    return $this->contentType;
  }

  /**
   * @param string $content_type
   * @return $this For chaining.
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setContentType($content_type) {
    if ( ! $this->isValidContentType($content_type) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Content type not valid: %s', $content_type)
      );
    }
    $this->contentType = $content_type;
    return $this;
  }

  public function addShard(Shard $shard) {
    if ( $shard->getHostNid() != $this->getNid() ) {
      throw new ShardUnexpectedValueException(
        sprintf(
          'Shard host nid of %u does not match node nid of %u.',
          $shard->getHostNid(),
          $this->getNid()
        )
      );
    }
    if ( ! in_array($shard->getGuestFieldName(), $this->validGuestFields) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Unknown guest field name: %s', $shard->getGuestFieldName())
      );
    }
    if ( ! $this->isValidViewMode($shard->getViewMode()) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Unknown view mode: %s', $shard->getViewMode())
      );
    }
    $this->guestShards[] = $shard;
    return $this;
  }

  public function addValidGuestField($field_name) {
    if ( ! $field_name ) {
      throw new ShardMissingDataException(
        sprintf('Missing field name')
      );
    }
    $this->validGuestFields[] = $field_name;
    return $this;
  }

}