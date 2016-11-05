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
  protected $newNodePlaceholderNid;

  /**
   * Ids of shard collection items to be deleted, when updating existing
   * host node.
   *
   * @var int[]
   */
  protected $oldShards;

  /**
   * New shards to be created for new node.
   *
   * @var ShardTagModel[]
   */
  protected $newShardCollectionItems;

  /**
   * @return mixed
   */
  public function getNewNodePlaceholderNid() {
    return $this->newNodePlaceholderNid;
  }

  /**
   * @param mixed $value
   * @return \Drupal\shard\ShardDataRepository
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setNewNodePlaceholderNid($value) {
    if ( ! Uuid::isValid($value) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Nid should be a UUID. Got: %s', $value)
      );
    }
    $this->newNodePlaceholderNid = $value;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getOldShards() {
    return $this->oldShards;
  }

  /**
   * @param mixed $oldShards
   * @return ShardDataRepository
   */
  public function setOldShards($oldShards) {
    $this->oldShards = $oldShards;
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