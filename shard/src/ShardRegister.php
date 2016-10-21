<?php

/**
 * Created by PhpStorm.
 * Date: 10/21/16
 * Time: 8:18 AM
 */

namespace Drupal\shard;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShardRegister {

  protected $eventDispatcher;
  protected $plugins;

  /**
   * @return mixed
   */
  public function getPlugins() {
    return $this->plugins;
  }

  public function __construct(ContainerAwareEventDispatcher $eventDispatcher) {
    $this->eventDispatcher = $eventDispatcher;
    $pluginRegisterEvent = new ShardPluginRegisterEvent();
    $this->eventDispatcher->dispatch('shard.register_plugins', $pluginRegisterEvent);
    $this->plugins = $pluginRegisterEvent->getRegisteredPlugins();
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher')
    );
  }


}