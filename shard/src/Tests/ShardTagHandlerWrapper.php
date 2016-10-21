<?php
/**
 * @file
 * Inherits from SlothTagHandler, exposing protected methods for testing.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\sloth\Tests;

use Drupal\sloth\SlothTagHandler;

class SlothTagHandlerWrapper extends SlothTagHandler {

  /**
   * SlothTagHandlerWrapper constructor.
   */
  public function __construct() {
    $container = \Drupal::getContainer();
    parent::__construct(
//      $container->get('sloth.eligible_fields'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('renderer')
    );
  }

  public function getSlothNid(\DOMElement $element) {
    return parent::getSlothNid($element);
  }

  public function getViewModeOfElement(\DOMElement $element) {
    return parent::getViewModeOfElement($element);
  }

  public function stripAttributes(\DOMElement $element) {
    parent::stripAttributes($element);
  }

  public function insertLocalContentDb(\DOMElement $element, $local_content) {
    parent::insertLocalContentDb($element, $local_content);
  }

  public function removeElementChildren(\DOMElement $element) {
    parent::removeElementChildren($element);
  }

  public function cacheTagDetails(\DOMElement $element) {
    parent::cacheTagDetails($element);
  }

  /**
   * slothInsertionDetails is a protected property.
   * Use this method to get to it for testing.
   *
   * @return \Drupal\sloth\SlothReferenceBag
   */
  public function getSlothReferenceBag() {
    return $this->slothInsertionDetails;
  }

  public function findFirstWithClass(\DOMNodeList $elements, $class) {
    return parent::findFirstWithClass($elements, $class);
  }

  public function duplicateAttributes(\DOMElement $from, \DOMElement $to) {
    return parent::duplicateAttributes($from, $to);
  }

  public function copyChildren(\DOMElement $from, \DOMElement $to) {
    return parent::copyChildren($from, $to);
  }


  public function replaceElementContents(\DOMElement $element, \DOMElement $replacement) {
    return parent::replaceElementContents($element, $replacement);
  }


  public function findLocalContentContainerInDoc(\DOMDocument $document) {
    return parent::findLocalContentContainerInDoc($document);
  }


  public function insertLocalContentIntoViewHtml(\DOMDocument $view_document, $local_content) {
    return parent::insertLocalContentIntoViewHtml($view_document, $local_content);
  }

  public function findFirstWithAttribute(\DOMNodeList $elements, $attribute, $value) {
    return parent::findFirstWithAttribute($elements, $attribute, $value);
  }

  public function getDomElementOuterHtml(\DOMElement $element) {
    return parent::getDomElementOuterHtml($element);
  }

  public function loadDomDocumentHtml( \DOMDocument $dom_document, $html ) {
    return parent::loadDomDocumentHtml($dom_document, $html);
  }

}
