<?php
/**
 * @file
 * Useful methods for testing.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard\Tests;


class TestUtilities {

  /**
   * Change string into a predictable format.
   *
   * @param $in
   * @return mixed|string
   */
  static public function normalizeString($in) {
    //" to '
    $out = str_replace('"', "'", $in);
    $out = str_replace("\n", '', $out);
    $out = str_replace("\r", '', $out);
    $out = str_replace(' ', '', $out);
    $out = trim($out);
    return $out;
  }

  static public function checkAttributesAreSame(\DOMElement $element1, \DOMElement $element2) {
    if ($element1->attributes->length != $element2->attributes->length) {
      return FALSE;
    }
    foreach ($element1->attributes as $attribute) {
      $el1_attr = $element1->getAttribute($attribute->name);
      $el2_attr = $element2->getAttribute($attribute->name);
      if (TestUtilities::normalizeString($el1_attr) != TestUtilities::normalizeString($el2_attr)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  static public function checkDomDocHtmlSame( \DOMDocument $dom_doc1, \DOMDocument $dom_doc2) {
    $html1 = TestUtilities::normalizeString($dom_doc1->C14N());
    $html2 = TestUtilities::normalizeString($dom_doc2->C14N());
    return $html1 == $html2;
  }

  static public function checkHtmlSame( $html1, $html2 ) {
    $dom_doc1 = new \DOMDocument();
    $dom_doc1->preserveWhiteSpace = false;
    $dom_doc1->loadHTML($html1);
    $dom_doc2 = new \DOMDocument();
    $dom_doc2->preserveWhiteSpace = false;
    $dom_doc2->loadHTML($html2);
    return TestUtilities::checkDomDocHtmlSame($dom_doc1, $dom_doc2);
  }

  static public function makePageNode($title) {
    $values = array(
      'type' => 'page',
      'title' => $title,
    );
    $node = \Drupal::service('entity_type.manager')->getStorage('node')->create($values);
    return $node;
  }

}