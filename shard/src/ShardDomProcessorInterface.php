<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 11/5/16
 * Time: 9:34 AM
 */
namespace Drupal\shard;

interface ShardDomProcessorInterface {
  /**
   * Get the HTML represented by a DOMElement.
   *
   * @param \DOMElement $element The element.
   * @return string HTML The HTML.
   */
  public function getElementOuterHtml(\DOMElement $element);

  /**
   * Get the inner HTML (i.e., HTML of the children) of a DOM element.
   *
   * @param \DOMElement $element Element to process.
   * @return string The HTML.
   */
  public function getElementInnerHtml(\DOMElement $element);

  /**
   * Return first element with a given class.
   * @param \DOMNodeList $elements
   * @param string $class Class to find.
   * @return \DOMElement|false Element with class.
   */
  public function findFirstElementWithClass(\DOMNodeList $elements, $class);

  /**
   * Remove all of the chldren from a DOM element.
   *
   * @param  \DOMElement $element
   */
  public function removeElementChildren(\DOMElement $element);

  /**
   * Strip the attributes of an element.
   *
   * @param \DOMElement $element
   */
  public function stripElementAttributes(\DOMElement $element);

  /**
   * Return first element with a given value for a given attribute.
   * @param \DOMNodeList $elements
   * @param string $attribute Attribute to check.
   * @param string $value Value to check for. * for any.
   * @return \DOMElement|false An element.
   */
  public function findFirstElementWithAttribute(\DOMNodeList $elements, $attribute, $value);

  /**
   * Return the first HTML child shard tag of an element
   * that has not been processed.
   *
   * @param \DOMElement $parentElement
   * @return \DOMElement|false An element.
   */
  public function findFirstUnprocessedShardTag(\DOMElement $parentElement);

  /**
   * Is an element a shard tag?
   *
   * @param \DOMElement $element
   * @return bool
   */
  public function isElementShard(\DOMElement $element);

  /**
   * Has a shard been processed already?
   *
   * Note: only works for elements that are shards.
   *
   * @param \DOMElement $element Shard element to check.
   * @return bool Result.
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function isShardElementProcessed(\DOMElement $element);

  public function markShardAsProcessed(\DOMElement $element);

  /**
   * Is a shard a known type?
   *
   * @param \DOMElement $element
   * @return bool
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function isKnownShardType(\DOMElement $element);

  /**
   * Tell authors and admins if there is a shard tag with an unknown type.
   *
   * @param string $typeName Unknown type name.
   */
  public function reportUnknownShardType($typeName);

  /**
   * @param \DOMDocument $document
   * @return bool|\DOMElement Element with the class local-content.
   */
  public function findLocalContentContainerInDoc(\DOMDocument $document);

  /**
   * Find local content within an element.
   *
   * @param \DOMElement $element Element to look in
   * @return bool|\DOMElement Element with local content, false if not found.
   */
  public function findElementWithLocalContent(\DOMElement $element);

  /**
   * Rebuild a DOM element from another.
   *
   * There could be a better way to do this, but the code here should
   * be safe. It keeps the DOM-space (the DOMDocument that $element is from)
   * intact.
   *
   * @param \DOMElement $element Element to rebuild.
   * @param \DOMElement $replacement Element to rebuild from.
   */
  public function replaceElementContents(\DOMElement $element, \DOMElement $replacement);

  /**
   * Replace the children of one DOM element with the children of another.
   *
   * @param \DOMElement $target Element to rebuild.
   * @param \DOMElement $source Element to rebuild from.
   */
  public function replaceElementChildren(\DOMElement $target, \DOMElement $source);

  /**
   * Duplicate the attributes on one element to another.
   *
   * @param \DOMElement $from Duplicate attributes from this element...
   * @param \DOMElement $to ...to this element.
   */
  public function duplicateElementAttributes(\DOMElement $from, \DOMElement $to);

  /**
   * Copy the child nodes from one DomElement to another.
   *
   * @param \DOMElement $from Copy children from this element...
   * @param \DOMElement $to ...to this element.
   */
  public function copyElementChildren(\DOMElement $from, \DOMElement $to);

  /**
   * Load HTML into a DOMDocument, with error handling.
   *
   * @param \DOMDocument $document Doc to parse the HTML.
   * @param string $html HTML to parse.
   */
  public function loadDomDocumentFromHtml(\DOMDocument $document, $html);

  /**
   * Create a DOMDocument from some HTML.
   *
   * @param string $html HTML.
   * @return \DOMDocument DOM document created from HTML.
   */
  public function createDomDocumentFromHtml($html);

  /**
   * Return the value of a required attribute of an element.
   *
   * @param \DOMElement $element Element to check.
   * @param string $attribute Attribute name.
   * @return string Attribute value.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  public function getRequiredElementAttribute(\DOMElement $element, $attribute);

  /**
   * @return \Drupal\shard\ShardMetadataInterface
   */
  public function getMetadata();
}