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
    $shardTagHandler = \Drupal::service('shard.tag_handler');
    $text = $shardTagHandler->dbHtmlToViewHtml($text, $langCode);
    return new FilterProcessResult($text, $langCode);
  }
}