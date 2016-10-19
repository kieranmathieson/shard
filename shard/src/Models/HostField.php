<?php
/**
 * Models a field of a node that can host shards.
 */

namespace Drupal\shard\Models;

use Symfony\Component\DependencyInjection\ContainerInterface;

class HostField {

  /**
   * Machine name of the field.
   *
   * @var string
   */
  protected $machineName;

  /**
   * Values for this host field. Single-valued fields will just have one.
   *
   * @var HostFieldValue[]
   */
  protected $hostFieldValues = [];

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

}