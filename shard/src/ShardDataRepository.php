<?php
/**
 * @file
 * Holds data about shards, for convenient transfer between
 * hook code in the .module file.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Drupal\Component\Uuid\Uuid;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;

class ShardDataRepository {

  /**
   * UUID used as placeholder for new node's nid.
   *
   * @var string
   */
  protected $hostPlaceholderNid = NULL;

  /**
   * The actual host nid. For new nodes, this replaces
   * the placeholder UUID.
   *
   * @var int
   */
  protected $actualHostNid = NULL;

  /**
   * New shards to be created for new node.
   *
   * @var ShardModel[]
   */
  protected $newShardCollectionItems = [];

  /**
   * @return mixed
   */
  public function getHostPlaceholderNid() {
    return $this->hostPlaceholderNid;
  }

  /**
   * @param mixed $value
   * @return \Drupal\shard\ShardDataRepository
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setHostPlaceholderNid($value) {
    if ( ! Uuid::isValid($value) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Placeholder nid should be UUID. Got: %s', $value)
      );
    }
    $this->hostPlaceholderNid = $value;
    return $this;
  }

  /**
   * @return int
   */
  public function getActualHostNid() {
    return $this->actualHostNid;
  }

  /**
   * @param $value
   * @return \Drupal\shard\ShardDataRepository
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setActualHostNid($value) {
    if ( ! is_numeric($value) || $value <= 0 ) {
      throw new ShardUnexpectedValueException(
        sprintf('Actual nid should be positive number. Got: %s', $value)
      );
    }
    $this->actualHostNid = $value;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getNewShardCollectionItems() {
    return $this->newShardCollectionItems;
  }

  /**
   * @param mixed $newShardCollectionItems
   * @return ShardDataRepository
   */
  public function setNewShardCollectionItems($newShardCollectionItems) {
    $this->newShardCollectionItems = $newShardCollectionItems;
    return $this;
  }


}