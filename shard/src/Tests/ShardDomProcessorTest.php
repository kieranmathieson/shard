<?php
/**
 * @file
 * Tests for ShardDomProcessor
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;

//use Drupal\Core\Entity\EntityDisplayRepository;
//use Drupal\shard\ShardDomProcessor;
//use Drupal\shard\ShardMetadata;
use Drupal\simpletest\WebTestBase;
use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;

/**
 * Provides automated tests for the ShardDomProcessor class.
 *
 * @group shard
 */
class ShardDomProcessorTest extends WebTestBase {

  /**
   * Object to test.
   *
   * @var \Drupal\shard\ShardDomProcessor
   */
  protected $domProcessor;

  protected $metadata;

  public static $modules = array('shard', 'field', 'field_collection',
    'text', 'node');


  public function testGroup1() {
    $this->domProcessor = \Drupal::service('shard.dom_processor');
    $this->createDomDocumentFromHtmlTest();
    $this->duplicateAttributesTest();
    $this->findLocalContentTest();
    $this->findFirstWithAttributeTest();
    $this->findFirstWithClassTest();
    $this->findFirstUnprocessedShardTagTest();
    $this->findLocalContentContainerInDocTest();
    $this->getInnerHtmlTest();
    $this->getOuterHtmlTest();
    $this->getRequiredAttributeTest();
    $this->isElementShardTest();
    $this->isKnownShardTypeTest();
    $this->isShardElementProcessedTest();
    $this->markShardAsProcessedTest();
    $this->removeElementChildrenTest();
    $this->replaceElementChildrenTest();
    $this->replaceElementContentsTest();
    $this->reportUnknownShardTypeTest();
    $this->stripElementAttributesTest();
  }

  public function createDomDocumentFromHtmlTest() {
    $html = "
<body>
  <p>
    Sloths are coming!
  </p>
  <p>
    RUN!
  </p>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div>Meow!</div>
  </div>
</body>
";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $result = $doc->getElementsByTagName('body')->item(0)->C14N();
    $this->assertTrue(
      TestUtilities::checkHtmlSame($html, $result),
      "createDomDocumentFromHtmlTest: Found the right HTML."
    );
  }

  public function duplicateAttributesTest() {

    $html = "<body><div data-thing='6' class='r'>schwein</div><p>I will <span>die</span></p><div>hund</div></body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $from = $doc->getElementsByTagName('div')->item(0);
    $to = $doc->getElementsByTagName('div')->item(1);
    $this->domProcessor->duplicateElementAttributes($from, $to);
    //Check that the attributes are the same.
    $this->assertTrue(TestUtilities::checkAttributesAreSame($from, $to),
      "duplicateAttributes: Attributes copied, as expected.");
  }


  public function findLocalContentTest() {

    $html = "
<body><div data-thing='6' class='r'>schwein</div><p>I will <span>die</span></p><div>hund</div>
<div class='local-content'>DOG</div>
</body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $body = $doc->getElementsByTagName('body')->item(0);
    /* @var \DOMElement $div */
    $div = $this->domProcessor->findElementWithLocalContent($body);
    $this->assertEqual(
      TestUtilities::normalizeString($div->C14N()),
      TestUtilities::normalizeString("<div class='local-content'>DOG</div>"),
      'findLocalContentTest:Found expected local content tag.');


    $html = "
<body>
  <div data-thing='6' class='r'>
    schwein
    <div>
      <p>COWS are strange.</p>
      <div class='local-content'>WOOF</div>
    </div>
  </div>
  <p>I will <span>die</span></p>
  <div>hund</div>

</body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $body = $doc->getElementsByTagName('body')->item(0);
    /* @var \DOMElement $div */
    $div = $this->domProcessor->findElementWithLocalContent($body);
    $this->assertEqual($div->textContent, 'WOOF',
      'findLocalContentContainerInDoc:Found expected local content when deeply nested.');
  }


  public function findFirstWithAttributeTest() {

    $html = "
<body>
  <p>
    Sloths are coming!
  </p>
  <p>
    RUN!
  </p>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div data-thing='44'>Meow!</div><div class='local-content'>DOGS!</div>
  </div>
</body>
";
    $expected = "<div data-thing='44'>Meow!</div>";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->domProcessor->findFirstElementWithAttribute($elements, 'data-thing', 44);
    $this->assertEqual(
      TestUtilities::normalizeString($first->C14N()),
      TestUtilities::normalizeString($expected),
      "findFirstWithAttribute: Found  element, simple test."
    );

    $expected = "<span data-best-animal='this-one'>dogs</span>";
    $html = "
<body>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div>Meow!</div>
    <div class='later'>
      <p>Now!</p>
      <h2>Things!</h2>
      <section>
        <div class='local-content'>
          <p>" . $expected . "</p>
          <div>Woof!</div>
        </div>
      </section>
    </div>
  </div>
</body>
";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->domProcessor->findFirstElementWithAttribute($elements,
      'data-best-animal', 'this-one');
    $this->assertEqual(
      TestUtilities::normalizeString($first->C14N()),
      TestUtilities::normalizeString($expected),
      "findFirstWithAttribute: Found element, nested."
    );


    $expected = "<span data-best-animal='this-one'>dogs</span>";
    $html = "
<body>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div>Meow!</div>
    <div class='later'>
      <p>Now!</p>
      <h2>Things!</h2>
      <section>
        <div class='local-content'>
          <p>" . $expected . "</p>
          <div>Woof!</div>
        </div>
      </section>
    </div>
  </div>
</body>
";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->domProcessor->findFirstElementWithAttribute($elements,
      'data-nowt', 'this-one');
    $this->assertFalse(
      $first,
      "findFirstWithAttribute: Didn't find element, as expected. Wrong attribute name."
    );

    $expected = "<span data-best-animal='this-one'>dogs</span>";
    $html = "
<body>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div>Meow!</div>
    <div class='later'>
      <p>Now!</p>
      <h2>Things!</h2>
      <section>
        <div class='local-content'>
          <p>" . $expected . "</p>
          <div>Woof!</div>
        </div>
      </section>
    </div>
  </div>
</body>
";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->domProcessor->findFirstElementWithAttribute($elements,
      'data-best-animal', 'that-one');
    $this->assertFalse(
      $first,
      "findFirstWithAttribute: Didn't find element, as expected. Wrong value."
    );


  }

  public function findFirstWithClassTest() {
    $expected = "<div class='local-content'>DOGS!</div>";
    $html = "
<body>
  <p>
    Sloths are coming!
  </p>
  <p>
    RUN!
  </p>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div>Meow!</div>" . $expected . "
  </div>
</body>
";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->domProcessor->findFirstElementWithClass($elements, 'local-content');
    $this->assertEqual(
      TestUtilities::normalizeString($first->C14N()),
      TestUtilities::normalizeString($expected),
      "findFirstWithClass: Found the right element, simple test."
    );

    //Test for a class that does not exist.
    $first = $this->domProcessor->findFirstElementWithClass($elements, 'middle');
    $this->assertFalse($first,
      "findFirstWithClass: Did not find something that doesn't exist.");


    $expected = '<div class=\'local-content\'><p>dogs</p></div>';
    $html = "
<body>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div>Meow!</div>
    <div class='later'>
      <p>Now!</p>
      <h2>Things!</h2>
      <section>
      <p>Things</p>
        " . $expected . "
      </section>
    </div>
  </div>
</body>
";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->domProcessor->findFirstElementWithClass($elements, 'local-content');
    $this->assertEqual(
      TestUtilities::normalizeString($expected),
      TestUtilities::normalizeString($first->C14N()),
      "findFirstWithClass: Found the right element, nested."
    );
  }

  function findFirstUnprocessedShardTagTest() {
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
    $this->domProcessor->getMetadata()->setShardTypeNames(['sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $body = $doc->getElementsByTagName('body')->item(0);
    $first = $this->domProcessor->findFirstUnprocessedShardTag($body);
    $this->assertEqual($first->getAttribute('data-sloth-id'), 666,
      "findFirstUnprocessedShardTag: Found the right element."
    );


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
  <div data-shard-type='sloth' data-sloth-id='666' data-view-mode='strange' data-shard-processed='processed'>
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
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $body = $doc->getElementsByTagName('body')->item(0);
    $first = $this->domProcessor->findFirstUnprocessedShardTag($body);
    $this->assertFalse($first,
      "findFirstUnprocessedShardTag: Nothing found, as expected."
    );
  }

  public function findLocalContentContainerInDocTest() {

    $html = "
<body><div data-thing='6' class='r'>schwein</div><p>I will <span>die</span></p><div>hund</div>
<div class='local-content'>DOG</div>
</body>
    ";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    /* @var \DOMElement $div */
    $div = $this->domProcessor->findLocalContentContainerInDoc($doc);
    $this->assertEqual(
      TestUtilities::normalizeString($div->C14N()),
      TestUtilities::normalizeString("<div class='local-content'>DOG</div>"),
      'findLocalContentContainerInDoc:Found expected local content tag.');


    $html = "
<body>
  <div data-thing='6' class='r'>
    schwein
    <div>
      <p>COWS are strange.</p>
      <div class='local-content'>WOOF</div>
    </div>
  </div>
  <p>I will <span>die</span></p>
  <div>hund</div>

</body>
    ";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    /* @var \DOMElement $div */
    $div = $this->domProcessor->findLocalContentContainerInDoc($doc);
    $this->assertEqual($div->textContent, 'WOOF',
      'findLocalContentContainerInDoc:Found expected local content when deeply nested.');
  }

  public function getInnerHtmlTest(){
    $innerHtml = "<div data-thing='6'><div class='local-content'>DOG</div></div>";
    $outerHtml = "<section id='pht'>" . $innerHtml . '</section>';
    $doc = $this->domProcessor->createDomDocumentFromHtml(
      $outerHtml
    );
    /* @var \DOMElement $div */
    $body = $doc->getElementsByTagName('section')->item(0);
    $result = $this->domProcessor->getElementInnerHtml($body);
    $this->assertEqual(
      TestUtilities::normalizeString($innerHtml),
      TestUtilities::normalizeString($result),
      'getInnerHtmlTest: got expected HTML.');
  }

  public function getOuterHtmlTest(){
    $innerHtml = "<div data-thing='6'><div class='local-content'>DOG</div></div>";
    $outerHtml = "<section id='pht'>" . $innerHtml . '</section>';
    $doc = $this->domProcessor->createDomDocumentFromHtml(
      $outerHtml
    );
    /* @var \DOMElement $div */
    $body = $doc->getElementsByTagName('section')->item(0);
    $result = $this->domProcessor->getElementOuterHtml($body);
    $this->assertEqual(
      TestUtilities::normalizeString($outerHtml),
      TestUtilities::normalizeString($result),
      'getOuterHtmlTest: got expected HTML.');
  }

  public function getRequiredAttributeTest() {
    $html = "
<body>
  <div data-thing='6' class='r'>
    schwein
    <div>
      <p>COWS are strange.</p>
      <div class='local-content'>WOOF</div>
    </div>
  </div>
  <p>I will <span>die</span></p>
  <div>hund</div>

</body>
    ";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    /* @var \DOMElement $div */
    $div = $doc->getElementsByTagName('div')->item(0);
    $this->assertEqual(
      $this->domProcessor->getRequiredElementAttribute($div, 'class'),
      'r',
      'getRequiredAttributeTest: Found expected attribute value.');


    $html = "
<body>
  <div data-thing='6' class='r'>
    schwein
    <div>
      <p>COWS are strange.</p>
      <div class='local-content'>WOOF</div>
    </div>
  </div>
  <p>I will <span>die</span></p>
  <div>hund</div>

</body>
    ";
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    /* @var \DOMElement $div */
    $div = $doc->getElementsByTagName('div')->item(0);
    try {
      $this->domProcessor->getRequiredElementAttribute($div, 'joke');
      $this->fail(t('getRequiredAttributeTest: Expected exception was not thrown.'));
    } catch (ShardMissingDataException $e) {
      $this->pass(t('getRequiredAttributeTest: Expected exception was thrown.'));
    };
  }

  function isKnownShardTypeTest() {
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
   </body>";
    $this->domProcessor->getMetadata()->setShardTypeNames(['llama', 'sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $div = $doc->getElementsByTagName('div')->item(0);
    $this->assertTrue(
      $this->domProcessor->isKnownShardType($div),
      'isKnownShardTypeTest: shard type sloth known, as expected.'
    );

    $html = "
  <body>
    <div data-shard-type='lather' data-sloth-id='314' data-view-mode='shard' data-shard-processed='processed'>
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
   </body>";
    $this->domProcessor->getMetadata()->setShardTypeNames(['llama', 'sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $div = $doc->getElementsByTagName('div')->item(0);
    $this->assertFalse(
      $this->domProcessor->isKnownShardType($div),
      'isKnownShardTypeTest: shard type lather unknown, as expected.'
    );

    $html = "
  <body>
    <div data-jim='processed'>
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
   </body>";
    $this->domProcessor->getMetadata()->setShardTypeNames(['llama', 'sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $div = $doc->getElementsByTagName('div')->item(0);
    try {
      $this->domProcessor->isKnownShardType($div);
      $this->fail(t('isKnownShardTypeTest: Expected exception for not-a-shard was not thrown.'));
    } catch (ShardUnexpectedValueException $e) {
      $this->pass(t('isKnownShardTypeTest: Expected exception for not-a-shard was thrown.'));
    };

  }


  function isElementShardTest() {
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
   </body>";
    $this->domProcessor->getMetadata()->setShardTypeNames(['llama', 'sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $div = $doc->getElementsByTagName('div')->item(0);
    $this->assertTrue(
      $this->domProcessor->isElementShard($div),
      'isElementShardTest: element type sloth is a shard, as expected.'
    );

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
   </body>";
    $this->domProcessor->getMetadata()->setShardTypeNames(['llama', 'sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $div = $doc->getElementsByTagName('div')->item(0);
    $this->assertTrue(
      $this->domProcessor->isElementShard($div),
      'isElementShardTest: element type sloth is a shard, as expected.'
    );
  }

  function isShardElementProcessedTest() {
    $html = "
  <body>
    <div data-shard-type='sloth' data-sloth-id='314' 
            data-view-mode='shard' data-shard-processed='processed'>
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
   </body>";
    $this->domProcessor->getMetadata()->setShardTypeNames(['llama', 'sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $div = $doc->getElementsByTagName('div')->item(0);
    $this->assertTrue(
      $this->domProcessor->isShardElementProcessed($div),
      'isShardElementProcessedTest: shard is processed, as expected.'
    );

    $html = "
  <body>
    <div data-shard-type='sloth' data-sloth-id='314' 
            data-view-mode='shard'>
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
   </body>";
    $this->domProcessor->getMetadata()->setShardTypeNames(['llama', 'sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    $div = $doc->getElementsByTagName('div')->item(0);
    $this->assertFalse(
      $this->domProcessor->isShardElementProcessed($div),
      'isShardElementProcessedTest: shard is not processed, as expected.'
    );
  }

  public function loadDomDocumentFromHtmlTest() {
    $html = "
<body>
  <p>
    Sloths are coming!
  </p>
  <p>
    RUN!
  </p>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div>Meow!</div>
  </div>
</body>
";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = false;
    $this->domProcessor->loadDomDocumentFromHtml($doc, $html);
    $result = $doc->getElementsByTagName('body')->item(0)->C14N();
    $this->assertTrue(
      TestUtilities::checkHtmlSame($html, $result),
      "loadDomDocumentFromHtmlTest: Found the right HTML."
    );
  }

  function markShardAsProcessedTest(){
    $html = "
  <body>
    <div data-shard-type='sloth' data-sloth-id='314' data-view-mode='shard'>
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
   </body>";
    $this->domProcessor->getMetadata()->setShardTypeNames(['llama', 'sloth']);
    $doc = $this->domProcessor->createDomDocumentFromHtml($html);
    /* @var \DOMElement $div */
    $div = $doc->getElementsByTagName('div')->item(0);
    $this->domProcessor->markShardAsProcessed($div);
    $this->assertEqual(
      $div->getAttribute('data-shard-processed'),
      'processed',
      'markShardAsProcessed: element marked as processed, as expected.'
    );

  }

  public function removeElementChildrenTest() {

    $html = "
<body><div data-thing='6'><p>I will <span>die</span></p><p>BOO!</p></div></body>
";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->domProcessor->removeElementChildren($element);
    $expected = "<div data-thing='6'></div>";
    $this->assertEqual(
      TestUtilities::normalizeString($expected),
      TestUtilities::normalizeString($element->C14N()),
      'removeElementChildrenTest: The children were killed, as expected.'
    );


    $html = "
<body><div data-thing='6'></div></body>
";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->domProcessor->removeElementChildren($element);
    $expected = "<div data-thing='6'></div>";
    $this->assertEqual(
      TestUtilities::normalizeString($expected),
      TestUtilities::normalizeString($element->C14N()),
      'removeElementChildrenTest: No children to kill, as expected.'
    );

  }

  public function replaceElementChildrenTest() {
    $html = "
<body>
  <div class='r'>schwein</div>
  <div>I will <span>die</span></div>
  <div>hund</div>
</body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $target = $doc->getElementsByTagName('div')->item(0);
    $source = $doc->getElementsByTagName('div')->item(1);
    $this->domProcessor->replaceElementChildren($target, $source);
    $this->assertEqual(
      TestUtilities::normalizeString($target->C14N()),
      TestUtilities::normalizeString("<div class='r'>I will <span>die</span></div>"),
      'replaceElementChildrenTest: Replaced element contents successful.'
    );
  }

  public function replaceElementContentsTest() {

    $html = "<body><div data-thing='6' class='r'>schwein</div><p>I will <span>die</span></p><div>hund</div></body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $from = $doc->getElementsByTagName('div')->item(0);
    $with = $doc->getElementsByTagName('div')->item(1);
    $this->domProcessor->replaceElementContents($from, $with);
    $this->assertEqual(
      TestUtilities::normalizeString($from->C14N()),
      TestUtilities::normalizeString('<div>hund</div>'),
      'replaceElementContentsTest: Replaced element contents successful.'
    );
  }

  public function stripElementAttributesTest() {
    $html = "
<body>
  <div data-sloth-id='314' data-view-mode='shard'>DOG</div>
</body>
";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->domProcessor->stripElementAttributes($element);
    $this->assertEqual(
      TestUtilities::normalizeString('<div>DOG</div>'),
      TestUtilities::normalizeString($element->C14N()),
      "stripElementAttributesTest: The attributes were stripped, as expected.");
  }

  function reportUnknownShardTypeTest() {
    //TODO
  }




}