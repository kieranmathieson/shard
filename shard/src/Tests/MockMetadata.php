<?php
/**
 * @file
 * Mock of ShardMetadata for testing.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;

use Drupal\node\NodeInterface;
use Drupal\shard\ShardMetadata;
use Drupal\shard\ShardMetadataInterface;

class MockMetadata implements ShardMetadataInterface {

  /**
   * @return \string[]
   */
  public function getShardTypeNames() {
    return ['sloth', 'page'];
  }

  /**
   * @param \string[] $shardTypeNames
   * @return ShardMetadata
   */
  public function setShardTypeNames($shardTypeNames) {
    // TODO: Implement setShardTypeNames() method.
  }

  /**
   * Check whether a name is a valid shard type name.
   *
   * @param string $name Name to check.
   * @return bool Is it valid?
   */
  public function isValidShardTypeName($name) {
    return in_array($name, $this->getShardTypeNames());
  }

  /**
   * Check whether nid is valid.
   *
   * @param int $value Value to check.
   * @return bool True if valid, else false.
   */
  public function isValidNid($value) {
    return TRUE;
  }

  /**
   * Check whether view mode name is valid.
   *
   * @param string $value Value to check.
   * @return bool True if valid, else false.
   */
  public function isValidViewModeName($value) {
    return TRUE;
  }

  /**
   * Check whether content type name is valid.
   *
   * @param string $value Value to check.
   * @return bool True if valid, else false.
   */
  public function isValidContentTypeName($value) {
    return TRUE;
  }

  /**
   * Return a list of the names of fields that are allowed to have
   * shards in them.
   *
   * @param NodeInterface $node
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEligibleFieldsForNode(NodeInterface $node) {
    return ['body'];
  }

  /**
   * Return a list of the names of fields that are allowed to have
   * shards in them.
   *
   * @param string $bundleName Name of the bundle type, e.g., article.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEligibleFieldsForBundle($bundleName) {
    return ['body'];
  }
}