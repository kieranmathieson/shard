<?php
/**
 * @file
 * Does all the shard tag processing for a node.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\shard\Exceptions\ShardBadDataTypeException;
use Drupal\shard\Exceptions\ShardMissingDataException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;
use Drupal\shard\Exceptions\ShardNotFoundException;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Database\Connection;

class ShardDomProcessor {

  /**
   * ShardTagHandler constructor.
   *
   * Load shard configuration data set by admin.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Database\Connection $database_connection
   * @internal param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(
//                       EligibleFieldsInterface $eligible_fields,
                       EntityTypeManagerInterface $entity_type_manager,
                       EntityDisplayRepositoryInterface $entity_display_repository,
                       QueryFactory $entity_query,
                       RendererInterface $renderer,
                       Connection $database_connection) {
//    $this->eligibleFields = $eligible_fields;
    $this->eligibleFields = new EligibleFields(
      \Drupal::service('config.factory'),
      \Drupal::service('entity_field.manager')
    );
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;

  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
//      $container->get('shard.eligible_fields'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('renderer'),
      $container->get('database')
    );
  }



  /**
   * Get the HTML represented by a DOMElement.
   *
   * @param \DOMElement $element The element.
   * @return string HTML The HTML.
   */
  protected function getDomElementOuterHtml(\DOMElement $element) {
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
  protected function getDomElementInnerHtml(\DOMElement $element){
    $result = '';
    foreach( $element->childNodes as $child ) {
      if ( get_class($child) == 'DOMText' ) {
        $result .= $child->wholeText;
      }
      else {
        $result .= $this->getDomElementOuterHtml($child);
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
  protected function findFirstWithClass(\DOMNodeList $elements, $class) {
    return $this->findFirstWithAttribute($elements, 'class', $class);
  }

  /**
   * Remove all of the chldren from a DOM element.
   *
   * @param  \DOMElement $element
   */
  protected function removeElementChildren(\DOMElement $element) {
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
  protected function stripAttributes(\DOMElement $element) {
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
   * @param string $value Value to check for.
   * @return \DOMElement|false An element.
   */
  protected function findFirstWithAttribute(\DOMNodeList $elements, $attribute, $value) {
    //For each element
    /* @var \DOMElement $element */
    foreach($elements as $element) {
      //Is it an element?
      if (get_class($element) == 'DOMElement') {
        //Does it have the attribute and value?
        if ($element->hasAttribute($attribute)) {
          if ($element->getAttribute($attribute) == $value) {
            //Yes - return the element.
            return $element;
          }
        }
        //Test children.
        if ($element->hasChildNodes()) {
          $result = $this->findFirstWithAttribute($element->childNodes, $attribute, $value);
          if ($result) {
            return $result;
          }
        }
      }
    }
    return false;
  }



  /**
   * Get a shard id from a tag.
   *
   * @param \DOMElement $element The tag.
   * @return string The shard id.
   * @throws \Drupal\shard\Exceptions\ShardBadDataTypeException
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  protected function getShardId(\DOMElement $element) {
    $shard_id = $element->getAttribute('data-shard-id');
    if ( ! $shard_id ) {
      throw new ShardMissingDataException('Shard id missing for shard DB tag.');
    }
    if ( ! is_numeric($shard_id) ) {
      throw new ShardBadDataTypeException(
        sprintf('Argh! Shard id is not numeric: %s.', $shard_id)
      );
    }
    return $shard_id;
  }

  /**
   * Get the value of a required field from a shard.
   *
   * @param FieldCollectionItem $shard Field collection item
   *        with shard insertion data.
   * @param string $field_name Name of the field whose value is needed.
   * @return mixed Field's value.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  protected function getRequiredShardValue(FieldCollectionItem $shard, $field_name) {
    $value = $shard->{$field_name}->getString();
    if ( strlen($value) == 0 ) {
      throw new ShardMissingDataException(
        sprintf('Missing required shard field value: %s', $field_name)
      );
    }
    return $value;
  }

  /**
   * Add local content to HTML of a view of a shard.
   * The view HTML must has a div with the class local-content.
   *
   * @param \DOMDocument $destination_document HTML to insert local content into.
   * @param string $local_content HTML to insert.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  protected function insertLocalContentIntoViewHtml(\DOMDocument $destination_document, $local_content) {
    if ( $local_content ) {
      $destination_container = $this->findLocalContentContainerInDoc($destination_document);
      if (! $destination_container) {
        throw new ShardMissingDataException(
          'Problem detected during shard processing. Local content, but no '
          . 'local content container.'
        );
      }
      else {
        //The local content container should have no children.
        $this->removeElementChildren($destination_container);
        //Copy the children of the local content to the container.
        $local_content_doc = new \DOMDocument();
        $local_content_doc->preserveWhiteSpace = FALSE;
        $this->loadDomDocumentHtml($local_content_doc, '<body>' . $local_content . '</body>');
        $local_content_domified
          = $local_content_doc->getElementsByTagName('body')->item(0);
        $this->copyChildren($local_content_domified, $destination_container);
      }
    } //End of local content.
  }

  /**
   * @param \DOMDocument $document
   * @return bool|\DOMElement Element with the class local-content.
   */
  protected function findLocalContentContainerInDoc(\DOMDocument $document) {
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
  protected function findElementWithLocalContent(\DOMElement $element) {
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
  protected function replaceElementContents(
    \DOMElement $element,
    \DOMElement $replacement
  ) {
    //Remove the children of the element.
    $this->removeElementChildren($element);
    //Remove the attributes of the element.
    $this->stripAttributes($element);
    //Find the element to copy from.
    //$source_element = $replacement->getElementsByTagName('body')->item(0);
    //Copy the attributes of the HTML to the element.
//    $this->duplicateAttributes($replacement, $element);
    //Copy the child nodes of the HTML to the element.
    $this->copyChildren($replacement, $element);
  }

  /**
   * Duplicate the attributes on one element to another.
   *
   * @param \DOMElement $from Duplicate attributes from this element...
   * @param \DOMElement $to ...to this element.
   */
  protected function duplicateAttributes(\DOMElement $from, \DOMElement $to) {
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
  protected function copyChildren(\DOMElement $from, \DOMElement $to) {
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
   * Get the view mode stored in a shard collection item.
   *
   * @param \Drupal\field_collection\Entity\FieldCollectionItem $collectionItem
   * @return string The view mode.
   * @throws \Drupal\shard\Exceptions\ShardUnexptectedValueException
   */
  protected function getCollectionViewMode(FieldCollectionItem $collectionItem) {
    //Get the view mode.
    $view_mode = $this->getRequiredShardValue(
      $collectionItem,
      'field_display_mode'
    );
    //Does the view mode exist?
    $all_view_modes = $this->entityDisplayRepository->getViewModes('node');
    if ( ! key_exists($view_mode, $all_view_modes) ) {
      throw new ShardUnexptectedValueException(
        sprintf('Unknown shard view mode: %s', $view_mode)
      );
    }
    return $view_mode;
  }


  /**
   * Get the shard node referenced by a shard collection item.
   *
   * @param \Drupal\field_collection\Entity\FieldCollectionItem $collectionItem
   * @return \Drupal\Core\Entity\EntityInterface Shard node.
   * @throws \Drupal\shard\Exceptions\ShardNotFoundException
   */
  protected function getCollectionItemShard(FieldCollectionItem $collectionItem) {
    $shard_nid = $collectionItem->getHostId();
    $shard_node = $this->entityTypeManager->getStorage('node')->load($shard_nid);
    //Does the shard exist?
    if ( ! $shard_node ) {
      throw new ShardNotFoundException('Cannot find shard ' . $shard_nid);
    }
    return $shard_node;
  }


  /**
   * Load HTML into a DOMDocument, with error handling.
   *
   * @param \DOMDocument $dom_document Doc to parse the HTML.
   * @param string $html HTML to parse.
   */
  protected function loadDomDocumentHtml( \DOMDocument $dom_document, $html ) {
    libxml_use_internal_errors(true);
    try {
      $dom_document->loadHTML($html);
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
}