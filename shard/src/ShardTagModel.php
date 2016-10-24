<?php
/**
 * Used when modeling the tags in a field when saving data, to get the
 * HTML conversion done from the most embedded tag upwards.
 *
 * One entry in a tree of shard tags is represented by a ShardTagModel.
 *
 * BODY-----T1
 *          |
 *          T2-----T3
 *          |
 *          T4-----T5
 *          |      |
 *          |      T6
 *          |
 *          T7
 *
 * T3 processed before T2. T5 and T6 before T4.
 *
 */

namespace Drupal\shard;


//use Drupal\shard\Exceptions\ShardMissingDataException;

class ShardTagModel {
  /**
   *
   * @var \DOMElement
   */
  protected $domElement;

  /**
   * Shard tags inside this tag
   *
   * @var ShardTagModel[]
   */
  protected $childTagModels = [];

//  protected $tagNumber = NULL;
//
//  protected $parentTagNumber = NULL;

  /**
   * @var ShardDomProcessor
   */
  static protected $domProcessor = NULL;

//  static protected $tagCount = 0;


  /**
   * Service with metadata about shards, e.g., eligible fields.
   *
   * @param \DOMElement $element
   * @param $shardTypeName
   * @internal param \Drupal\shard\ShardMetadataInterface $
   */
//  protected $metadata;

  public function __construct(\DOMElement $element) {
    if ( ! ShardTagModel::$domProcessor ) {
      ShardTagModel::$domProcessor = \Drupal::service('shard.dom_processor');
    }
    $this->domElement = $element;
  }

  /**
   * @return \Drupal\shard\ShardTagModel[]
   */
  public function getChildTagModels() {
    return $this->childTagModels;
  }

  public function buildTreeForElement() {
    $divs = $this->domElement->getElementsByTagName('div');
    while (TRUE) {
      $firstChildShardTagElement = ShardTagModel::$domProcessor->findFirstElementWithAttribute(
        $divs, ShardMetadata::SHARD_TYPE_TAG, '*');
    }
    $firstChildShardTagElement = ShardTagModel::$domProcessor->findFirstElementWithAttribute(
      $divs, ShardMetadata::SHARD_TYPE_TAG, '*');
    if ( $firstChildShardTagElement ) {
      $child = new ShardTagModel($firstChildShardTagElement);
//      ShardTagModel::$tagCount++;
//      $child->tagNumber = ShardTagModel::$tagCount;
//      $child->parentTagNumber = $this->tagNumber;
      $child->buildTreeForElement();
      $this->childTagModels[] = $child;
    }
  }


}