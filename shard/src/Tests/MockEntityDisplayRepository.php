<?php
/**
 * @file
 * Mock object for testing.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;



class MockEntityDisplayRepository implements EntityDisplayRepositoryInterface {

  /**
   * Gets the entity view mode info for all entity types.
   *
   * @return array
   *   The view mode info for all entity types.
   */
  public function getAllViewModes() {
    // TODO: Implement getAllViewModes() method.
    return [];
  }

  /**
   * Gets the entity view mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode info should be returned.
   *
   * @return array
   *   The view mode info for a specific entity type.
   */
  public function getViewModes($entity_type_id) {
    return ['wobbly', 'shard'];
  }

  /**
   * Gets the entity form mode info for all entity types.
   *
   * @return array
   *   The form mode info for all entity types.
   */
  public function getAllFormModes() {
    // TODO: Implement getAllFormModes() method.
    return [];
  }

  /**
   * Gets the entity form mode info for a specific entity type.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode info should be returned.
   *
   * @return array
   *   The form mode info for a specific entity type.
   */
  public function getFormModes($entity_type_id) {
    // TODO: Implement getFormModes() method.
    return [];
  }

  /**
   * Gets an array of view mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode options should be returned.
   *
   * @return array
   *   An array of view mode labels, keyed by the display mode ID.
   */
  public function getViewModeOptions($entity_type_id) {
    // TODO: Implement getViewModeOptions() method.
    return [];
  }

  /**
   * Gets an array of form mode options.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode options should be returned.
   *
   * @return array
   *   An array of form mode labels, keyed by the display mode ID.
   */
  public function getFormModeOptions($entity_type_id) {
    // TODO: Implement getFormModeOptions() method.
    return [];
  }

  /**
   * Returns an array of enabled view mode options by bundle.
   *
   * @param string $entity_type_id
   *   The entity type whose view mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of view mode labels, keyed by the display mode ID.
   */
  public function getViewModeOptionsByBundle($entity_type_id, $bundle) {
    // TODO: Implement getViewModeOptionsByBundle() method.
    return [];
  }

  /**
   * Returns an array of enabled form mode options by bundle.
   *
   * @param string $entity_type_id
   *   The entity type whose form mode options should be returned.
   * @param string $bundle
   *   The name of the bundle.
   *
   * @return array
   *   An array of form mode labels, keyed by the display mode ID.
   */
  public function getFormModeOptionsByBundle($entity_type_id, $bundle) {
    // TODO: Implement getFormModeOptionsByBundle() method.
    return [];
  }

  /**
   * Clears the gathered display mode info.
   *
   * @return $this
   */
  public function clearDisplayModeInfo() {
    // TODO: Implement clearDisplayModeInfo() method.
    return $this;
  }
}