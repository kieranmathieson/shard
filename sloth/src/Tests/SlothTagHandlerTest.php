<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 10/4/16
 * Time: 1:25 PM
 */

namespace Drupal\sloth\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\sloth\Exceptions\SlothMissingDataException;

/**
 * Provides automated tests for the SlothTagProcessor class in the sloth module.
 *
 * @group sloth
 */
class SlothTagHandlerTest extends WebTestBase {

  /**
   * Object to test.
   *
   * @var \Drupal\sloth\Tests\SlothTagHandlerWrapper
   */
  protected $slothTagHandler;

  /**
   * Set up stuff shared between tests.
   */
  public function setUp() {
    parent::setUp();
    $this->slothTagHandler = new SlothTagHandlerWrapper();
  }

  public function testGroup1() {
    $this->cacheTagDetailsTest();
    $this->checkElementForLocalContentTest();
    $this->copyChildrenTest();
    $this->duplicateAttributesTest();
    $this->findFirstWithAttributeTest();
    $this->findFirstWithClassTest();
    $this->findLocalContentContainerInDocTest();
    $this->getDomElementHtmlTest();
//    $this->getLocalContentFromCkTagTest();
    $this->getSlothNidTest();
    $this->getViewModeOfElementTest();
    $this->insertLocalContentDbTest();
    $this->insertLocalContentIntoViewHtmlTest();
    $this->removeElementChildrenTest();
    $this->replaceElementContentsTest();
    $this->stripAttributesTest();
  }

  public function getSlothNidTest() {
    $html = "<body><div data-sloth-id='314'></div></body>";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->assertEqual(
      '314',
      $this->slothTagHandler->getSlothNid($element),
      "getSlothNid: Got expected nid."
    );


    $html = "<body><div></div></body>";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    try {
      $this->slothTagHandler->getSlothNid($element);
      $this->fail(t('getSlothNid: Expected missing nid exception was not thrown.'));
    } catch (SlothMissingDataException $e) {
      $this->pass(t('getSlothNid: Expected missing nid exception was thrown.'));
    };
  }

  public function getViewModeOfElementTest() {

    $html = "<body><div data-sloth-id='314' data-view-mode='shard'></div></body>";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->assertEqual(
      'shard',
      $this->slothTagHandler->getViewModeOfElement($element),
      "getViewMode: Got expected view mode."
    );


    $html = "<body><div></div></body>";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    try {
      $this->slothTagHandler->getViewModeOfElement($element);
      $this->fail(t('getViewMode: Expected missing view mode exception was not thrown.'));
    } catch (SlothMissingDataException $e) {
      $this->pass(t('getViewMode: Expected missing view mode exception was thrown.'));
    };
  }

//  public function getLocalContentFromCkTagTest() {
//    $html = "
//<body>
//  <div data-sloth-id='314' data-view-mode='shard'>
//    <div>Meow!</div>
//    <div class='local-content'>Woof!</div>
//  </div>
//</body>
//";
//    $doc = new \DOMDocument();
//    $doc->preserveWhiteSpace = FALSE;
//    $doc->loadHTML($html);
//    $element = $doc->getElementsByTagName('div')->item(0);
//    $this->assertEqual(
//      'Woof!',
//      $this->slothTagHandler->getLocalContentFromCkTag($element),
//      "getLocalContentFromCkTag: Got expected local content in simple test."
//    );
//
//
//    $html = "
//<body>
//  <div data-sloth-id='314' data-view-mode='shard'>
//    <div>Meow!</div>
//    <div class='local-content'>
//      <p><span>dogs</span></p>
//      <div>Woof!</div>
//    </div>
//  </div>
//</body>
//";
//    $doc = new \DOMDocument();
//    $doc->preserveWhiteSpace = FALSE;
//    $doc->loadHTML($html);
//    $element = $doc->getElementsByTagName('div')->item(0);
//    $this->assertEqual(
//      '<p><span>dogs</span></p><div>Woof!</div>',
//      $this->slothTagHandler->getLocalContentFromCkTag($element),
//      'getLocalContentFromCkTag: Got expected local content, more complex test.'
//    );
//
//
//    $html = "
//<body>
//  <div data-sloth-id='314' data-view-mode='shard'>
//    <div>Meow!</div>
//    <div class='later'>
//      <p>Now!</p>
//      <h2>Things!</h2>
//      <section>
//        <div class='local-content'>
//          <p><span>dogs</span></p>
//          <div>Woof!</div>
//        </div>
//      </section>
//    </div>
//  </div>
//</body>
//";
//    $doc = new \DOMDocument();
//    $doc->preserveWhiteSpace = FALSE;
//    $doc->loadHTML($html);
//    $element = $doc->getElementsByTagName('div')->item(0);
//    $this->assertEqual(
//      '<p><span>dogs</span></p><div>Woof!</div>',
//      $this->slothTagHandler->getLocalContentFromCkTag($element),
//      'getLocalContentFromCkTag: Got expected local content, deeply nested.'
//    );
//  }

  public function cacheTagDetailsTest() {
    $local_content = '<p><span>dogs</span></p><div>Woof!</div>';
    $html = "
<body>
  <div data-sloth-id='314' data-view-mode='shard'>
    <div>Meow!</div>
    <div class='local-content'>" . $local_content . "
    </div>
  </div>
</body>
";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    //The call to test.
    $this->slothTagHandler->cacheTagDetails($element);
    $sloth_bag = $this->slothTagHandler->getSlothReferenceBag();
    $sloth_nid_right = ($sloth_bag->getSlothNid() == 314);
    $view_mode_right = ($sloth_bag->getViewMode() == 'shard');
    $local_content_right =
      $this->normalizeString($sloth_bag->getLocalContent())
      ==
      $this->normalizeString($local_content);
    $location_right = ($sloth_bag->getLocation() == 3);
    $this->assertTrue(
      $sloth_nid_right && $view_mode_right && $local_content_right && $location_right,
      "cacheTagDetails: Sloth bag stored details correctly."
    );

  }

  public function findFirstWithClassTest() {
    $expected = "<div class='local-content'>DOGZ!</div>";
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
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->slothTagHandler->findFirstWithClass($elements, 'local-content');
    $this->assertEqual(
      $this->normalizeString($first->C14N()),
      $this->normalizeString($expected),
      "findFirstWithClass: Found the right element, simple test."
    );

    //Test for a class that does not exist.
    $first = $this->slothTagHandler->findFirstWithClass($elements, 'middle');
    $this->assertFalse($first,
      "findFirstWithClass: Did not find something that doesn't exist.");


    $html = "
<body>
  <div data-sloth-id='314' data-view-mode='shard'>
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
</body>
";
    $expected = '<div class=\'local-content\'><p>dogs</p></div>';
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $this->slothTagHandler->loadDomDocumentHtml($doc, $html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->slothTagHandler->findFirstWithClass($elements, 'local-content');
    $this->assertTrue($this->checkHtmlSame(
      $this->normalizeString($expected),
      $this->normalizeString($first->C14N())),
      "findFirstWithClass: Found the right element, nested."
    );
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
    <div data-thing='44'>Meow!</div><div class='local-content'>DOGZ!</div>
  </div>
</body>
";
    $expected = "<div data-thing='44'>Meow!</div>";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->slothTagHandler->findFirstWithAttribute($elements, 'data-thing', 44);
    $this->assertEqual(
      $this->normalizeString($first->C14N()),
      $this->normalizeString($expected),
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
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $this->slothTagHandler->loadDomDocumentHtml($doc, $html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->slothTagHandler->findFirstWithAttribute($elements,
      'data-best-animal', 'this-one');
    $this->assertEqual(
      $this->normalizeString($first->C14N()),
      $this->normalizeString($expected),
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
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $this->slothTagHandler->loadDomDocumentHtml($doc, $html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->slothTagHandler->findFirstWithAttribute($elements,
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
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $this->slothTagHandler->loadDomDocumentHtml($doc, $html);
    $elements = $doc->getElementsByTagName('div');
    //Test for a class that exists.
    $first = $this->slothTagHandler->findFirstWithAttribute($elements,
      'data-best-animal', 'that-one');
    $this->assertFalse(
      $first,
      "findFirstWithAttribute: Didn't find element, as expected. Wrong value."
    );


  }


  public function stripAttributesTest() {
    $html = "
<body>
  <div data-sloth-id='314' data-view-mode='shard'>DOG</div>
</body>
";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->slothTagHandler->stripAttributes($element);
    $this->assertEqual(
      $this->normalizeString('<div>DOG</div>'),
      $this->normalizeString($element->C14N()),
      "stripAttributes:The attributes were stripped, as expected.");
  }

  public function insertLocalContentDbTest() {

    $html = "<body><div data-thing='6'></div></body>";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $local = "<span id='x33'>DOGZ!</span>";
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->slothTagHandler->insertLocalContentDb($element, $local);
    $expected = "<div data-thing='6'><div class='local-content'>" . $local . "</div></div>";
    $this->assertEqual(
      $this->normalizeString($expected),
      $this->normalizeString($element->C14N()),
      'insertLocalContentDb: The expected content was added.');
  }

  public function removeElementChildrenTest() {

    $html = "
<body><div data-thing='6'><p>I will <span>die</span></p><p>ARGH!</p></div></body>
";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->slothTagHandler->removeElementChildren($element);
    $expected = "<div data-thing='6'></div>";
    $this->assertEqual(
      $this->normalizeString($expected),
      $this->normalizeString($element->C14N()),
      'removeElementChildren:The children were killed, as expected.'
    );


    $html = "
<body><div data-thing='6'></div></body>
";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $element = $doc->getElementsByTagName('div')->item(0);
    $this->slothTagHandler->removeElementChildren($element);
    $expected = "<div data-thing='6'></div>";
    $this->assertEqual(
      $this->normalizeString($expected),
      $this->normalizeString($element->C14N()),
      'removeElementChildren:No children to kill, as expected.'
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
  $this->slothTagHandler->duplicateAttributes($from, $to);
  //Check that the attributes are the same.
  $this->assertTrue($this->checkAttributesAreSame($from, $to),
    "duplicateAttributes: Attributes copied, as expected.");
  }

  public function copyChildrenTest() {

    $html = "<body><div><p>schwein</p><p>hund</p>schwein</div><p>I will <span>die</span></p><div></div></body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $from = $doc->getElementsByTagName('div')->item(0);
    $to = $doc->getElementsByTagName('div')->item(1);
    $this->slothTagHandler->copyChildren($from, $to);
    $to_html = $this->normalizeString($to->C14N());
    $expected = $this->normalizeString("<div><p>schwein</p><p>hund</p>schwein</div>");
    $this->assertEqual($expected, $to_html, "copyChildren: Children copied, as expected.");
  }

  public function replaceElementContentsTest() {

    $html = "<body><div data-thing='6' class='r'>schwein</div><p>I will <span>die</span></p><div>hund</div></body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $from = $doc->getElementsByTagName('div')->item(0);
    $with = $doc->getElementsByTagName('div')->item(1);
    $this->slothTagHandler->replaceElementContents($from, $with);
    $this->assertEqual(
      $this->normalizeString($from->C14N()),
      $this->normalizeString('<div>hund</div>'),
      'replaceElementContents:Replaced element contents successful.'
    );
  }

  public function findLocalContentContainerInDocTest() {

    $html = "
<body><div data-thing='6' class='r'>schwein</div><p>I will <span>die</span></p><div>hund</div>
<div class='local-content'>DOG</div>
</body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    /* @var \DOMElement $div */
    $div = $this->slothTagHandler->findLocalContentContainerInDoc($doc);
    $this->assertEqual(
      $this->normalizeString($div->C14N()),
      $this->normalizeString("<div class='local-content'>DOG</div>"),
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
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    /* @var \DOMElement $div */
    $div = $this->slothTagHandler->findLocalContentContainerInDoc($doc);
    $this->assertEqual($div->textContent, 'WOOF',
      'findLocalContentContainerInDoc:Found expected local content when deeply nested.');
  }

  public function insertLocalContentIntoViewHtmlTest(){

    $local = 'DOG!';
    $html = "
<body><div data-thing='6'><div class='local-content'></div></div></body>
    ";
    $expected = "
<body><div data-thing='6'><div class='local-content'>" . $local . "</div></div></body>
    ";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($html);
    $this->slothTagHandler->insertLocalContentIntoViewHtml($doc, $local);
    /* @var \DOMElement $body */
    $body = $doc->getElementsByTagName('body')->item(0);
    $this->assertEqual(
      $this->normalizeString($body->C14N()),
      $this->normalizeString($expected),
      'insertLocalContentIntoViewHtml: Inserted expected local content.');

  }

  public function checkElementForLocalContentTest() {

  }

  public function getDomElementHtmlTest(){
    $test_html = "<div data-thing='6'><div class='local-content'>DOG</div></div>";
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->loadHTML($test_html);
    $body = $doc->getElementsByTagName('body')->item(0);
    $result = $this->slothTagHandler->getDomElementOuterHtml($body);
    $this->assertEqual(
      $this->normalizeString($test_html),
      $this->normalizeString($result),
      'getDomElementHtmlTest: got expected HTML.');

  }



  /**
   * Change string into a predictable format.
   *
   * @param $in
   * @return mixed|string
   */
  protected function normalizeString($in) {
    //" to '
    $out = str_replace('"', "'", $in);
    $out = str_replace("\n", '', $out);
    $out = str_replace("\r", '', $out);
    $out = str_replace(' ', '', $out);
    $out = trim($out);
    return $out;
  }

  /**
   * Copy the attributes from one element to another.
   *
   * @param \DOMElement $element1
   * @param \DOMElement $element2
   * @return bool
   * @internal param \DOMElement $from Copy attributes from this element...
   * @internal param \DOMElement $to ...to this element.
   */
  protected function checkAttributesAreSame(\DOMElement $element1, \DOMElement $element2) {
    if ($element1->attributes->length != $element2->attributes->length) {
      return FALSE;
    }
    foreach ($element1->attributes as $attribute) {
      $el1_attr = $element1->getAttribute($attribute->name);
      $el2_attr = $element2->getAttribute($attribute->name);
      if ($this->normalizeString($el1_attr) != $this->normalizeString($el2_attr)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  protected function checkDomDocHtmlSame( \DOMDocument $dom_doc1, \DOMDocument $dom_doc2) {
    $html1 = $this->normalizeString($dom_doc1->C14N());
    $html2 = $this->normalizeString($dom_doc2->C14N());
    return $html1 == $html2;
  }

  protected function checkHtmlSame( $html1, $html2 ) {
    $dom_doc1 = new \DOMDocument();
    $dom_doc1->preserveWhiteSpace = false;
    $this->slothTagHandler->loadDomDocumentHtml($dom_doc1, $html1);
    $dom_doc2 = new \DOMDocument();
    $dom_doc2->preserveWhiteSpace = false;
    $this->slothTagHandler->loadDomDocumentHtml($dom_doc2, $html2);
    return $this->checkDomDocHtmlSame($dom_doc1, $dom_doc2);
  }

}