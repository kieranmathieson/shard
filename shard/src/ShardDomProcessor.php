<?php
/**
 * @file
 * Does all the shard tag processing for a node.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

//use Drupal\shard\Exceptions\ShardBadDataTypeException;
//use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ShardDomProcessor {

  /**
   * @var ShardMetadataInterface
   */
  protected $metadata;

  /**
   * ShardTagHandler constructor.
   *
   * Load shard configuration data set by admin.
   * @param \Drupal\shard\ShardMetadataInterface $metadata
   */
  public function __construct(
    ShardMetadataInterface $metadata) {
    $this->metadata = $metadata;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shard.metadata')
    );
  }

  /**
   * Get the HTML represented by a DOMElement.
   *
   * @param \DOMElement $element The element.
   * @return string HTML The HTML.
   */
  public function getElementOuterHtml(\DOMElement $element) {
    $tmp_doc = new \DOMDocument();
    //Make sure there's a body element.
    if ( $element->tagName != 'body' && $element->getElementsByTagName('body')->length == 0 ) {
      $tmp_doc->appendChild( $tmp_doc->createElement('body') );
      $body = $tmp_doc->getElementsByTagName('body')->item(0);
      $body->appendChild($tmp_doc->importNode($element, TRUE));
    }
    else {
      $tmp_doc->appendChild($tmp_doc->importNode($element, TRUE));
    }
    $html = $tmp_doc->saveHTML( $tmp_doc->getElementsByTagName('body')->item(0) );
    preg_match("/\<body\>(.*)\<\/body\>/msi", $html, $matches);
    $html = $matches[1];
    return $html;
  }

  /**
   * Get the inner HTML (i.e., HTML of the children) of a DOM element.
   *
   * @param \DOMElement $element Element to process.
   * @return string The HTML.
   */
  public function getElementInnerHtml(\DOMElement $element){
    $result = '';
    foreach( $element->childNodes as $child ) {
      if ( get_class($child) == 'DOMText' ) {
        $result .= $child->wholeText;
      }
      else {
        $result .= $this->getElementOuterHtml($child);
      }
    }
    return $result;
  }

  /**
   * Return first element with a given class.
   * @param \DOMNodeList $elements
   * @param string $class Class to find.
   * @return \DOMElement|false Element with class.
   */
  public function findFirstElementWithClass(\DOMNodeList $elements, $class) {
    return $this->findFirstElementWithAttribute($elements, 'class', $class);
  }

  /**
   * Remove all of the chldren from a DOM element.
   *
   * @param  \DOMElement $element
   */
  public function removeElementChildren(\DOMElement $element) {
    $children = [];
    if ( $element->hasChildNodes() ) {
      foreach ( $element->childNodes as $child_node ){
        $children[] = $child_node;
      }
      foreach ( $children as $child ) {
        $element->removeChild($child);
      }
    }
  }



  /**
   * Strip the attributes of an element.
   *
   * @param \DOMElement $element
   */
  public function stripElementAttributes(\DOMElement $element) {
    $attributes = $element->attributes;
    $attribute_names = [];
    foreach( $attributes as $attribute => $value ) {
      $attribute_names[] = $attribute;
    }
    foreach ($attribute_names as $attribute_name) {
      $element->removeAttribute($attribute_name);
    }
  }



  /**
   * Return first element with a given value for a given attribute.
   * @param \DOMNodeList $elements
   * @param string $attribute Attribute to check.
   * @param string $value Value to check for. * for any.
   * @return \DOMElement|false An element.
   */
  public function findFirstElementWithAttribute(\DOMNodeList $elements, $attribute, $value) {
    //For each element
    /* @var \DOMElement $element */
    foreach($elements as $element) {
      //Is it an element?
      if (get_class($element) == 'DOMElement') {
        //Does it have the attribute and value?
        if ($element->hasAttribute($attribute)) {
          if ($value == '*' || $element->getAttribute($attribute) == $value) {
            //Yes - return the element.
            return $element;
          }
        }
        //Test children.
        if ($element->hasChildNodes()) {
          $result = $this->findFirstElementWithAttribute($element->childNodes, $attribute, $value);
          if ($result) {
            return $result;
          }
        }
      }
    }
    return false;
  }


  /**
   * Return the first HTML shard tag that has not been processed.
   *
   * @param \DOMNodeList $elements
   * @return \DOMElement|false An element.
   */
  public function findFirstUnprocessedShardTag(\DOMNodeList $elements) {
    //For each element
    /* @var \DOMElement $element */
    foreach($elements as $element) {
      //Is it an element?
      if (get_class($element) == 'DOMElement') {
        //Is it a shard tag?
        if ($element->hasAttribute(ShardMetadata::SHARD_TYPE_TAG)) {
          //Is it a known tag?
          $shardTypeName = strtolower($element->getAttribute(ShardMetadata::SHARD_TYPE_TAG));
          if ( in_array($shardTypeName, $this->metadata->getShardTypeNames() ) ) {
            //Is it unprocessed?
            $processed
              =    $element->hasAttribute(
                     ShardMetadata::SHARD_TAG_BEEN_PROCESSED_ATTRIBUTE
                   )
                && $element->getAttribute(
                     ShardMetadata::SHARD_TAG_BEEN_PROCESSED_ATTRIBUTE
                   ) == ShardMetadata::SHARD_TAG_HAS_BEEN_PROCESSED_VALUE;
            if (! $processed ) {
              //Yes - return the element.
              return $element;
            }
          }
          else {
            //Got a shard whose type name is not known.
            //If the user is an author or admin, warn him/her.
            if ( ShardUtilities::currentUserHasRole(['author', 'admin']) ) {
              drupal_set_message(t(
                'Reference to unknown shard type: %name',
                ['%name' => $shardTypeName]
              ), 'notice');
            }
          }
        }
        //Test children.
        if ($element->hasChildNodes()) {
          $result = $this->findFirstUnprocessedShardTag($element->childNodes);
          if ($result) {
            return $result;
          }
        }
      }
    }
    return false;
  }

  /**
   * Is an element a shard tag?
   *
   * @param \DOMElement $element
   * @return bool
   */
  public function isElementShard(\DOMElement $element) {
    return $element->hasAttribute(ShardMetadata::SHARD_TYPE_TAG);
  }

  /**
   * Has a shard been processed already?
   *
   * Note: only works for elements that are shards.
   *
   * @param \DOMElement $element Shard element to check.
   * @return bool Result.
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function isShardElementProcessed(\DOMElement $element) {
    if ( ! $this->isElementShard($element) ) {
      throw new ShardUnexpectedValueException(
        'Passed nonshard to isShardElementProcessed.'
      );
    }
    //Is it unprocessed?
    $processed
      =    $element->hasAttribute(
             ShardMetadata::SHARD_TAG_BEEN_PROCESSED_ATTRIBUTE
           )
        && $element->getAttribute(
             ShardMetadata::SHARD_TAG_BEEN_PROCESSED_ATTRIBUTE
           ) == ShardMetadata::SHARD_TAG_HAS_BEEN_PROCESSED_VALUE;
    return $processed;
  }


  public function markShardAsProcessed(\DOMElement $element) {
    //Is it a shard?
    if ( ! $this->isElementShard($element) ) {
      throw new ShardUnexpectedValueException(
        'Passed nonshard to markShardAsProcessed.'
      );
    }
    $element->setAttribute(
      ShardMetadata::SHARD_TAG_BEEN_PROCESSED_ATTRIBUTE,
      ShardMetadata::SHARD_TAG_HAS_BEEN_PROCESSED_VALUE
    );
  }
  /**
   * Is a shard a known type?
   *
   * @param \DOMElement $element
   * @return bool
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function isKnownShardType(\DOMElement $element) {
    //Is it a shard?
    if ( ! $this->isElementShard($element) ) {
      throw new ShardUnexpectedValueException(
        'Passed nonshard to isKnownShardType.'
      );
    }
    //Is it a known type?
    $shardTypeName = strtolower($element->getAttribute(ShardMetadata::SHARD_TYPE_TAG));
    return in_array($shardTypeName, $this->metadata->getShardTypeNames());
  }

  /**
   * Tell authors and admins if there is a shard tag with an unknown type.
   *
   * @param string $typeName Unknown type name.
   */
  public function reportUnknownShardType($typeName) {
    //If the user is an author or admin, warn him/her.
    if ( ShardUtilities::currentUserHasRole(['author', 'admin']) ) {
      drupal_set_message(
        t('Reference to unknown shard type: %name', ['%name' => $typeName]),
        'notice'
      );
    }
  }

  /**
   * @param \DOMDocument $document
   * @return bool|\DOMElement Element with the class local-content.
   */
  public function findLocalContentContainerInDoc(\DOMDocument $document) {
    $divs = $document->getElementsByTagName('div');
    /* @var \DOMElement $div */
    foreach ($divs as $div) {
      $result = $this->findElementWithLocalContent($div);
      if ($result) {
        return $result;
      }
    }
    return false;
  }

  /**
   * Find local content within an element.
   *
   * @param \DOMElement $element Element to look in
   * @return bool|\DOMElement Element with local content, false if not found.
   */
  public function findElementWithLocalContent(\DOMElement $element) {
    if ( $element->tagName == 'div'
        && $element->hasAttribute('class')
        && $element->getAttribute('class') == 'local-content') {
        return $element;
    }
    foreach( $element->childNodes as $child ) {
      if ( get_class($child) == 'DOMElement' ) {
        $result = $this->findElementWithLocalContent($child);
        if ($result) {
          return $result;
        }
      }
    }
    return false;
  }

  /**
   * Rebuild a DOM element from another.
   *
   * There could be a better way to do this, but the code here should
   * be safe. It keeps the DOM-space (the DOMDocument that $element is from)
   * intact.
   *
   * @param \DOMElement $element Element to rebuild.
   * @param \DOMElement $replacement Element to rebuild from. Assume it is
   *  wrapped in a body tag.
   */
  public function replaceElementContents(
    \DOMElement $element,
    \DOMElement $replacement
  ) {
    //Remove the children of the element.
    $this->removeElementChildren($element);
    //Remove the attributes of the element.
    $this->stripElementAttributes($element);
    //Find the element to copy from.
    //$source_element = $replacement->getElementsByTagName('body')->item(0);
    //Copy the attributes of the HTML to the element.
//    $this->duplicateAttributes($replacement, $element);
    //Copy the child nodes of the HTML to the element.
    $this->copyElementChildren($replacement, $element);
  }

  /**
   * Replace the children of one DOM element with the children of another.
   *
   * @param \DOMElement $target Element to rebuild.
   * @param \DOMElement $source Element to rebuild from.
   */
  public function replaceElementChildren(
    \DOMElement $target,
    \DOMElement $source
  ) {
    //Remove the children of the element.
    $this->removeElementChildren($target);
    //Copy the child nodes of the HTML to the element.
    $this->copyElementChildren($source, $target);
  }

  /**
   * Duplicate the attributes on one element to another.
   *
   * @param \DOMElement $from Duplicate attributes from this element...
   * @param \DOMElement $to ...to this element.
   */
  public function duplicateElementAttributes(\DOMElement $from, \DOMElement $to) {
    //Remove existing attributes.
    foreach($to->attributes as $attribute) {
      $to->removeAttribute($attribute->name);
    }
    //Copy new attributes.
    foreach($from->attributes as $attribute) {
      $to->setAttribute($attribute->name, $from->getAttribute($attribute->name));
    }
  }

  /**
   * Copy the child nodes from one DomElement to another.
   *
   * @param \DOMElement $from Copy children from this element...
   * @param \DOMElement $to ...to this element.
   */
  public function copyElementChildren(\DOMElement $from, \DOMElement $to) {
    $kids = [];
    foreach ($from->childNodes as $child_node) {
      $kids[] = $child_node;
    }
    $owner_doc = $to->ownerDocument;
    foreach ($kids as $kid) {
      $to->appendChild( $owner_doc->importNode( $kid, true) );
    }
  }

  /**
   * Load HTML into a DOMDocument, with error handling.
   *
   * @param \DOMDocument $document Doc to parse the HTML.
   * @param string $html HTML to parse.
   */
  public function loadDomDocumentFromHtml(\DOMDocument $document, $html ) {
    libxml_use_internal_errors(true);
    try {
      $document->loadHTML($html);
    } catch (\Exception $e) {
    }
    $message = '';
    foreach (libxml_get_errors() as $error) {
      $message .= 'Line: ' . $error->line . ': ' . $error->message . '<br>';
    }
    libxml_clear_errors();
    libxml_use_internal_errors(false);
    if ( $message ) {
      $message = "Errors parsing HTML:<br>\n" . $message;
      \Drupal::logger('shards')->error($message);
    }
  }

  /**
   * Create a DOMDocument from some HTML.
   *
   * @param string $html HTML.
   * @return \DOMDocument DOM document created from HTML.
   */
  public function createDomDocumentFromHtml($html) {
    $domDocument = new \DOMDocument();
    $domDocument->preserveWhiteSpace = false;
    $this->loadDomDocumentFromHtml($domDocument, $html);
    return $domDocument;
  }

  /**
   * Return the value of a required attribute of an element.
   *
   * @param \DOMElement $element Element to check.
   * @param string $attribute Attribute name.
   * @return string Attribute value.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  public function getRequiredElementAttribute(\DOMElement $element, $attribute) {
    if ( ! $element->hasAttribute($attribute) ) {
      throw new ShardMissingDataException(
        sprintf('Element missing required %x attribute', $attribute)
      );
    }
    return $element->getAttribute($attribute);
  }

  /**
   * @return \Drupal\shard\ShardMetadataInterface
   */
  public function getMetadata() {
    return $this->metadata;
  }

}