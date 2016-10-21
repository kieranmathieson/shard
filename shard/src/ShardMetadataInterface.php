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

interface ShardMetadataInterface {

  /**
   * Placeholder value showing that a nid is unknown.
   *
   * Null won't work as well, since many functions use null as a no-result
   * indicator. Using a specific value distinguishes between those fallback
   * cases, and the situation where we deliberately say that the nid is
   * unknown.
   *
   * @var int
   */
  const UNKNOWN = -666;

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
  public function isValidViewMode($value);

  /**
   * Check whether content type name is valid.
   *
   * @param string $value Value to check.
   * @return bool True if valid, else false.
   */
  public function isValidContentType($value);

  /**
   * Return a list of the names of fields that are allowed to have
   * sloth shards in them.
   *
   * @param EntityInterface $entity Entity with the fields.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEntityEligibleFields(EntityInterface $entity);

  /**
   * Return a list of the names of fields that are allowed to have
   * sloth shards in them.
   *
   * @param string $entity_type_name Name of the entity type, e.g., node.
   * @param string $bundle_name Name of the bundle type, e.g., article.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEligibleFields($entity_type_name, $bundle_name);


}