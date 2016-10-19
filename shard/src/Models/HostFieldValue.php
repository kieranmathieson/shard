<?php
/**
 * @file
 * Models a value of a host field. Fields can have more than one.
 */

namespace Drupal\shard\Models;

use Symfony\Component\DependencyInjection\ContainerInterface;

class HostFieldValue {

  /**
   * The host field this belongs to.
   *
   * @var HostField
   */
  protected $hostField;

  /**
   * Shard tags in this field value.
   *
   * @var ShardTag[]
   */
  protected $shardTags;

  /**
   * This field value's DOMDocument. All manipulation on the field value
   * should be done with this DOMDocument.
   *
   * @var \DOMDocument
   */
  protected $domDocument;

  /**
   * Value's HTML, with shard tags in CKEditor format.
   *
   * @var string
   */
  protected $ckHtml = null;

  /**
   * Value's HTML, with shard tags in DB format.
   *
   * @var string
   */
  protected $dbHtml = null;

  /**
   * Value's HTML, with shard tags in view format.
   *
   * @var string
   */
  protected $viewHtml = null;

  /**
   * Shards referencing this field value.
   *
   * @var ShardFieldCollectionItem[]
   */
  protected $referencingShards = [];

  /**
   * Service supplying metadata about nodes and fields.
   *
   * @var \Drupal\shard\Models\NodeMetadataInterface
   */
  protected $nodeMetaData;


  public function __construct(NodeMetadataInterface $node_meta_data) {
    $this->nodeMetadata = $node_meta_data;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shard.node_metadata')
    );
  }

  /**
   * @return \Drupal\shard\Models\HostField
   */
  public function getHostField() {
    return $this->hostField;
  }

  /**
   * @param \Drupal\shard\Models\HostField $hostField
   * @return HostFieldValue
   */
  public function setHostField(HostField $hostField) {
    $this->hostField = $hostField;
    return $this;
  }

  /**
   * @return string
   */
  public function getCkHtml() {
    return $this->ckHtml;
  }

  /**
   * @param string $ckHtml
   * @return HostFieldValue
   */
  public function setCkHtml($ckHtml) {
    $this->ckHtml = $ckHtml;
    return $this;
  }

  /**
   * @return string
   */
  public function getDbHtml() {
    return $this->dbHtml;
  }

  /**
   * @param string $dbHtml
   * @return HostFieldValue
   */
  public function setDbHtml($dbHtml) {
    $this->dbHtml = $dbHtml;
    return $this;
  }

  /**
   * @return string
   */
  public function getViewHtml() {
    return $this->viewHtml;
  }

  /**
   * @param string $viewHtml
   * @return HostFieldValue
   */
  public function setViewHtml($viewHtml) {
    $this->viewHtml = $viewHtml;
    return $this;
  }

  public function addShard(Shard $shard) {
    if ( $shard->getHostNid() != $this->getNid() ) {
      throw new ShardUnexpectedValueException(
        sprintf(
          'Shard host nid of %u does not match node nid of %u.',
          $shard->getHostNid(),
          $this->getNid()
        )
      );
    }
    if ( ! in_array($shard->getGuestFieldName(), $this->validGuestFields) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Unknown guest field name: %s', $shard->getGuestFieldName())
      );
    }
    if ( ! $this->isValidViewMode($shard->getViewMode()) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Unknown view mode: %s', $shard->getViewMode())
      );
    }
    $this->guestShards[] = $shard;
    return $this;
  }


}