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
use Drupal\shard\ShardTagHandler;

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
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langCode
   *   The language code of the text to be filtered.
   *
   * @return \Drupal\filter\FilterProcessResult
   *   The filtered text, wrapped in a FilterProcessResult object.
   *
   * @see \Drupal\filter\FilterProcessResult
   */
  public function process($text, $langCode) {
    $container = \Drupal::getContainer();
    $shardTagHandler = new ShardTagHandler(
      $container->get('shard.metadata'),
      $container->get('shard.dom_processor'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('renderer'),
      $container->get('event_dispatcher'),
      $container->get('uuid')
    );
    $text = $shardTagHandler->dbHtmlToViewHtml($text, $langCode);
    return new FilterProcessResult($text, $langCode);
  }
}