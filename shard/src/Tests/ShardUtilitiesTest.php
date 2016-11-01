<?php
/**
 * @file
 * Functional tests for ShardUtilities.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\user\Entity\User;
use Drupal\shard\ShardUtilities;

/**
 * Provides automated tests for the Shard class.
 *
 * @group shard
 */
class ShardUtilitiesTest extends WebTestBase {

  /**
   * Kent's uid.
   *
   * @var int $kentId
   */
  protected $kentId;

  public static $modules = array('shard', 'field', 'field_collection',
    'text', 'node');


  public function setUp() {
    parent::setUp();
    $this->drupalCreateRole(
      ['access content'],
      'evil'
    );
    $this->drupalCreateRole(
      ['access content'],
      'neutral'
    );
    $this->drupalCreateRole(
      ['access content'],
      'good'
    );
    /* @var \Drupal\user\Entity\User $kent  */
    $kent = $this->drupalCreateUser(
      ['access content'],
      'kent'
    );
    $kent->addRole('evil');
    $kent->save();
    $this->kentId = $kent->id();
  }

  public function testCurrentUserHasRole() {
    $kent = User::load($this->kentId);
    $this->setCurrentUser($kent);

    $this->assertTrue(
      ShardUtilities::currentUserHasRole('evil'),
      'testCurrentUserHasRole: Kent is evil, as expected.'
    );

    $this->assertFalse(
      ShardUtilities::currentUserHasRole('good'),
      'testCurrentUserHasRole: Kent is not good, as expected.'
    );

    $this->assertTrue(
      ShardUtilities::currentUserHasRole(['good', 'evil']),
      'testCurrentUserHasRole: Kent is good or evil, as expected.'
    );

    $this->assertFalse(
      ShardUtilities::currentUserHasRole(['good', 'neutral']),
      'testCurrentUserHasRole: Kent is neither good nor neutral, as expected.'
    );

  }



}