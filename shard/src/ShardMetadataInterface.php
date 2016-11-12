<?php
/**
 * @file
 * Interface for a class that stores metadata about the node entity type.
 * Includes fields eligible for hosting shards. This metadata is
 * specific to the shard module.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

interface ShardMetadataInterface {

  //The name of the entity type used by the field collection module
  //to store collection item data.
  const SHARD_ENTITY_TYPE = 'field_collection_item';

  //The name of the bundle of the field collection entity type that holds
  //shard data.
  const SHARD_BUNDLE_NAME = 'field_shard';

  //HTML tag used to identify an element as a shard tag, and its type.
  const SHARD_TYPE_TAG = 'data-shard-type';

  //HTML tag used in the DB format of a tag to store the id of a collection
  //item entity for a shard.
  const SHARD_ID_ATTRIBUTE = 'data-shard-id';

  //HTML tag used in CKEditor format to show the nid of the guest node.
  const SHARD_GUEST_NID_TAG = 'data-guest-id';

  //HTML tag used in CKEditor format to show the view mode to use.
  const SHARD_VIEW_FORMAT_ATTRIBUTE = 'data-view-mode';

  //HTML attribute to identify whether shard tag has been processed.
  const SHARD_TAG_BEEN_PROCESSED_ATTRIBUTE = 'data-shard-processed';

  //Value for SHARD_TAG_BEEN_PROCESSED_ATTRIBUTE that shows the tag
  //has been processed.
  const SHARD_TAG_HAS_BEEN_PROCESSED_VALUE = 'processed';

  /**
   * This class tells CKEditor that some HTML is a widget. Replace [type]
   * with shard type at runtime. Same as module name.
   */
  const CLASS_IDENTIFYING_WIDGET = '[type]-shard';

  //Names of the fields in the shard bundle of collection item entities.
  const FIELD_NAME_HOST_NODE_ID = 'field_host_node';
  const FIELD_NAME_HOST_FIELD = 'field_host_field';
  const FIELD_HOST_FIELD_DELTA = 'field_host_field_delta';
  const FIELD_NAME_VIEW_MODE = 'field_view_mode';
  const FIELD_NAME_LOCATION = 'field_shard_location';
  const FIELD_NAME_LOCAL_CONTENT = 'field_custom_content';

  //Class name for local content, for styling.
  const SHARD_LOCAL_CONTENT_CLASS = 'local-content';

  //Config key used to store temp data while creating or updating a
  //shardable node.
  const SHARD_NODE_SAVE_DATA_REPO = 'shard-host-repo';

  /**
   * @return \string[]
   */
  public function getShardTypeNames();

  /**
   * @param \string[] $shardTypeNames
   * @return ShardMetadata
   */
  public function setShardTypeNames($shardTypeNames);

  /**
   * Check whether a name is a valid shard type name.
   *
   * @param string $name Name to check.
   * @return bool Is it valid?
   */
  public function isValidShardTypeName($name);

  /**
   * Get the valid view modes.
   *
   * @return string[] View modes
   */
  public function getViewModes();

  /**
   * @return array Allowed view modes.
   */
  public function getAllowedViewModes();

  /**
   * Check whether nid is valid.
   *
   * @param int $value Value to check.
   * @return bool True if valid, else false.
   */
  public function isValidNid($value);

  /**
   * Check whether view mode name is valid.
   *
   * @param string $value Value to check.
   * @return bool True if valid, else false.
   */
  public function isValidViewModeName($value);

  /**
   * Check whether content type name is valid.
   *
   * @param string $value Value to check.
   * @return bool True if valid, else false.
   */
  public function isValidContentTypeName($value);

  /**
   * Is a field name eligible for shards?
   *
   * @param string $fieldName Name of the field, e.g., body
   * @return bool True if the field is allowed.
   */
  public function isFieldEligible($fieldName);

    /**
   * Return a list of the names of fields that are allowed to have
   * shards in them.
   *
   * @param EntityInterface $node
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEligibleFieldsForNode(EntityInterface $node);

  /**
   * Return a list of the names of fields that are allowed to have
   * shards in them.
   *
   * @param string $bundleName Name of the bundle type, e.g., article.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEligibleFieldsForBundle($bundleName);

  public function nodeHasEligibleFields(NodeInterface $node);

  public function stashStringInConFig($key, $data);

  /**
   * @param $key
   * @return string
   */
  public function fetchStringFromConfig($key);
}