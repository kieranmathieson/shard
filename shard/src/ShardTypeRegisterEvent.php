<?php
/**
 * @file
 * Object passed around when registering shard types.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Symfony\Component\EventDispatcher\Event;

class ShardTypeRegisterEvent extends Event {

  /**
   * Names of modules that implement shard types.
   *
   * @var string[]
   */
  protected $modulesImplementingShardTypes = [];

  public function __construct() {
    $this->modulesImplementingShardTypes = [];
  }

  /**
   * @return mixed
   */
  public function getRegisteredShardTypes() {
    return $this->modulesImplementingShardTypes;
  }

  /**
   * Called by a shard type module to register its shardiness.
   *
   * @param string $moduleName Module name.
   * @return $this
   */
  public function registerShardType($moduleName) {
    $this->modulesImplementingShardTypes[] = $moduleName;
    return $this;
  }

}