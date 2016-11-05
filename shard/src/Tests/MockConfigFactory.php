<?php
/**
 * @file
 * Mock object for testing.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;

class MockConfigFactory implements ConfigFactoryInterface {

  /**
   * Returns an immutable configuration object for a given name.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   *
   * @return MockImmutableConfig
   *   A configuration object.
   */
  public function get($name) {
    if ( $name == 'shard.settings' ) {
      return new MockImmutableConfig();
    }
    return NULL;
  }

  /**
   * Returns an mutable configuration object for a given name.
   *
   * Should not be used for config that will have runtime effects. Therefore it
   * is always loaded override free.
   *
   * @param string $name
   *   The name of the configuration object to construct.
   *
   * @return \Drupal\Core\Config\Config
   *   A configuration object.
   */
  public function getEditable($name) {
    // TODO: Implement getEditable() method.
  }

  /**
   * Returns a list of configuration objects for the given names.
   *
   * This will pre-load all requested configuration objects does not create
   * new configuration objects. This method always return immutable objects.
   * ConfigFactoryInterface::getEditable() should be used to retrieve mutable
   * configuration objects, one by one.
   *
   * @param array $names
   *   List of names of configuration objects.
   *
   * @return \Drupal\Core\Config\ImmutableConfig[]
   *   List of successfully loaded configuration objects, keyed by name.
   */
  public function loadMultiple(array $names) {
    // TODO: Implement loadMultiple() method.
  }

  /**
   * Resets and re-initializes configuration objects. Internal use only.
   *
   * @param string|null $name
   *   (optional) The name of the configuration object to reset. If omitted, all
   *   configuration objects are reset.
   *
   * @return $this
   */
  public function reset($name = NULL) {
    // TODO: Implement reset() method.
    return $this;
  }

  /**
   * Renames a configuration object using the storage.
   *
   * @param string $old_name
   *   The old name of the configuration object.
   * @param string $new_name
   *   The new name of the configuration object.
   *
   * @return $this
   */
  public function rename($old_name, $new_name) {
    // TODO: Implement rename() method.
    return $this;
  }

  /**
   * The cache keys associated with the state of the config factory.
   *
   * All state information that can influence the result of a get() should be
   * included. Typically, this includes a key for each override added via
   * addOverride(). This allows external code to maintain caches of
   * configuration data in addition to or instead of caches maintained by the
   * factory.
   *
   * @return array
   *   An array of strings, used to generate a cache ID.
   */
  public function getCacheKeys() {
    // TODO: Implement getCacheKeys() method.
    return [];
  }

  /**
   * Clears the config factory static cache.
   *
   * @return $this
   */
  public function clearStaticCache() {
    // TODO: Implement clearStaticCache() method.
    return $this;
  }

  /**
   * Gets configuration object names starting with a given prefix.
   *
   * @see \Drupal\Core\Config\StorageInterface::listAll()
   *
   * @param string $prefix
   *   (optional) The prefix to search for. If omitted, all configuration object
   *   names that exist are returned.
   *
   * @return array
   *   An array containing matching configuration object names.
   */
  public function listAll($prefix = '') {
    // TODO: Implement listAll() method.
    return [];
  }

  /**
   * Adds config factory override services.
   *
   * @param \Drupal\Core\Config\ConfigFactoryOverrideInterface $config_factory_override
   *   The config factory override service to add. It is added at the end of the
   *   priority list (lower priority relative to existing ones).
   */
  public function addOverride(ConfigFactoryOverrideInterface $config_factory_override) {
    // TODO: Implement addOverride() method.
  }
}