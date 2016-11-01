<?php
/**
 * @file
 * Functional tests for ShardTagModel. Unit tests are elsewhere.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;

//use Drupal\Core\Entity\EntityDisplayRepository;
//use Drupal\shard\ShardDomProcessor;
//use Drupal\shard\ShardMetadata;
//use Drupal\node\Entity\Node;
//use Drupal\shard\ShardMetadata;
use Drupal\shard\ShardTagModel;
use Drupal\simpletest\WebTestBase;
//use Drupal\shard\Exceptions\ShardNotFoundException;
//use Drupal\shard\Exceptions\ShardUnexpectedValueException;
use Symfony\Component\DependencyInjection\Container;
//use Drupal\Core\Entity\Entity;
/**
 * Provides automated tests for the Shard class.
 *
 * @group shard
 */
class ShardTagModelTest extends WebTestBase {

  /* @var Container $container */
  protected $container;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public static $modules = array('shard', 'field', 'field_collection',
    'text', 'node');

  function setUp() {
    parent::setUp();
    $this->container = \Drupal::getContainer();
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
  }

  function makeShardTagModel(){
    $shardTagModel = new ShardTagModel(
      \Drupal::service('database'),
      new MockMetadata(),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('renderer'),
      \Drupal::service('shard.dom_processor')
    );
    return $shardTagModel;
  }

  function testLoadHostNodeFromStorage() {
    /* @var \Drupal\node\NodeInterface $page */
    $page = $this->createNode([
      'title' => 'DOG',
      'type' => 'page'
    ]);
    $newNodeId = $page->id();
    $this->assertTrue(
      is_numeric($newNodeId),
      'testLoadHostNodeFromStorage: Node created with id ' . $newNodeId
    );
    $shardTagModel = $this->makeShardTagModel();
    $shardTagModel->setHostNid($newNodeId);
    /* @var \Drupal\Core\Entity\EntityInterface $hostNode */
    $hostNode = $shardTagModel->loadHostNodeFromStorage();
    $correct =
      ($hostNode->id() == $newNodeId)
      && ($hostNode->getEntityTypeId() == 'node')
      && ($hostNode->bundle() == 'page');
    $this->assertTrue($correct,
      'testLoadHostNodeFromStorage: got expected node.');
//    //A bad nid.
//    try {
//      $shardTagModel->setHostNid(6);
//      $hostNode = $shardTagModel->loadHostNodeFromStorage();
//      $this->fail('testLoadHostNodeFromStorage: did not get expected exception.');
//    } catch (ShardNotFoundException $e) {
//      $this->pass('testLoadHostNodeFromStorage: got expected exception.');
//    }
  }

  function testLoadGuestNodeFromStorage() {
    /* @var \Drupal\node\NodeInterface $page */
    $page = $this->createNode([
      'title' => 'DOG',
      'type' => 'page'
    ]);
    $newNodeId = $page->id();
    $this->assertTrue(
      is_numeric($newNodeId),
      'testLoadGuestNodeFromStorage: Node created with id ' . $newNodeId
    );
    $shardTagModel = $this->makeShardTagModel();
    $shardTagModel->setGuestNid($newNodeId);
    /* @var \Drupal\Core\Entity\EntityInterface $guestNode */
    $guestNode = $shardTagModel->loadGuestNodeFromStorage();
    $correct =
      ($guestNode->id() == $newNodeId)
      && ($guestNode->getEntityTypeId() == 'node')
      && ($guestNode->bundle() == 'page');
    $this->assertTrue($correct,
      'testLoadGuestNodeFromStorage: got expected node.');
//    //A bad nid.
//    try {
//      $shardTagModel->setGuestNid(6);
//      $guestNode = $shardTagModel->loadGuestNodeFromStorage();
//      $this->fail('testLoadGuestNodeFromStorage: did not get expected exception.');
//    } catch (ShardNotFoundException $e) {
//      $this->pass('testLoadGuestNodeFromStorage: got expected exception.');
//    }
  }

  function testGetSetShardType() {
    $shardTagModel = $this->makeShardTagModel();
    $shardTagModel->setShardType('sloth');
    $this->assertEqual(
      $shardTagModel->getShardType(),
      'sloth',
      'testGetSetShardType: got expected shard type'
    );
  }

  function testGetSetHostFieldName() {
    $shardTagModel = $this->makeShardTagModel();
    $shardTagModel->setHostFieldName('body');
    $this->assertEqual(
      $shardTagModel->getHostFieldName(),
      'body',
      'testGetSetHostFieldName: got expected field name'
    );
  }

  function testGetSetViewMode() {
    $shardTagModel = $this->makeShardTagModel();
    $shardTagModel->setViewMode('peekaboo');
    $this->assertEqual(
      $shardTagModel->getViewMode(),
      'peekaboo',
      'testGetSetViewMode: got expected view mode'
    );
  }

}