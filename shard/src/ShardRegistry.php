<?php
/**
 * Handles registry of shard plugins. The plugins must listen for
 * the event shard.register_plugins, and call $event->registerPlugin.
 * E.g.:
 *
 * $event->registerPlugin('sloth');
 */

namespace Drupal\shard;


class ShardRegistry {

  protected $registeredShardTypes;



}