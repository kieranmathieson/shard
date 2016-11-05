<?php
/**
 * @file
 * Tests for ShardTagHandler.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;


use Drupal\shard\ShardTagHandler;
use Drupal\simpletest\WebTestBase;

class ShardTagHandlerTest extends WebTestBase {

  public static $modules = array('shard', 'field', 'field_collection',
    'text', 'node');

  public function setUp() {
    parent::setUp();

  }

  protected function createShardTagHander() {
    $shardTagHander = new ShardTagHandler(
      new MockMetadata(),
      new MockDomProcessor(),
      new MockEntityTypeManager(),
      new MockEntityDisplayRepository(),
      \Drupal::service('entity.query'),
      new MockRenderer(),
      \Drupal::service('event-dispatcher'),
      \Drupal::service('uuid')
    );
    return $shardTagHander;
  }

  public function testGroup1() {

  }

  protected function findFirstUnprocessedDbToCkShardTest(){
    $html = "
<body>
  <div data-shard-type='sloth' data-sloth-id='314' data-view-mode='shard' data-shard-processed='processed'>
    <div>Meow!</div>
    <div class='later'>
      <p>Now!</p>
      <h2>Things!</h2>
      <section>
        <div class='local-content'>
          <p>dogs</p>
        </div>
      </section>
    </div>
  </div>
  <div data-shard-type='sloth' data-sloth-id='666' data-view-mode='strange'>
    <div>Meow!</div>
    <div class='now'>
      <p>Then!</p>
      <h2>Things!</h2>
      <section>
        <div class='local-content'>
          <p>DOGS</p>
        </div>
      </section>
    </div>
  </div>
  <div data-shard-type='sloth' data-sloth-id='777' data-view-mode='grit' data-shard-processed='processed'>
    <div>Meow!</div>
    <div class='eventually'>
      <p>When?</p>
      <h2>Things!</h2>
      <section>
        <div class='local-content'>
          <p>puppies!</p>
        </div>
      </section>
    </div>
  </div>
</body>
";



  }
}