<?php
/**
 * @file
 * Functional tests for ShardUtilities.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;

use Drupal\shard\ShardMetadata;
use Drupal\simpletest\WebTestBase;
//use Drupal\Core\Entity\Query\QueryFactory;
//use Drupal\user\Entity\User;
//use Drupal\shard\ShardUtilities;

/**
 * Provides automated tests for the Shard class.
 *
 * @group shard
 */
class ShardMetadataTest extends WebTestBase {

  /**
   * Kent's uid.
   *
   * @var int $kentId
   */
  protected $kentId;

  public static $modules = array('shard', 'field', 'field_collection',
    'text', 'node');

  /**
   * Ids of created nodes.
   * @var int[]
   */
  protected $nodeIds;

  public function setUp() {
    parent::setUp();
    //Set up some dummy nodes. Wouldn't need to do this if
    //Drupal\Core\Entity\Query\QueryFactory implemented
    //QueryFactoryInterface, but it doesn't! Boo!
    /* @var \Drupal\node\NodeInterface $page */
    $page = $this->createNode([
      'title' => 'DOG',
      'type' => 'page'
    ]);
    $this->nodeIds[] = $page->id();
    $page = $this->createNode([
      'title' => 'LLAMA',
      'type' => 'page'
    ]);
    $this->nodeIds[] = $page->id();
    $page = $this->createNode([
      'title' => 'FROG',
      'type' => 'page'
    ]);
    $this->nodeIds[] = $page->id();
//    $this->drupalCreateRole(
//      ['access content'],
//      'evil'
//    );
//    $this->drupalCreateRole(
//      ['access content'],
//      'neutral'
//    );
//    $this->drupalCreateRole(
//      ['access content'],
//      'good'
//    );
//    /* @var \Drupal\user\Entity\User $kent  */
//    $kent = $this->drupalCreateUser(
//      ['access content'],
//      'kent'
//    );
//    $kent->addRole('evil');
//    $kent->save();
//    $this->kentId = $kent->id();
  }

  protected function createMetadataObject() {
    $metadata = new ShardMetadata(
      new MockEntityDisplayRepository(),
      new MockEntityTypeBundleInfo(),
      new MockConfigFactory(),
      new MockEntityFieldManager(),
      new MockEventDispatcher()
    );
    return $metadata;
  }

  public function testGroup1(){
    $this->setGetTypeNamesTest();
    $this->isValidTypeNameTest();
    $this->isValidNidTest();
  }

  public function setGetTypeNamesTest() {
    $metadata = $this->createMetadataObject();
    $shardTypes = ['sloth', 'page'];
    $metadata->setShardTypeNames($shardTypes);
    $this->assertEqual(
      $metadata->getShardTypeNames(),
      $shardTypes,
      'testSetGetTypeNames: Got back expected shard type names.'
    );

  }

  public function isValidTypeNameTest() {
    $metadata = $this->createMetadataObject();
    $shardTypes = ['sloth', 'page'];
    $metadata->setShardTypeNames($shardTypes);
    $this->assertTrue(
      $metadata->isValidShardTypeName('sloth'),
      'testIsValidTypeName: sloth is valid shard type, as expected.'
    );
    $this->assertTrue(
      $metadata->isValidShardTypeName('page'),
      'testIsValidTypeName: page is valid shard type, as expected.'
    );
    $this->assertFalse(
      $metadata->isValidShardTypeName('road'),
      'testIsValidTypeName: road is note valid shard type, as expected.'
    );
  }

  public function isValidNidTest() {
    $metadata = $this->createMetadataObject();
    $this->assertTrue(
      $metadata->isValidNid($this->nodeIds[0]),
      'testIsValidNid: nid ' . $this->nodeIds[0] . ' valid, as expected.'
    );
    $uuidMaker = new \Drupal\Component\Uuid\Php();
    $uuid = $uuidMaker->generate();
    $this->assertTrue(
      $metadata->isValidNid($uuid),
      'testIsValidNid: nid ' . $uuid . ' valid, as expected.'
    );
    $this->assertFalse(
      $metadata->isValidNid('cat'),
      'testIsValidNid: nid cat not valid, as expected.'
    );
    $this->assertFalse(
      $metadata->isValidNid(-666),
      'testIsValidNid: nid -666 not valid, as expected.'
    );
    $highestNid = max($this->nodeIds);
    $badNid = $highestNid + 666;
    $this->assertFalse(
      $metadata->isValidNid($badNid),
      'testIsValidNid: nid ' . $badNid . ' not valid, as expected.'
    );
  }


//    $kent = User::load($this->kentId);
//    $this->setCurrentUser($kent);
//
//    $this->assertTrue(
//      ShardUtilities::currentUserHasRole('evil'),
//      'testCurrentUserHasRole: Kent is evil, as expected.'
//    );
//
//    $this->assertFalse(
//      ShardUtilities::currentUserHasRole('good'),
//      'testCurrentUserHasRole: Kent is not good, as expected.'
//    );
//
//    $this->assertTrue(
//      ShardUtilities::currentUserHasRole(['good', 'evil']),
//      'testCurrentUserHasRole: Kent is good or evil, as expected.'
//    );
//
//    $this->assertFalse(
//      ShardUtilities::currentUserHasRole(['good', 'neutral']),
//      'testCurrentUserHasRole: Kent is neither good nor neutral, as expected.'
//    );


}