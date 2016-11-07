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
   * Is this data for a new node?
   *
   * @var bool
   */
  protected $isNewNode = FALSE;

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
   * @param boolean $isNewNode
   * @return ShardDataRepository
   */
  public function setIsNewNode($isNewNode) {
    $this->isNewNode = $isNewNode;
    return $this;
  }

  /**
   * @return boolean
   */
  public function isNewNode() {
    return $this->isNewNode;
  }

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
    $isPositiveNumber = is_numeric($value) && $value > 0;
    $isUuid = Uuid::isValid($value);
    if ( ! $isPositiveNumber && ! $isUuid ) {
      throw new ShardUnexpectedValueException(
        sprintf('Nid should be number or UUID. Got: %s', $value)
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