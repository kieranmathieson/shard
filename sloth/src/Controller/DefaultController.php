<?php

namespace Drupal\sloth\Controller;

use Drupal\Core\Controller\ControllerBase;
//use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\Entity;

/**
 * Class DefaultController.
 *
 * @package Drupal\sloth\Controller
 */
class DefaultController extends ControllerBase {

  /**
   * Sharts.
   *
   * @return string
   *   Return Hello string.
   */

  /* @var \Drupal\Core\Entity\Query\QueryFactory $entityQuery */
  protected $entityQuery;
  public function shards() {
    $this->entityQuery = \Drupal::service('entity.query');
    $query = $this->entityQuery->get('field_collection_item')
      ->condition('field_name', 'field_shard')
      ->condition('field_host_node', 2);
    $results = $query->execute();
    $entities = entity_load_multiple('field_collection_item', $results);
    $header = [
      $this->t('Shard id'),
      $this->t('Host id'),
    ];
    $rows = [];

    /* @var \Drupal\node\Entity\Node $entity  */
    foreach ($entities as $entity) {
      $rows[] = [
        $entity->id(),
        $entity->field_host_node->getValue()[0]['target_id'],
      ];
    }

    return [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
  }

  public function shardChange() {
    $this->entityQuery = \Drupal::service('entity.query');
    $query = $this->entityQuery->get('field_collection_item')
      ->condition('field_name', 'field_shard')
      ->condition('field_host_node', 2);
    $results = $query->execute();
    /* @var \Drupal\Core\Entity\Entity  $entity  */
    $entity = entity_load('field_collection_item', array_values($results)[0]);
    $field_host_node_value = $entity->get('field_host_node')->getValue();
    $field_host_node_value[0]['target_id'] = 666;
    $entity->get('field_host_node')->setValue($field_host_node_value);
    $entity->save();



    return [
      '#markup' => 'Butt'
    ];
  }

}
