<?php
/**
 * @file
 * Register shard plugins.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Symfony\Component\EventDispatcher\Event;

class ShardTranslationEvent extends Event {

  protected $eventName = NULL;
  protected $shardTypeName = NULL;
  protected $domElement = NULL;
  protected $domDocument = NULL;
  protected $shardId = NULL;
  protected $hostNid = NULL;
  protected $guestNid = NULL;
  protected $viewMode = NULL;
  protected $fieldName = NULL;
  protected $delta = NULL;
  protected $location = NULL;
  protected $localContent = NULL;

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