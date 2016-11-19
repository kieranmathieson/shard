<?php

/**
 * @file
 */

namespace Drupal\sloth;

use Drupal\shard\ShardFilteringEvent;
use Drupal\shard\ShardTypeRegisterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class SlothSubscriber implements EventSubscriberInterface {

  /**
   * Registers a sloth shard type.
   * @param \Drupal\shard\ShardTypeRegisterEvent $event
   */
  public function registerShardType(ShardTypeRegisterEvent $event) {
    $event->registerShardType('sloth');
  }

  /**
   * Run before shard has prepared a field for viewing.
   *
   * @param \Drupal\shard\ShardFilteringEvent $event
   */
  public function beforeFiltering(ShardFilteringEvent $event) {
    $html = $event->getHtml();
    $html = '<h2>Sloths are evil!</h2>' . $html;
    $event->setHtml($html);
  }

  /**
   * Run after shard has prepared a field for viewing.
   *
   * @param \Drupal\shard\ShardFilteringEvent $event
   */
  public function afterFiltering(ShardFilteringEvent $event) {
    $html = $event->getHtml();
    $html .= '<h2>Evil, I tell \'e! Evil!</h2>';
    $event->setHtml($html);
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents(){
    $events['shard.register_shard_types'][] = ['registerShardType'];
    $events['shard.before_filtering'][] = ['beforeFiltering'];
    $events['shard.after_filtering'][] = ['afterFiltering'];
    return $events;
  }

}
