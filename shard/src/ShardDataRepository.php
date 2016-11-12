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
   * @var array Holds all data. In an array for easy serialization.
   * Elements:
   *
   *  - hostPlaceholderNidU (string) UID used as placeholder for new node's nid.
   *
   *  - actualHostNid (int) The actual host nid. For new nodes, this replaces
   *    the placeholder UUID.
   *
   *  - newShardCollectionItems (array) New shards to be created for new node.
   */
  protected $dataArray = [];

  public function __construct() {
    $this->dataArray['hostPlaceholderNid'] = NULL;
    $this->dataArray['actualHostNid'] = NULL;
    $this->dataArray['newShardCollectionItems'] = NULL;
  }

  /**
   * @return mixed
   */
  public function getHostPlaceholderNid() {
    return $this->dataArray['hostPlaceholderNid'];
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
    $this->dataArray['hostPlaceholderNid'] = $value;
    return $this;
  }

  /**
   * @return int
   */
  public function getActualHostNid() {
    return $this->dataArray['actualHostNid'];
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
    $this->dataArray['actualHostNid'] = $value;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getNewShardCollectionItems() {
    return $this->dataArray['newShardCollectionItems'];
  }

  /**
   * @param mixed $newShardCollectionItems
   * @return ShardDataRepository
   */
  public function setNewShardCollectionItems($newShardCollectionItems) {
    $this->dataArray['newShardCollectionItems'] = $newShardCollectionItems;
    return $this;
  }

  /**
   * Serializes all the data for this object into a string.
   *
   * @return string Serialized data.
   */
  public function serialize() {
    $tempShardModels = [];
    /* @var \Drupal\shard\ShardModel $shardCollectionItem */
    foreach ($this->dataArray['newShardCollectionItems'] as $shardCollectionItem) {
      $tempShardModels[] = $shardCollectionItem->serialize();
    }
    $this->dataArray['newShardCollectionItems'] = $tempShardModels;
    return serialize($this->dataArray);
  }

  /**
   * Unserialize a string into data for this object.
   *
   * @param string $serializedData Serialized data.
   */
  public function unserialize($serializedData) {
    $this->dataArray = unserialize($serializedData);
    $tempShardModels = [];
    foreach($this->dataArray['newShardCollectionItems'] as $serializedItem) {
      //shard.model service supplies a new object each time service()
      //is called.
      /* @var ShardModel $tempShardModel */
      $tempShardModel = \Drupal::service('shard.model');
      $tempShardModel->unserialize($serializedItem);
      $tempShardModels[] = $tempShardModel;
    }
    $this->dataArray['newShardCollectionItems'] = $tempShardModels;
  }
}