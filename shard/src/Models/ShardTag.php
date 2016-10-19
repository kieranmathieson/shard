<?php
/**
 * @file
 * Models a shard tag in a field instance. There are three formats:
 *
 * * DB - as the tag appears in the database.
 * * CK - as the tag appears in CKEditor.
 * * View - as the tag appears in a page view of the host.
 *
 * See the documentation for details about each format.
 *
 * @version Kieran Mathieson
 *
 */

namespace Drupal\shard\Models;

use Symfony\Component\DependencyInjection\ContainerInterface;

class ShardTag {

  const SHARD_TYPE_ATTRIBUTE = 'data-shard-type';

  /**
   * Shard type, e.g., sloth. Used in all three formats.
   *
   * @var string
   */
  protected $shardType;

  /**
   * The nid of the host node.
   *
   * @var int
   */
  protected $hostNid;


  protected $shardId;

  /**
   * Service supplying metadata about nodes and fields.
   *
   * @var \Drupal\shard\Models\NodeMetadataInterface
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

}