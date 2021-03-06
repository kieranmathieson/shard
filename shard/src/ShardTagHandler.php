<?php
/**
 * @file
 * Does all the shard tag processing for a node.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

//use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Entity\EntityTypeManagerInterface;
//use Drupal\shard\Exceptions\ShardBadDataTypeException;
//use Drupal\shard\Exceptions\ShardException;
use Drupal\shard\Exceptions\ShardMissingDataException;
//use Drupal\shard\Exceptions\ShardDatabaseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
//use Drupal\Core\Entity\Query\QueryFactory;
//use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
//use Drupal\shard\Exceptions\ShardUnexpectedValueException;
//use Drupal\shard\Exceptions\ShardNotFoundException;
use Drupal\Core\Render\RendererInterface;
//use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\Uuid\Php;
//use Drupal\node\NodeInterface;
//use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\EventDispatcher\EventDispatcher;
//use Drupal\shard\ShardModel;

class ShardTagHandler {

  /**
   * Dep inj container.
   *
   * @var ContainerInterface;
   */
  protected $container;

  /**
   * Object holding metadata for fields and nodes.
   *
   * @var ShardMetadataInterface
   */
  protected $metadata;

  /**
   * @var ShardDomProcessor
   */
  protected $domProcessor;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Holds info about display modes.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   */
  protected $entityDisplayRepository;

  /**
   * Entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactoryInterface
   */
//  protected $entityQuery;

  /**
   * Logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Render\RendererInterface $renderer
   */
  protected $renderer;

  /**
   * Used to interact with sharders (modules that implement shards).
   *
   * @var EventDispatcher
   */
  protected $eventDispatcher;

  /**
   * Service to generate UUIDs.
   *
   * @var \Drupal\Component\Uuid\Php;
   */
  protected $uuidService;

  /**
   * ShardTagHandler constructor.
   *
   * Load shard configuration data set by admin.
   * @param \Drupal\shard\ShardMetadataInterface $metadata
   * @param \Drupal\shard\ShardDomProcessor|\Drupal\shard\ShardDomProcessorInterface $domProcessor
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param EventDispatcher $eventDispatcher
   * @param \Drupal\Component\Uuid\Php $uuidService
   */
  public function __construct(
//                       ContainerInterface $container,
                       ShardMetadataInterface $metadata,
                       ShardDomProcessorInterface $domProcessor,
                       EntityTypeManagerInterface $entity_type_manager,
                       EntityDisplayRepositoryInterface $entity_display_repository,
                       RendererInterface $renderer,
                       EventDispatcher $eventDispatcher,
                       Php $uuidService) {
//    $this->container = $container;
    $this->metadata = $metadata;
    $this->domProcessor = $domProcessor;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->renderer = $renderer;
    $this->eventDispatcher = $eventDispatcher;
    $this->uuidService = $uuidService;
    //Create a logger.
    $this->logger = \Drupal::logger('shard');
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
//      $container,
      $container->get('shard.metadata'),
      $container->get('shard.dom_processor'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('renderer'),
      $container->get('shard.event-dispatcher'),
      $container->get('uuid')
    );
  }

  /**
   * Convert the shard tags in some HTML code from CKEditor
   * format to DB format. This is called by hooks in the module
   * file. When the node is new, shard data can't be saved until
   * after the new node is created. So this method saves deets of
   * shards, and passes them back to the module for later
   * processing.
   *
   * @param string $ckHtml HTML to convert.
   * @param ShardModel[] $newShardCollectionItems Deets for shard collection
   *        items for a new host node, to be saved once the host node's nid
   *        is known.
   * @param int|string $hostNid Either a nid (existing host nodes) or
   *        a UUID (new nodes).
   * @param string $fieldName Name of the field being processed.
   * @param int $delta Which value of the field?
   * @param int $numTagsProcessed Number of shard tags processed for this HTML.
   * @return string Converted HTML.
   */
  public function ckHtmlToDbHtml($ckHtml, &$newShardCollectionItems,
                                    $hostNid, $fieldName, $delta, &$numTagsProcessed) {
    //No tags processed yet.
    $numTagsProcessed = 0;
    //Wrap content in a unique tag.
    $outerWrapperTagId = $this->uuidService->generate();
    $ckHtml = "<div id='$outerWrapperTagId'>$ckHtml</div>";
    //Create a DOM document from the HTML. PHP's DOM processor likes to work
    //with one DOM document when messing with HTML. Otherwise, you need
    //to import elements across documents.
    $domDocument = $this->domProcessor->createDomDocumentFromHtml($ckHtml);
    $wrapperDiv = $domDocument->getElementById($outerWrapperTagId);
    //Process the first shard tag found in an element.
    //Will recurse while there are more.
    $this->ckHtmlToDbHtmlProcessOneTag(
      $wrapperDiv, $newShardCollectionItems,
      $hostNid, $fieldName, $delta, $numTagsProcessed);
    //Strip out indicators that tags are processed.
    $this->domProcessor->stripProcessedMarkers($wrapperDiv);
    //Get the new content.
    $result = $domDocument->getElementById($outerWrapperTagId);
    $html = $domDocument->saveHTML( $result );
    //Strip the wrapper tag.
    $quote = "'";
    $dblQuote = '"';
    $eitherQuote = '[\\' . $quote . '\\' . $dblQuote . ']';
    $regex = '/' . "<div id={$eitherQuote}$outerWrapperTagId{$eitherQuote}>"
      . '(.*)\<\/div\>/msi';
    preg_match($regex, $html, $matches);
    $dbHtml = $matches[1];
    return $dbHtml;
  }

  /**
   * Process the first shard insertion tag in CKEditor format in some
   * HTML. Call recursively until there are no more left.
   *
   * @param \DOMElement $elementToProcess
   * @param ShardModel[] $newShardCollectionItems Deets for shard collection
   *        items for a new host node, to be saved once the host node's nid
   *        is known.
   * @param $hostNid
   * @param $fieldName
   * @param $delta
   * @param int $numTagsProcessed Number of shard tags processed for this HTML.
   */
  protected function ckHtmlToDbHtmlProcessOneTag(
      \DOMElement $elementToProcess,
      &$newShardCollectionItems,
      $hostNid, $fieldName, $delta, &$numTagsProcessed) {
    //Find the first unprocessed child shard tag
    /* @var \DOMElement $first */
    $first = $this->domProcessor->findFirstUnprocessedShardTag($elementToProcess);
    if ($first) {
      //Create a data model of the tag.
      $shardTagModel = $this->createNewShardTagModelFromCkFormat(
        $first, $hostNid, $fieldName, $delta);
      if ( is_numeric($hostNid) ) {
        //This is an existing host node, with a known nid.
        //Shard collection item ready to save.
        //Field collection item id is returned.
        $shardItemId = $shardTagModel->saveNewShardCollectionItem();
      }
      else {
        //New host node, so its nid is not known.
        //Keep the shard model for processing later.
        //Create a UUID to use as a placeholder for the shard id.
        $shardItemId = $this->uuidService->generate();
        $shardTagModel->setShardPlaceHolderId( $shardItemId );
        $newShardCollectionItems[] = $shardTagModel;
      }
      //Rebuild the tag with the DB shard format.
      //Remove existing attributes.
      $this->domProcessor->stripElementAttributes( $first );
      //Add right attributes.
      $first->setAttribute(
        ShardMetadata::SHARD_TYPE_TAG, $shardTagModel->getShardType());
      $first->setAttribute(
        ShardMetadata::SHARD_ID_ATTRIBUTE, $shardItemId);
      //Kill HTML in node.
      $this->domProcessor->removeElementChildren($first);
      //Add local content.
      $this->insertLocalContentDb($first, $shardTagModel->getLocalContent());
      //Mark as processed.
      $this->domProcessor->markShardAsProcessed($first);
      //Increment processed tag count.
      $numTagsProcessed++;
      //Process next tag.
      $this->ckHtmlToDbHtmlProcessOneTag($elementToProcess, $newShardCollectionItems,
        $hostNid, $fieldName, $delta, $numTagsProcessed);
    } // End if found a shard to process.
  }

  /**
   * Inside HTML inside an element.
   *
   * @param \DOMElement $element
   * @param string $local_content
   */
  protected function insertLocalContentDb(\DOMElement $element, $local_content ) {
    if ( $local_content ) {
//      //Make the local content wrapper that shards expect.
//      $local_content_wrapper = $element->ownerDocument->createElement('div');
//      $local_content_wrapper->setAttribute(
//        'class', ShardMetadata::SHARD_LOCAL_CONTENT_CLASS);
//      //Parse the content to add inside the wrapper.
      //Add a temp wrapper to make the local content easier to find (see below).
      $local_content = '<div id="local_content_wrapper_of_shards">' . $local_content . '</div>';
      $doc = $this->domProcessor->createDomDocumentFromHtml($local_content);
      $temp_wrapper = $doc->getElementById('local_content_wrapper_of_shards');
      //Append all the children of the local content to the wrapper element.
      foreach( $temp_wrapper->childNodes as $child_node ) {
        $element->appendChild(
          $element->ownerDocument->importNode($child_node, TRUE)
        );
      }
      //Now append the local content wrapper to the target element.
//      $element->appendChild($local_content_wrapper);
    }
  }

  /**
   * Called during presave data for a host entity. Load its original. Find all instances
   * of a given field. Find all of the shard tags in teh field. For each tag,
   * go to the shard, and erase the entry for that tag in the field_shard
   * field collection field.
   *
   * This means that no shards will reference the given field of the given entity.
   * Later, we'll rebuild the references.
   *
   * Called by hook_presave().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param string $field_name Name of the field.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  protected function eraseShardRecordsForField(EntityInterface $entity, $field_name) {
    $domDocument = new \DOMDocument();
    //Load the original of the entity.
    $original_entity = $this->entityTypeManager->getStorage('node')->loadUnchanged($entity->id());
    $original_entity_nid = $original_entity->id();
    //Grab the values of the field. Could be more than one, for multivalued
    //fields.
    $field_values = $original_entity->{$field_name}->getValue();
    //For each instance of the field...
    for($i = 0; $i < sizeof($field_values); $i++) {
      $html = $original_entity->{$field_name}[$i]->value;
      //Find the divs.
      $this->domProcessor->loadDomDocumentFromHtml($domDocument, $html);
      /* @var \DOMNodeList $divs */
      $divs = $domDocument->getElementsByTagName('div');
      //For each div...
      /* @var \DOMElement $div */
      foreach ($divs as $div) {
        if ($div->hasAttribute('data-shard-type')) {
          //Is this a shard?
          if ($div->getAttribute('data-shard-type') == 'shard') {
            //Get the item id of the field collection for the shard tag.
            $field_collection_item_id = $div->getAttribute('data-shard-id');
            if ( ! $field_collection_item_id ) {
              throw new ShardMissingDataException(
                'Could not find shard for %nid', ['%nid' => $original_entity_nid]
              );
            }
            else {
              //Erase it.
              $shard_item = $this->entityTypeManager->getStorage('field_collection_item')
                ->load($field_collection_item_id);
              $shard_item->delete();
            }
          } //End data shard type is shard.
        }
      } //End for each div.
    } //End for each field instance.
  }

  /**
   * Create a shard tag model from a tag in CKEditor format.
   * @param \DOMElement $element Element to be modelled.
   * @param int|string $hostNid Host nid for existing nodes, UUID for new one.
   * @param string $fieldName Name of the field being processed.
   * @param int $delta Delta of the field being processed.
   * @return ShardModel Model of the tag.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  protected function createNewShardTagModelFromCkFormat(
    $element, $hostNid, $fieldName, $delta) {
    $guestNid = $this->domProcessor->getRequiredElementAttribute(
      $element, ShardMetadata::SHARD_GUEST_NID_TAG);
    $shardType = $this->domProcessor->getRequiredElementAttribute(
      $element, ShardMetadata::SHARD_TYPE_TAG);
    $viewMode = $this->domProcessor->getRequiredElementAttribute(
      $element, ShardMetadata::SHARD_VIEW_FORMAT_ATTRIBUTE);
    $location = $element->getLineNo();
    $localContentElement
      = $this->domProcessor->findElementWithLocalContent($element);
    if ( $localContentElement ) {
      $localContent = trim($this->domProcessor->getElementInnerHtml($localContentElement));
    }
    else {
      $localContent = '';
    }
    //A new instance of shard.model is created on each call.
    /* @var ShardModel $shardTagModel */
    $shardTagModel = \Drupal::service('shard.model');
    $shardTagModel
      ->setGuestNid($guestNid)
      ->setHostNid($hostNid)
      ->setHostFieldName($fieldName)
      ->setDelta($delta)
      ->setShardType($shardType)
      ->setViewMode($viewMode)
      ->setLocation($location)
      ->setLocalContent($localContent);
    return $shardTagModel;
  }

  /**
   * Convert the shard tags in some HTML code from DB
   * format to view format.
   *
   * The shard filter calls this method, passing the content to change.
   * Drupal will call this method for each field and delta that it
   * applies to.
   *
   * @param string $dbHtml
   * @param string $langCode
   *   The language code of the text to be converted.
   * @return string
   */
  public function dbHtmlToViewHtml($dbHtml, $langCode) {
    $outerWrapperTagId = $this->uuidService->generate();
    $dbHtml = "<div id='$outerWrapperTagId'>$dbHtml</div>";
    //Create a DOM document from the HTML.
    $domDocument = $this->domProcessor->createDomDocumentFromHtml($dbHtml);
    $wrapperDiv = $domDocument->getElementById($outerWrapperTagId);
    //Process the first shard tag found. Recurse while there are more.
    $this->dbHtmlToViewHtmlProcessOneTag($wrapperDiv);
    //Strip out indicators that tags are processed.
    $this->domProcessor->stripProcessedMarkers($wrapperDiv);
    //Get the new content.
    $result = $domDocument->getElementById($outerWrapperTagId);
    $viewHtml = $domDocument->saveHTML($result);
    //Strip the wrapper tag.
    $quote = "'";
    $dblQuote = '"';
    $eitherQuote = '[\\' . $quote . '\\' . $dblQuote . ']';
    $regex = '/' . "<div id={$eitherQuote}$outerWrapperTagId{$eitherQuote}>"
      . '(.*)\<\/div\>/msi';
    preg_match($regex, $viewHtml, $matches);
    $viewHtml = $matches[1];
    return $viewHtml;
  }

  /**
   * Convert one shard tag from DB to view format.
   *
   * @param \DOMElement $parentElement The container element with the tag.
   */
  public function dbHtmlToViewHtmlProcessOneTag(\DOMElement $parentElement) {
    /* @var \DOMElement $first */
    $first = $this->domProcessor->findFirstUnprocessedShardTag($parentElement);
    if ($first) {
//      //Trigger event.
//      $event = new ShardTranslationEvent();
      //Get the id of the shard. This is the id of a field_collection_item entity.
      $shardId = $this->domProcessor->getRequiredElementAttribute(
        $first, ShardMetadata::SHARD_ID_ATTRIBUTE);
      //Create an object to model the shard.
      /* @var ShardModel $shard */
      $shard = \Drupal::getContainer()->get('shard.model');
      $shard->setShardId($shardId);
      $shard->loadShardCollectionItemFromStorage($shardId);
      //Inject the HTML element to show the embed.
      $shard->injectGuestNodeHtmlIntoShardTag($first);
      //Done with this tag. Mark it as processed.
      $this->domProcessor->markShardAsProcessed($first);
      //Process next tag.
      $this->dbHtmlToViewHtmlProcessOneTag($parentElement);
    } // End if found a shard to process.
  }

  /**
   * Return first shard element that has not been processed yet.
   * Applies only to DB to CKEditor conversion.
   * @param \DOMNodeList $elements Elements to search.
   * @param string $shardTypeName
   * @return \DOMElement|false An element, false if none found.
   */
  protected function findFirstUnprocessedDbToCkShard(\DOMNodeList $elements, $shardTypeName) {
    //For each element
    /* @var \DOMElement $element */
    foreach($elements as $element) {
      //Is it an element?
      if (get_class($element) == 'DOMElement') {
        //Is it a shard?
        if ($element->hasAttribute(ShardMetadata::SHARD_TYPE_TAG)) {
          //Is it the right type of shard?
          if ($element->getAttribute(ShardMetadata::SHARD_TYPE_TAG) == $shardTypeName) {
            //Is it an unprocessed tag (not converted to CK format yet)?
            $already_processed = $element->hasAttribute('class')
              && $element->getAttribute('class') == ShardMetadata::CLASS_IDENTIFYING_WIDGET;
            if ( ! $already_processed ) {
              //Yes - return the element.
              return $element;
            }
          }
        }
        //Test children.
        if ($element->hasChildNodes()) {
          $result = $this->findFirstUnprocessedDbToCkShard($element->childNodes, $shardTypeName);
          if ($result) {
            return $result;
          }
        }
      }
    }
    return false;
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
      $destination_container = $this->domProcessor->findLocalContentContainerInDoc($destination_document);
      if (! $destination_container) {
        throw new ShardMissingDataException(
          'Problem detected during shard processing. Local content, but no '
          . 'local content container.'
        );
      }
      else {
        //The local content container should have no children.
        $this->domProcessor->removeElementChildren($destination_container);
        //Copy the children of the local content to the container.
        $local_content_doc = new \DOMDocument();
        $local_content_doc->preserveWhiteSpace = FALSE;
        $this->domProcessor->loadDomDocumentFromHtml($local_content_doc, '<body>' . $local_content . '</body>');
        $local_content_domified
          = $local_content_doc->getElementsByTagName('body')->item(0);
        $this->domProcessor->copyElementChildren($local_content_domified, $destination_container);
      }
    } //End of local content.
  }

//  /**
//   * @param \DOMDocument $document
//   * @return bool|\DOMElement Element with the class local-content.
//   */
//  protected function findLocalContentContainerInDoc(\DOMDocument $document) {
//    $divs = $document->getElementsByTagName('div');
//    /* @var \DOMElement $div */
//    foreach ($divs as $div) {
//      $result = $this->findElementWithLocalContent($div);
//      if ($result) {
//        return $result;
//      }
//    }
//    return false;
//  }

//  /**
//   * Find local content within an element.
//   *
//   * @param \DOMElement $element Element to look in
//   * @return bool|\DOMElement Element with local content, false if not found.
//   */
//  protected function findElementWithLocalContent(\DOMElement $element) {
//    if ( $element->tagName == 'div'
//        && $element->hasAttribute('class')
//        && $element->getAttribute('class') == 'local-content') {
//        return $element;
//    }
//    foreach( $element->childNodes as $child ) {
//      if ( get_class($child) == 'DOMElement' ) {
//        $result = $this->findElementWithLocalContent($child);
//        if ($result) {
//          return $result;
//        }
//      }
//    }
//    return false;
//  }

//  /**
//   * Rebuild a DOM element from another.
//   *
//   * There could be a better way to do this, but the code here should
//   * be safe. It keeps the DOM-space (the DOMDocument that $element is from)
//   * intact.
//   *
//   * @param \DOMElement $element Element to rebuild.
//   * @param \DOMElement $replacement Element to rebuild from. Assume it is
//   *  wrapped in a body tag.
//   */
//  protected function replaceElementContents(
//    \DOMElement $element,
//    \DOMElement $replacement
//  ) {
//    //Remove the children of the element.
//    $this->removeElementChildren($element);
//    //Remove the attributes of the element.
//    $this->stripAttributes($element);
//    //Find the element to copy from.
//    //$source_element = $replacement->getElementsByTagName('body')->item(0);
//    //Copy the attributes of the HTML to the element.
////    $this->duplicateAttributes($replacement, $element);
//    //Copy the child nodes of the HTML to the element.
//    $this->copyChildren($replacement, $element);
//  }
//
//  /**
//   * Duplicate the attributes on one element to another.
//   *
//   * @param \DOMElement $from Duplicate attributes from this element...
//   * @param \DOMElement $to ...to this element.
//   */
//  protected function duplicateAttributes(\DOMElement $from, \DOMElement $to) {
//    //Remove existing attributes.
//    foreach($to->attributes as $attribute) {
//      $to->removeAttribute($attribute->name);
//    }
//    //Copy new attributes.
//    foreach($from->attributes as $attribute) {
//      $to->setAttribute($attribute->name, $from->getAttribute($attribute->name));
//    }
//  }
//
//  /**
//   * Copy the child nodes from one DomElement to another.
//   *
//   * @param \DOMElement $from Copy children from this element...
//   * @param \DOMElement $to ...to this element.
//   */
//  protected function copyChildren(\DOMElement $from, \DOMElement $to) {
//    $kids = [];
//    foreach ($from->childNodes as $child_node) {
//      $kids[] = $child_node;
//    }
//    $owner_doc = $to->ownerDocument;
//    foreach ($kids as $kid) {
//      $to->appendChild( $owner_doc->importNode( $kid, true) );
//    }
//  }

  /**
   * @param $dbHtml
   * @return string
   * @internal param $html
   */
  public function dbHtmlToCkHtml($dbHtml) {
    //Wrap content in a unique tag.
    $outerWrapperTagId = $this->uuidService->generate();
    $dbHtml = "<div id='$outerWrapperTagId'>$dbHtml</div>";
    //Create a DOM document from the HTML. PHP's DOM processor likes to work
    //with one DOM document when messing with HTML. Otherwise, you need
    //to import elements across documents.
    $domDocument = $this->domProcessor->createDomDocumentFromHtml($dbHtml);
    $wrapperDiv = $domDocument->getElementById($outerWrapperTagId);
    //Process the first shard tag found. Recurse while there are more.
    $this->dbHtmlToCkHtmlProcessOneTag($wrapperDiv);
    //Strip out indicators that tags are processed.
    $this->domProcessor->stripProcessedMarkers($wrapperDiv);
    //Get the new content.
    $result = $domDocument->getElementById($outerWrapperTagId);
    $ckHtml = $domDocument->saveHTML( $result );
    //Strip the wrapper tag.
    $quote = "'";
    $dblQuote = '"';
    $eitherQuote = '[\\' . $quote . '\\' . $dblQuote . ']';
    $regex = '/' . "<div id={$eitherQuote}$outerWrapperTagId{$eitherQuote}>"
      . '(.*)\<\/div\>/msi';
    preg_match($regex, $ckHtml, $matches);
    $ckHtml = $matches[1];
    return $ckHtml;
  }

  /**
   * Convert one shard tag from DB to CKEditor format.
   *
   * @param \DOMElement $parentElement The container.
   */
  public function dbHtmlToCkHtmlProcessOneTag(\DOMElement $parentElement) {
    //Is there are shard tag in the document.
    /* @var \DOMElement $first */
    $first = $this->domProcessor->findFirstUnprocessedShardTag($parentElement);
    if ($first) {
      $shardId = $this->domProcessor->getRequiredElementAttribute(
        $first, ShardMetadata::SHARD_ID_ATTRIBUTE);
      $shardType = $this->domProcessor->getRequiredElementAttribute(
        $first, ShardMetadata::SHARD_TYPE_TAG);
      //Load the shard.
      $shard = \Drupal::getContainer()->get('shard.model');
//      $shard = new ShardTagModel(
//        \Drupal::service('database'),
//        \Drupal::service('shard.metadata'),
//        \Drupal::service('entity_type.manager'),
//        \Drupal::service('renderer'),
//        \Drupal::service('shard.dom_processor')
//      );
      $shard->loadShardCollectionItemFromStorage($shardId);
      //Change tag attributes, from DB to CK format.
      $shard->setShardType($shardType);
      //Add the guest nid to the element for CK.
      $first->setAttribute(ShardMetadata::SHARD_GUEST_NID_TAG, $shard->getGuestNid());
      //Add the view mode to the element for CK.
      $viewMode = $shard->getViewMode();
      $first->setAttribute(ShardMetadata::SHARD_VIEW_FORMAT_ATTRIBUTE, $viewMode);
      //Add the class that the widget uses to see that an element is a widget.
      $first->setAttribute('class',
        str_replace('[type]', $shard->getShardType(), ShardMetadata::CLASS_IDENTIFYING_WIDGET));
      //Remove the shard id. Not part of the CK format.
      $first->removeAttribute(ShardMetadata::SHARD_ID_ATTRIBUTE);
      //Now insert the content of the shard element being processed.
      $shard->injectGuestNodeHtmlIntoShardTag($first);
      //Done with this tag. Mark it as processed.
      $this->domProcessor->markShardAsProcessed($first);
      //Done with this tag.
      //Process next tag.
      $this->dbHtmlToCkHtmlProcessOneTag($parentElement);


      //Add local content.
//      if ($localContent) {
//        $this->insertLocalContentIntoViewHtml( $viewDocument, $localContent );
//      }

      //Make the HTML element to show the embed.
//      $embeddingElement = $shard->createShardEmbeddingElement();
      //Insert the HTML into the wrapper tag.
//      $this->domProcessor->replaceElementChildren($first, $embeddingElement);
//      //Render the selected display of the shard.
//      $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
//      $renderArray = $viewBuilder->view($guestNode, $viewMode);
//      $viewHtml = (string)$this->renderer->renderRoot($renderArray);
//      $view_builder = $this->entityTypeManager->getViewBuilder('node');
//      $render_array = $view_builder->view($shard_node, $view_mode);
//      $view_html = (string) $this->renderer->renderRoot($render_array);
//      //DOMify it.
//      $view_document = new \DOMDocument();
//      $view_document->preserveWhiteSpace = FALSE;
//      //Wrap in a body tag to make processing easier.
//      $this->domProcessor->loadDomDocumentFromHtml($view_document, '<body>' . $view_html . '</body>');
//      //Get local content.
//      $local_content = $shard_field_collection_item
//        ->get('field_custom_content')->getString();
//      //Add local content, if any, to the rendered display. The rendered view
//      // mode must have a div with the class local-content.
//      if ($local_content) {
//        $this->insertLocalContentIntoViewHtml($view_document, $local_content);
//      }
      //Replace the inner tags of the DB version of the shard insertion tag with the view
      // version, keeping the wrapper tag in place.
//      $this->domProcessor->removeElementChildren($first);
//      $this->domProcessor->copyElementChildren(
//        $view_document->getElementsByTagName('body')->item(0),
//        $first
//      );
    } // End if found a shard to process.
  }


//  /**
//   * Get the view mode stored in a shard collection item.
//   *
//   * @param \Drupal\field_collection\Entity\FieldCollectionItem $collectionItem
//   * @return string The view mode.
//   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
//   */
//  protected function getCollectionViewMode(FieldCollectionItem $collectionItem) {
//    //Get the view mode.
//    $view_mode = $this->getRequiredShardValue(
//      $collectionItem,
//      'field_display_mode'
//    );
//    //Does the view mode exist?
//    $all_view_modes = $this->entityDisplayRepository->getViewModes('node');
//    if ( ! key_exists($view_mode, $all_view_modes) ) {
//      throw new ShardUnexpectedValueException(
//        sprintf('Unknown shard view mode: %s', $view_mode)
//      );
//    }
//    return $view_mode;
//  }


//  /**
//   * Get the shard node referenced by a shard collection item.
//   *
//   * @param \Drupal\field_collection\Entity\FieldCollectionItem $collectionItem
//   * @return \Drupal\Core\Entity\EntityInterface Shard node.
//   * @throws \Drupal\shard\Exceptions\ShardNotFoundException
//   */
//  protected function getCollectionItemShard(FieldCollectionItem $collectionItem) {
//    $shard_nid = $collectionItem->getHostId();
//    $shard_node = $this->entityTypeManager->getStorage('node')->load($shard_nid);
//    //Does the shard exist?
//    if ( ! $shard_node ) {
//      throw new ShardNotFoundException('Cannot find shard ' . $shard_nid);
//    }
//    return $shard_node;
//  }


}