<?php
/**
 * @file
 * Unit tests for ShardTagModel. Functional tests are elsewhere.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\Tests\shard\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Drupal\shard\ShardModel;

use Drupal\shard\Exceptions\ShardUnexpectedValueException;

/**
 * AddClass units tests.
 *
 *
 * @ingroup shard
 *
 * @group shard
 */

class ShardTagModelTest extends UnitTestCase {

  function buildTagModelObject(){
    $databaseMock = $this->getMockBuilder('Drupal\Core\Database\Driver\mysql\Connection')
      ->disableOriginalConstructor()
      ->getMock();
    $metadataMock = $this->getMockBuilder('Drupal\shard\ShardMetadata')
      ->disableOriginalConstructor()
      ->getMock();
    $metadataMock->expects($this->any())->method('isValidNid')
      ->willReturnMap([
        [6, TRUE],
        [7, FALSE]
      ]);
    $metadataMock->expects($this->any())->method('isValidShardTypeName')
      ->willReturnMap([
        ['sloth', TRUE],
        ['cow', FALSE]
      ]);
    $entityTypeManagerMock = $this->getMockBuilder('Drupal\Core\Entity\EntityTypeManager')
      ->disableOriginalConstructor()
      ->getMock();
    $rendererMock = $this->getMockBuilder('Drupal\Core\Render\Renderer')
      ->disableOriginalConstructor()
      ->getMock();
    $domProcessorMock = $this->getMockBuilder('Drupal\shard\ShardDomProcessor')
      ->disableOriginalConstructor()
      ->getMock();
    $shardTagModel = new ShardModel(
      $databaseMock, $metadataMock, $entityTypeManagerMock, $rendererMock, $domProcessorMock
    );
    return $shardTagModel;
  }


  function testSetGetShardId() {
    $shardTagModel = $this->buildTagModelObject();
    $shardTagModel->setShardId(6);
    $this->assertTrue(
      $shardTagModel->getShardId() == 6,
      'testSetGetShardId: got expected value'
    );
    try {
      $shardTagModel->setShardId(7);
      $this->fail('testSetGetShardId: expected exception not thrown.');
    } catch (ShardUnexpectedValueException $e) {
      $this->assertTrue(TRUE, 'testSetGetShardId: expected exception thrown.');
    }
  }

  function testSetGetShardType() {
    $shardTagModel = $this->buildTagModelObject();
    $shardTagModel->setShardType('sloth');
    $this->assertTrue(
      $shardTagModel->getShardType() == 'sloth',
      'testSetGetShardType: got expected value'
    );
    try {
      $shardTagModel->setShardType('cow');
      $this->fail('testSetGetShardType: expected exception not thrown.');
    } catch (ShardUnexpectedValueException $e) {
      $this->assertTrue(TRUE, 'testSetGetShardType: expected exception thrown.');
    }
  }

  function testSetGetHostNid() {
    $shardTagModel = $this->buildTagModelObject();
    $shardTagModel->setHostNid(6);
    $this->assertTrue(
      $shardTagModel->getHostNid() == 6,
      'testSetGetHostNid: got expected value'
    );
    try {
      $shardTagModel->setHostNid(7);
      $this->fail('testSetGetHostNid: expected exception not thrown.');
    } catch (ShardUnexpectedValueException $e) {
      $this->assertTrue(TRUE, 'testSetGetHostNid: expected exception thrown.');
    }
  }

  /**
   * @TODO: put in functional test class.
   */
//  function testLoadHostNodeFromStorage() {
//
//  }

  function testSetGetGuestNid() {
    $shardTagModel = $this->buildTagModelObject();
    $shardTagModel->setGuestNid(6);
    $this->assertTrue(
      $shardTagModel->getGuestNid() == 6,
      'testSetGetGuestNid: got expected value'
    );
    try {
      $shardTagModel->setGuestNid(7);
      $this->fail('testSetGetGuestNid: expected exception not thrown.');
    } catch (ShardUnexpectedValueException $e) {
      $this->assertTrue(TRUE, 'testSetGetGuestNid: expected exception thrown.');
    }
  }


  function testSetGetDelta() {
    $shardTagModel = $this->buildTagModelObject();
    $shardTagModel->setDelta(0);
    $this->assertTrue(
      $shardTagModel->getDelta() == 0,
      'testSetGetDelta: Got expected value of 0.'
    );
    //Test bad value
    try {
      $shardTagModel->setDelta('cow');
      $this->fail('testSetGetDelta: expected exception not thrown.');
    } catch (ShardUnexpectedValueException $e) {
      $this->assertTrue(TRUE, 'testSetGetDelta: expected exception thrown for bad value.');
    }
  }

  /**
   * @TODO: put in functional test class.
   */
//  function testLoadGuestNodeFromStorage() {
//
//  }

  function testSetGetLocation() {
    $shardTagModel = $this->buildTagModelObject();
    $shardTagModel->setLocation(16);
    $this->assertTrue(
      $shardTagModel->getLocation() == 16,
      'testSetGetLocation: got expected value'
    );
    try {
      $shardTagModel->setLocation('goats');
      $this->fail('testSetGetLocation: expected exception not thrown.');
    } catch (ShardUnexpectedValueException $e) {
      $this->assertTrue(TRUE, 'testSetGetLocation: \'goats\': expected exception thrown.');
    }
    try {
      $shardTagModel->setLocation(-2);
      $this->fail('testSetGetLocation: expected exception not thrown.');
    } catch (ShardUnexpectedValueException $e) {
      $this->assertTrue(TRUE, 'testSetGetLocation: -2: expected exception thrown.');
    }
  }

  function testSetGetLocalContent() {
    $shardTagModel = $this->buildTagModelObject();
    $localContent1 = '<p>DOGS!</p>';
    $shardTagModel->setLocalContent($localContent1);
    $this->assertTrue(
      $shardTagModel->getLocalContent() == $localContent1,
      'testSetGetLocalContent: got expected value 1'
    );
    //Test MT string.
    $localContent2 = '';
    $shardTagModel->setLocalContent($localContent2);
    $this->assertTrue(
      $shardTagModel->getLocalContent() == $localContent2,
      'testSetGetLocalContent: got expected value 2'
    );

  }

}
