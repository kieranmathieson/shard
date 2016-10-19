<?php
/**
 * @file
 * Represents a node, either a host node, or a guest node.
 */

namespace Drupal\shard\Models;

use Drupal\shard\Exceptions\ShardUnexpectedValueException;
use Symfony\Component\DependencyInjection\ContainerInterface;



class Node {

  /**
   * This node's id.
   *
   * @var int
   */
  protected $nid;

  /**
   * Name of this node's content type.
   * @var string
   */
  protected $contentType = NULL;

  /**
   * Service supplying metadata about nodes and fields.
   *
   * @var \Drupal\shard\Models\NodeFieldMetadataInterface
   */
  protected $nodeMetadata;

  public function __construct(NodeMetadataInterface $node_meta_data) {
    $this->nodeMetadata = $node_meta_data;
    $this->nid = NodeMetaData::UNKNOWN;
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
   * @return int
   */
  public function getNid() {
    return $this->nid;
  }

  /**
   * @param int $nid
   * @return $this For chaining.
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setNid($nid) {
    if ( ! $this->nodeMetadata->isValidNid($nid) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Nid not valid: %s', $nid)
      );
    }
    $this->nid = $nid;
    return $this;
  }

  /**
   * @return null
   */
  public function getContentType() {
    return $this->contentType;
  }

  /**
   * @param string $content_type
   * @return $this For chaining.
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setContentType($content_type) {
    if ( ! $this->nodeMetadata->isValidContentType($content_type) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Content type not valid: %s', $content_type)
      );
    }
    $this->contentType = $content_type;
    return $this;
  }

}