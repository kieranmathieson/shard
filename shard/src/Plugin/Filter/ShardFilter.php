<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 10/11/16
 * Time: 11:16 AM
 */

namespace Drupal\shard\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\shard\ShardFilteringEvent;
//use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @Filter(
 *   id = "filter_shard",
 *   title = @Translation("Shard Filter"),
 *   description = @Translation("Show shards."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 * )
 */
class ShardFilter extends FilterBase {

  /**
   * Used to interact with sharders (modules that implement shards).
   *
   * @var EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Performs the filter processing.
   *
   * @param string $html
   *   The HTML to be filtered.
   * @param string $langCode
   *   The language code of the HTML to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered HTML, wrapped in a FilterProcessResult object.
   *
   * @see \Drupal\filter\FilterProcessResult
   */
  public function process($html, $langCode) {
    $this->eventDispatcher = \Drupal::service('event_dispatcher');
    //Trigger sharders.
    $shardBeforeFilteringEvent = new ShardFilteringEvent($html);
    $this->eventDispatcher
      ->dispatch('shard.before_filtering', $shardBeforeFilteringEvent);
    $html = $shardBeforeFilteringEvent->getHtml();
    /* @var \Drupal\shard\ShardTagHandler $shardTagHandler */
    $shardTagHandler = \Drupal::service('shard.tag_handler');
    $html = $shardTagHandler->dbHtmlToViewHtml($html, $langCode);
    //Trigger sharders.
    $shardAfterFilteringEvent = new ShardFilteringEvent($html);
    $this->eventDispatcher
      ->dispatch('shard.after_filtering', $shardAfterFilteringEvent);
    $html = $shardAfterFilteringEvent->getHtml();
    return new FilterProcessResult($html, $langCode);
  }
}