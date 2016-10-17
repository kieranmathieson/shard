<?php
/**
 * @file
 * Defines a class that reports fields that are allowed to host shards, according to site
 * configuration.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\sloth\Services;

use Drupal\Core\Entity\EntityInterface;

interface EligibleFieldsInterface {

  /**
   * Return a list of the names of fields for an entity that are allowed to have
   * shards in them.
   *
   * @param EntityInterface $entity Entity with the fields.
   * @return string[] Names of fields that may have sloths embedded.
   */  public function listEntityEligibleFields(EntityInterface $entity);

  /**
   * Return a list of the names of fields that are allowed to have
   * shards in them.
   *
   * @param string $entity_type_name Name of the entity type, e.g., node.
   * @param string $bundle_name Name of the bundle type, e.g., article.
   * @return string[] Names of fields that may have sloths embedded.
   */
  public function listEligibleFields($entity_type_name, $bundle_name);
}