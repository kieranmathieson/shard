<?php
/**
 * @file
 * Mock object for testing.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class MockEntityTypeManager implements EntityTypeManagerInterface {


  /**
   * Clears static and persistent plugin definition caches.
   *
   * Don't resort to calling \Drupal::cache()->delete() and friends to make
   * Drupal detect new or updated plugin definitions. Always use this method on
   * the appropriate plugin type's plugin manager!
   */
  public function clearCachedDefinitions() {
    // TODO: Implement clearCachedDefinitions() method.
  }

  /**
   * Disable the use of caches.
   *
   * Can be used to ensure that uncached plugin definitions are returned,
   * without invalidating all cached information.
   *
   * This will also remove all local/static caches.
   *
   * @param bool $use_caches
   *   FALSE to not use any caches.
   */
  public function useCaches($use_caches = FALSE) {
    // TODO: Implement useCaches() method.
  }

  /**
   * Indicates if a specific plugin definition exists.
   *
   * @param string $plugin_id
   *   A plugin ID.
   *
   * @return bool
   *   TRUE if the definition exists, FALSE otherwise.
   */
  public function hasDefinition($plugin_id) {
    // TODO: Implement hasDefinition() method.
  }

  /**
   * Creates a new access control handler instance.
   *
   * @param string $entity_type
   *   The entity type for this access control handler.
   *
   * @return \Drupal\Core\Entity\EntityAccessControlHandlerInterface.
   *   A access control handler instance.
   */
  public function getAccessControlHandler($entity_type) {
    // TODO: Implement getAccessControlHandler() method.
  }

  /**
   * Creates a new storage instance.
   *
   * @param string $entity_type
   *   The entity type for this storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   A storage instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getStorage($entity_type) {
    // TODO: Implement getStorage() method.
  }

  /**
   * Creates a new view builder instance.
   *
   * @param string $entity_type
   *   The entity type for this view builder.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface.
   *   A view builder instance.
   */
  public function getViewBuilder($entity_type) {


  }

  /**
   * Creates a new entity list builder.
   *
   * @param string $entity_type
   *   The entity type for this list builder.
   *
   * @return \Drupal\Core\Entity\EntityListBuilderInterface
   *   An entity list builder instance.
   */
  public function getListBuilder($entity_type) {
    // TODO: Implement getListBuilder() method.
  }

  /**
   * Creates a new form instance.
   *
   * @param string $entity_type
   *   The entity type for this form.
   * @param string $operation
   *   The name of the operation to use, e.g., 'default'.
   *
   * @return \Drupal\Core\Entity\EntityFormInterface
   *   A form instance.
   */
  public function getFormObject($entity_type, $operation) {
    // TODO: Implement getFormObject() method.
  }

  /**
   * Gets all route provider instances.
   *
   * @param string $entity_type
   *   The entity type for this route providers.
   *
   * @return \Drupal\Core\Entity\Routing\EntityRouteProviderInterface[]
   */
  public function getRouteProviders($entity_type) {
    // TODO: Implement getRouteProviders() method.
  }

  /**
   * Checks whether a certain entity type has a certain handler.
   *
   * @param string $entity_type
   *   The name of the entity type.
   * @param string $handler_type
   *   The name of the handler.
   *
   * @return bool
   *   Returns TRUE if the entity type has the handler, else FALSE.
   */
  public function hasHandler($entity_type, $handler_type) {
    // TODO: Implement hasHandler() method.
  }

  /**
   * Creates a new handler instance for a entity type and handler type.
   *
   * @param string $entity_type
   *   The entity type for this handler.
   * @param string $handler_type
   *   The handler type to create an instance for.
   *
   * @return object
   *   A handler instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getHandler($entity_type, $handler_type) {
    // TODO: Implement getHandler() method.
  }

  /**
   * Creates new handler instance.
   *
   * Usually \Drupal\Core\Entity\EntityManagerInterface::getHandler() is
   * preferred since that method has additional checking that the class exists
   * and has static caches.
   *
   * @param mixed $class
   *   The handler class to instantiate.
   * @param \Drupal\Core\Entity\EntityTypeInterface $definition
   *   The entity type definition.
   *
   * @return object
   *   A handler instance.
   */
  public function createHandlerInstance($class, EntityTypeInterface $definition = NULL) {
    // TODO: Implement createHandlerInstance() method.
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   */
  public function getDefinition($entity_type_id, $exception_on_invalid = TRUE) {
    // TODO: Implement getDefinition() method.
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   */
  public function getDefinitions() {
    // TODO: Implement getDefinitions() method.
  }

  /**
   * Creates a pre-configured instance of a plugin.
   *
   * @param string $plugin_id
   *   The ID of the plugin being instantiated.
   * @param array $configuration
   *   An array of configuration relevant to the plugin instance.
   *
   * @return object
   *   A fully configured plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the instance cannot be created, such as if the ID is invalid.
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // TODO: Implement createInstance() method.
  }

  /**
   * Gets a preconfigured instance of a plugin.
   *
   * @param array $options
   *   An array of options that can be used to determine a suitable plugin to
   *   instantiate and how to configure it.
   *
   * @return object|false
   *   A fully configured plugin instance. The interface of the plugin instance
   *   will depends on the plugin type. If no instance can be retrieved, FALSE
   *   will be returned.
   */
  public function getInstance(array $options) {
    // TODO: Implement getInstance() method.
  }
}