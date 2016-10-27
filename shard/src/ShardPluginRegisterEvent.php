<?php
/**
 * @file
 * Register shard plugins.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Symfony\Component\EventDispatcher\Event;

class ShardPluginRegisterEvent extends Event {
  protected $modulesWithPlugins = [];

  public function __construct() {
    $this->modulesWithPlugins = [];
  }

  /**
   * @return mixed
   */
  public function getRegisteredPlugins() {
    return $this->modulesWithPlugins;
  }

  /**
   * @param $moduleName
   * @return $this
   */
  public function registerPlugin($moduleName) {
    $this->modulesWithPlugins[] = $moduleName;
    return $this;
  }

}