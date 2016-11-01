<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 10/31/16
 * Time: 5:37 PM
 */

namespace Drupal\shard\Tests;


use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryFactoryInterface;

class MockQueryFactory implements QueryFactoryInterface {

  /**
   * Instantiates an entity query for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   The operator to use to combine conditions: 'AND' or 'OR'.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query for a specific configuration entity type.
   */
  public function get(EntityTypeInterface $entity_type, $conjunction) {
    // TODO: Implement get() method.
  }

  /**
   * Instantiates an aggregation query object for a given entity type.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $conjunction
   *   - AND: all of the conditions on the query need to match.
   *   - OR: at least one of the conditions on the query need to match.
   *
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   *   The query object that can query the given entity type.
   *
   * @throws \Drupal\Core\Entity\Query\QueryException
   */
  public function getAggregate(EntityTypeInterface $entity_type, $conjunction) {
    // TODO: Implement getAggregate() method.
  }
}