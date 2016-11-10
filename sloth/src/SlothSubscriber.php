<?php

/**
 * @file
 */

namespace Drupal\sloth;

use Drupal\shard\ShardPluginRegisterEvent;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Subscribes to the kernel request event to completely obliterate the default content.
 *
 *   The event to process.
 */
class SlothSubscriber implements EventSubscriberInterface {

  public function registerPlugin(ShardPluginRegisterEvent $event) {
      $event->registerPlugin('sloth');
  }

//  public function presave(ShardModifyContentEvent $event) {
//    $content = $event->getContent();
//    $content = str_replace('squirrel', 'sloth', $content);
//    $event->setContent($content);
//  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents(){
    $events['shard.register_plugins'][] = ['registerPlugin'];
//    $events['shard.presave'][] = array('presave');
    return $events;
  }

}