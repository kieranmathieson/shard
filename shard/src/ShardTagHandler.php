<?php
/**
 * @file
 * Does all the shard tag processing for a node.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
//use Drupal\shard\Exceptions\ShardBadDataTypeException;
use Drupal\shard\Exceptions\ShardException;
use Drupal\shard\Exceptions\ShardMissingDataException;
//use Drupal\shard\Exceptions\ShardDatabaseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
//use Drupal\shard\Exceptions\ShardUnexpectedValueException;
//use Drupal\shard\Exceptions\ShardNotFoundException;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Database\Connection;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;


class ShardTagHandler {


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
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;

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
   * @var \Drupal\Core\Database\Connection
   */
  protected $databaseConnection;

  /**
   * Used to interact with sharders (modules that implement shards).
   *
   * @var ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * ShardTagHandler constructor.
   *
   * Load shard configuration data set by admin.
   * @param \Drupal\shard\ShardMetadataInterface $metadata
   * @param \Drupal\shard\ShardDomProcessor $domProcessor
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Database\Connection $database_connection
   * @param \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher $eventDispatcher
   * @internal param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(
                       ShardMetadataInterface $metadata,
                       ShardDomProcessor $domProcessor,
                       EntityTypeManagerInterface $entity_type_manager,
                       EntityDisplayRepositoryInterface $entity_display_repository,
                       QueryFactory $entity_query,
                       RendererInterface $renderer,
                       Connection $database_connection,
                       ContainerAwareEventDispatcher $eventDispatcher) {
    $this->metadata = $metadata;
    $this->domProcessor = $domProcessor;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityQuery = $entity_query;
    $this->renderer = $renderer;
    $this->databaseConnection = $database_connection;
    $this->eventDispatcher = $eventDispatcher;
    //Create a logger.
    $this->logger = \Drupal::logger('shard');
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shard.metadata'),
      $container->get('shard.dom_processor'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('renderer'),
      $container->get('database'),
      $container->get('event-dispatcher')
    );
  }

  /**
   * Convert shard tags from their CKEditor version to their DB storage
   * version for all eligible fields in $entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public function entityCkTagsToDbTags(EntityInterface $entity) {
    if ( ! $entity->isNew() ) {
      //Get the nid of the node containing the references.
      $host_nid = $entity->id();
    }
    else {
      //Use a temporary fake nid. It will be replaced when the real nid is
      //available.
      $host_nid = $this->computeTempNid();
      //Save it for hook_node_insert().
      $_REQUEST['temp_nid'] = $host_nid;
    }
    $this->shardInsertionDetails->setHostNid($host_nid);
    //Get the names of the fields that are eligible for shards.
    $eligible_fields = $this->eligibleFields->listEntityEligibleFields($entity);
    foreach($eligible_fields as $field_name) {
      try {
        $this->shardInsertionDetails->setFieldName($field_name);
        if ( ! $entity->isNew() ) {
          //Erase all shard references to the old version this field. Rebuild them later.
          $this->eraseShardRecordsForField($entity, $field_name);
        }
        //Loop over each value for this field (could be multivalued).
        $field_values = $entity->{$field_name}->getValue();
        for ($delta = 0; $delta < sizeof($field_values); $delta++) {
          $this->shardInsertionDetails->setDelta($delta);
          //Translate the HTML.
          //This will also update the field collection in the shards nodes
          //to show the shards' use in the host nodes.
          $this->shardInsertionDetails->setCkHtml(
            $entity->{$field_name}[$delta]->value
          );
          $this->ckHtmlToDbHtml();
          //Save the new HTML into the entity.
          $entity->{$field_name}[$delta]->value
            = $this->shardInsertionDetails->getDbHtml();
        } //End foreach value of the field
      } catch (ShardException $e) {
        $message = t(
          'Problem detected during shard processing for the field %field. '
          . 'It has been recorded in the log. Deets:', ['%field' => $field_name])
          . '<br><br>' . $e->getMessage();
        drupal_set_message($message, 'error');
        \Drupal::logger('shards')->error($message);
      }
    } // End for each eligible field.
  }

  public function replaceTempNid(EntityInterface $entity) {
    //Get the nid used, and the one to replace it with.
    $real_nid = $entity->id();
    $temp_nid = $_REQUEST['temp_nid'];
    //Get all the field collection items that reference the temp nid.
    $query = $this->entityQuery->get('field_collection_item')
      ->condition('field_name', 'field_shard')
      ->condition('field_host_node', $temp_nid);
    $result = $query->execute();
    $nodes = $this->$this->nodeStorage->loadMultiple($result);
    foreach($nodes as $node) {
      $node->field_host_node->value = $real_nid;
      $node->save();
    }
  }

  /**
   * Convert the shard tags in some HTML code from CKEditor
   * format to DB format.
   *
   * @param string $ckHtml HTML to convert.
   * @return string Result.
   */
  protected function ckHtmlToDbHtml($ckHtml) {
    //Wrap content in a unique tag.
    $ckHtml = '<body>' . $ckHtml . '</body>';
    $domDocument = $this->domProcessor->createDomDocumentFromHtml($ckHtml);
    //Work through all of the defined sharders.
    foreach($this->metadata->getShardTypeNames() as $shardTypeName) {
      /* Tags of the most inner shards should be collapsed to
       * DB format first. E.g., for:
       *
       * BODY-----T1
       *    |
       *    T2-----T3
       *    |
       *    T4-----T5
       *    |      |
       *    |      T6
       *    |
       *    T7
       *
       * T3 should be processed before T2. T5 and T6 before T4.
       */
      //Create a tree for the shard tags in the HTML.
      $shardTree = new ShardTagModel(
        $domDocument->getElementsByTagName('body')->item(0)
      );
      //Depth first processing of the tree. Process the child elements first.








      //Process the first shard tag found. Will recurse while there are more.
      //Doing one at a time allows for tag nesting.
      //The called function also adds a shard field collection item to the shard
      //node referred to by a shard tag in the HTML.
      $this->ckHtmlToDbHtmlProcessOneTag($domDocument, $shardTypeName);
    }
    //Get the new content.
    $body = $domDocument->getElementsByTagName('body')->item(0);
    $dbHtml = $domDocument->saveHTML( $body );
    //Strip the body tag.
    preg_match("/\<body\>(.*)\<\/body\>/msi", $dbHtml, $matches);
    $dbHtml = $matches[1];
    return $dbHtml;
  }

  protected function ckHtmlToDbHtmlProcessChildren(ShardTagModel $shardTagModel) {
    foreach( $shardTagModel->getChildTagModels() as $childTagModel ) {
      $this->ckHtmlToDbHtmlProcessChildren($childTagModel);
    }

  }

  /**
   * Process the first shard insertion tag in CKEditor format in some
   * HTML. Call recursively until there are no more left.
   *
   * @param \DOMDocument $domDocument
   * @param $shardTypeName
   * @internal param \DOMDocument $domDoc
   */
  protected function ckHtmlToDbHtmlProcessOneTag(\DOMDocument $domDocument, $shardTypeName ) {
    /* @var \DOMNodeList $divs */
    $divs = $domDocument->getElementsByTagName('div');
    /* @var \DOMElement $first */
    $first = $this->domProcessor->findFirstUnprocessedShardTag($divs, $shardTypeName);
    if ($first) {
      //Extract data about the shard to insert, add to the object used
      //to collect data about the current insertion.
      $this->cacheTagDetails($first);
      //Create shard field collection record, and add it to the
      //shard's shard field on the shard's node.
      // Get back the item_id of the record.
      //item_id is the PK of the field_collection entity.
      $item_id = $this->addShardToShard();
      //Rebuild the tag with the DB shard format.
      //Remove existing attributes.
      $this->domProcessor->stripElementAttributes( $first );
      //Add right attributes.
      $first->setAttribute(ShardMetaData::SHARD_TYPE_TAG, 'shard');
//      $first->setAttribute(
//        'data-shard-id',
//        $this->shardInsertionDetails->getShardNid()
//      );
      $first->setAttribute(
        'data-shard-id',
        $item_id
      );
      //Kill HTML in node.
      $this->domProcessor->removeElementChildren($first);
      //Add local content, if any.
      if ($this->shardInsertionDetails->getLocalContent()) {
        $this->insertLocalContentDb(
          $first,
          $this->shardInsertionDetails->getLocalContent()
        );
      }
      //Process next tag.
      $this->ckHtmlToDbHtmlProcessOneTag($domDoc);
    } // End if found a shard to process.
  }

//  /**
//   * Add data about a shard tag to a cache object, used as a convenient
//   * holding place. A bag of (shard) holding.
//   * @param \DOMElement $element The shard shard tag.
//   */
//  protected function cacheTagDetails(\DOMElement $element) {
//    //Get the shard's view mode.
//    $this->shardInsertionDetails->setViewMode( $this->getViewModeOfElement($element) );
//    //Get the shard's nid.
//    $this->shardInsertionDetails->setShardNid( $this->getShardNid($element) );
//    //Get the shard's location.
//    $this->shardInsertionDetails->setLocation( $element->getLineNo() );
//    //Get the shard's local content container.
//    /* @var \DOMElement $local_content_container */
//    $local_content_container = $this->findElementWithLocalContent($element);
//    if ( $local_content_container ) {
//      //Get its HTML.
//      $local_html = $this->getDomElementInnerHtml($local_content_container);
//    }
//    else {
//      $local_html = '';
//    }
//    $this->shardInsertionDetails->setLocalContent($local_html);
//  }

//  /**
//   * Get the HTML represented by a DOMElement.
//   *
//   * @param \DOMElement $element The element.
//   * @return string HTML The HTML.
//   */
//  protected function getDomElementOuterHtml(\DOMElement $element) {
//    $tmp_doc = new \DOMDocument();
//    //Make sure there's a body element.
//    if ( $element->tagName != 'body' && $element->getElementsByTagName('body')->length == 0 ) {
//      $tmp_doc->appendChild( $tmp_doc->createElement('body') );
//      $body = $tmp_doc->getElementsByTagName('body')->item(0);
//      $body->appendChild($tmp_doc->importNode($element, TRUE));
//    }
//    else {
//      $tmp_doc->appendChild($tmp_doc->importNode($element, TRUE));
//    }
//    $html = $tmp_doc->saveHTML( $tmp_doc->getElementsByTagName('body')->item(0) );
//    preg_match("/\<body\>(.*)\<\/body\>/msi", $html, $matches);
//    $html = $matches[1];
//    return $html;
//  }

//  /**
//   * Get the inner HTML (i.e., HTML of the children) of a DOM element.
//   *
//   * @param \DOMElement $element Element to process.
//   * @return string The HTML.
//   */
//  protected function getDomElementInnerHtml(\DOMElement $element){
//    $result = '';
//    foreach( $element->childNodes as $child ) {
//      if ( get_class($child) == 'DOMText' ) {
//        $result .= $child->wholeText;
//      }
//      else {
//        $result .= $this->getDomElementOuterHtml($child);
//      }
//    }
//    return $result;
//  }
//
//  /**
//   * Return first element with a given class.
//   * @param \DOMNodeList $elements
//   * @param string $class Class to find.
//   * @return \DOMElement|false Element with class.
//   */
//  protected function findFirstWithClass(\DOMNodeList $elements, $class) {
//    return $this->findFirstWithAttribute($elements, 'class', $class);
//  }
//
//  /**
//   * Remove all of the chldren from a DOM element.
//   *
//   * @param  \DOMElement $element
//   */
//  protected function removeElementChildren(\DOMElement $element) {
//    $children = [];
//    if ( $element->hasChildNodes() ) {
//      foreach ( $element->childNodes as $child_node ){
//        $children[] = $child_node;
//      }
//      foreach ( $children as $child ) {
//        $element->removeChild($child);
//      }
//    }
//  }

//  /**
//   * Find the local content from a shard tag in CK format.
//   *
//   * @param \DOMElement $element Element to search for local content.
//   * @return string Local content. Empty if none.
//   */
//  protected function getLocalContentFromCkTag(\DOMElement $element ) {
//    //Get the shard's local content.
//    /* @var \DOMNodeList $internal_divs */
//    $internal_divs = $element->getElementsByTagName('div');
//    /* @var \DOMElement $internal_div */
//    foreach ($internal_divs as $internal_div) {
//      if ($internal_div->hasAttribute('class')) {
//        $classes = $internal_div->getAttribute('class');
//        if (strpos($classes, 'local-content') !== FALSE) {
//          $result = '';
//          /* @var \DOMNode $child */
//          foreach ($internal_div->childNodes as $child) {
//            $result .= trim($child->C14N());
//          }
//          return $result;
//        }
//      }
//    } //End foreach
//    return '';
//  }

  /**
   * Inside an element, append a wrapper div with the class local-content,
   * that has inside it the contents of $local_content.
   *
   * @param \DOMElement $element
   * @param string $local_content
   */
  protected function insertLocalContentDb(\DOMElement $element, $local_content ) {
    if ( $local_content ) {
      //Make the local content wrapper that shards expect.
      $local_content_wrapper = $element->ownerDocument->createElement('div');
      $local_content_wrapper->setAttribute('class', 'local-content');
      //Parse the content to add inside the wrapper.
      //Add a temp wrapper to make the local content easier to find (see below).
      $local_content = '<div id="local_content_wrapper_of_shards">' . $local_content . '</div>';
      $doc = new \DOMDocument();
      $doc->preserveWhiteSpace = false;
      $this->domProcessor->loadDomDocumentElementHtml($doc, $local_content);
      $temp_wrapper = $doc->getElementById('local_content_wrapper_of_shards');
      //Append all the children of the local content to the wrapper element.
      foreach( $temp_wrapper->childNodes as $child_node ) {
        $local_content_wrapper->appendChild(
          $element->ownerDocument->importNode($child_node, TRUE)
        );
      }
      //Now append the local content wrapper to the target element.
      $element->appendChild($local_content_wrapper);
    }
  }

//  /**
//   * Get the view mode of the element.
//   *
//   * @param \DOMElement $element
//   * @return string View mode.
//   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
//   */
//  protected function getViewModeOfElement(\DOMElement $element) {
//    $view_mode = $element->getAttribute('data-view-mode');
//    if ( ! $view_mode ) {
//      throw new ShardMissingDataException(
//        'Could not find view mode for shard in %nid',
//        ['%nid' => $this->shardInsertionDetails->getHostNid() ]
//      );
//    }
//    return $view_mode;
//  }
//
//  /**
//   * Get the shard nid from a shard element.
//   *
//   * @param \DOMElement $element
//   * @return int Nid.
//   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
//   */
//  protected function getShardNid(\DOMElement $element) {
//    $nid = $element->getAttribute('data-shard-id');
//    if ( ! $nid ) {
//      throw new ShardMissingDataException(
//        'Could not find id for shard in %nid',
//        ['%nid' => $this->shardInsertionDetails->getHostNid() ]
//      );
//    }
//    return $nid;
//  }

//  /**
//   * Strip the attributes of an element.
//   *
//   * @param \DOMElement $element
//   */
//  protected function stripAttributes(\DOMElement $element) {
//    $attributes = $element->attributes;
//    $attribute_names = [];
//    foreach( $attributes as $attribute => $value ) {
//      $attribute_names[] = $attribute;
//    }
//    foreach ($attribute_names as $attribute_name) {
//      $element->removeAttribute($attribute_name);
//    }
//  }

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
   * Add a record to the shard field of shard, recording the insertion.
   * @return int Item id of the new record.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  protected function addShardToShard() {
    $shard = $this->entityTypeManager->getStorage('node')->load(
      $this->shardInsertionDetails->getShardNid()
    );
    if ( ! $shard ) {
      throw new ShardMissingDataException(
        'Could not find shard %nid', [
          '%nid' => $this->shardInsertionDetails->getShardNid()
      ]);
    }
    $shard_record = FieldCollectionItem::create([
      //field_name is the bundle setting. The field collection type of the
      //field collection entity.
      'field_name' => 'field_shard',
      'field_host_node' => $this->shardInsertionDetails->getHostNid(),
      'field_host_field' => $this->shardInsertionDetails->getFieldName(),
      'field_host_field_instance' => $this->shardInsertionDetails->getDelta(),
      'field_display_mode' => $this->shardInsertionDetails->getViewMode(),
      'field_shard_location' => $this->shardInsertionDetails->getLocation(),
      'field_custom_content' => $this->shardInsertionDetails->getLocalContent(),
    ]);
    $shard_record->setHostEntity($shard);
    $shard_record->save();
    $item_id = $shard_record->id();
    return $item_id;
  }

  /**
   * Convert shard tags from their database version to their display
   * version for all eligible fields in $entity. Do this for the
   * $build array that's passed to hook_node_view_alter().
   * @param $build
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
//  public function dbTagsToViewTags(&$build, EntityInterface $entity) {
//    //Get the names of the fields that are eligible for shards.
//    $eligible_fields = $this->eligibleFields->listEntityEligibleFields($entity);
//    foreach($eligible_fields as $field_name) {
//      try {
//        //Is there a build element for the eligible field?
//        if ( isset($build[$field_name]) ) {
//          if ( isset($build[$field_name]['#field_type']) ) {
//            $field_type = $build[$field_name]['#field_type'];
//            //Loop over the array elements for the field. The ones that have
//            //numeric indexes are values.
//            foreach ( $build[$field_name] as $delta => $element ) {
//              if ( is_numeric($delta) ) {
//                $build[$field_name][$delta]['#text']
//                  = $this->dbHtmlToViewHtml( $build[$field_name][$delta]['#text'] );
//
//                //Values to convert depends on what type of field it is.
//                if ( $field_name == 'text_with_summary' ) {
//
//
//                }
//              }
//            }
//
//            //Get the values.
//            $items_to_show = $build[$field_name]['#items']->getValue();
//            for($delta = 0; $delta < sizeof($items_to_show); $delta++) {
//              $items_to_show[$delta]['value'] = '<p>spit</p>';
//            }
//            $build[$field_name]['#items']->setValue($items_to_show);
//          }
//        }
//
//      } catch (ShardException $e) {
//        $message = t(
//            'Problem detected during shard processing for the field %field. '
//            . 'It has been recorded in the log. Deets:', ['%field' => $field_name])
//          . '<br><br>' . $e->getMessage();
//        drupal_set_message($message, 'error');
//        \Drupal::logger('shards')->error($message);
//      }
//    } // End for each eligible field.
//  }

  /**
   * Convert the shard tags in some HTML code from DB
   * format to view format.
   * @param string $dbHtml
   * @return string
   */
  public function dbHtmlToViewHtml($dbHtml) {
    //Wrap content in a unique tag.
    $dbHtml = '<body>' . $dbHtml . '</body>';
    $domDocument = $this->domProcessor->createDomDocumentFromHtml($dbHtml);
    //Process the first shard tag found. Recurse while there are more.
    //Doing one at a time allows for tag nesting.
    $this->dbHtmlToViewHtmlProcessOneTag($domDocument);
    //Get the new content.
    $body = $domDocument->getElementsByTagName('body')->item(0);
    $viewHtml = $domDocument->saveHTML( $body );
    //Strip the body tag.
    preg_match("/\<body\>(.*)\<\/body\>/msi", $viewHtml, $matches);
    $viewHtml = $matches[1];
    return $viewHtml;
  }

  /**
   * Convert one shard tag from DB to view format.
   *
   * @param \DOMDocument $domDocument The document with the tag.
   */
  public function dbHtmlToViewHtmlProcessOneTag(\DOMDocument $domDocument) {
    /* @var \DOMNodeList $divs */
    $divs = $domDocument->getElementsByTagName('div');
    /* @var \DOMElement $first */
    $first = $this->domProcessor->findFirstUnprocessedShardTag($divs);
    if ($first) {
//      //Trigger event.
//      $event = new ShardTranslationEvent();
      //Get the id of the shard. This is the id of a field_collection_item entity.
      $shardId = $this->domProcessor->getRequiredElementAttribute(
        $first, ShardMetaData::SHARD_ID_TAG);
      //Load the shard.
      $shard = new Shard(
        \Drupal::service('database'),
        \Drupal::service('shard.metadata'),
        \Drupal::service('entity_type.manager'),
        \Drupal::service('renderer'),
        \Drupal::service('shard.dom_processor')
      );
      $shard->setShardId($shardId);
      $shard->loadShardCollectionItemFromStorage();
      //Make the HTML element to show the embed.
      $embeddingElement = $shard->createShardEmbeddingElement();
//      //Load the node to be embedded.
//      $guestNode = $shard->loadGuestNodeFromStorage();
//      //Render the selected display of the shard.
//      $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
//      $viewMode = $shard->getViewMode();
//      $renderArray = $viewBuilder->view($guestNode, $viewMode);
//      $viewHtml = (string)$this->renderer->renderRoot($renderArray);
//      //DOMify it.
//      //Wrap in a body tag to mke processing easier.
//      $viewDocument = $this->domProcessor->createDomDocumentFromHtml('<body>' . $viewHtml . '</body>');
//      //Get local content.
//      $localContent = $shard->getLocalContent();
//      //Add local content, if any, to the rendered display. The rendered view
//      // mode must have a div with the class local-content.
//      if ($localContent) {
//        $this->insertLocalContentIntoViewHtml( $viewDocument, $localContent );
//      }
      //Insert the generated element into the shard's wrapper tag.
      $this->domProcessor->replaceElementContents($first, $embeddingElement);
      //Done with this tag. Mark it as processed.
      $this->domProcessor->markShardAsProcessed($first);
      //Process next tag.
      $this->dbHtmlToViewHtmlProcessOneTag($domDocument);
    } // End if found a shard to process.
  }

//  /**
//   * Return first element with a given value for a given attribute.
//   * @param \DOMNodeList $elements
//   * @param string $attribute Attribute to check.
//   * @param string $value Value to check for.
//   * @return \DOMElement|false An element.
//   */
//  protected function findFirstWithAttribute(\DOMNodeList $elements, $attribute, $value) {
//    //For each element
//    /* @var \DOMElement $element */
//    foreach($elements as $element) {
//      //Is it an element?
//      if (get_class($element) == 'DOMElement') {
//        //Does it have the attribute and value?
//        if ($element->hasAttribute($attribute)) {
//          if ($element->getAttribute($attribute) == $value) {
//            //Yes - return the element.
//            return $element;
//          }
//        }
//        //Test children.
//        if ($element->hasChildNodes()) {
//          $result = $this->findFirstWithAttribute($element->childNodes, $attribute, $value);
//          if ($result) {
//            return $result;
//          }
//        }
//      }
//    }
//    return false;
//  }

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
        if ($element->hasAttribute(ShardMetaData::SHARD_TYPE_TAG)) {
          //Is it the right type of shard?
          if ($element->getAttribute(ShardMetaData::SHARD_TYPE_TAG) == $shardTypeName) {
            //Is it an unprocessed tag (not converted to CK format yet)?
            $already_processed = $element->hasAttribute('class')
              && $element->getAttribute('class') == ShardMetaData::CLASS_IDENTIFYING_WIDGET;
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

//  /**
//   * Get a shard id from a tag.
//   *
//   * @param \DOMElement $element The tag.
//   * @return string The shard id.
//   * @throws \Drupal\shard\Exceptions\ShardBadDataTypeException
//   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
//   */
//  protected function getShardId(\DOMElement $element) {
//    $shard_id = $element->getAttribute('data-shard-id');
//    if ( ! $shard_id ) {
//      throw new ShardMissingDataException('Shard id missing for shard DB tag.');
//    }
//    if ( ! is_numeric($shard_id) ) {
//      throw new ShardBadDataTypeException(
//        sprintf('Argh! Shard id is not numeric: %s.', $shard_id)
//      );
//    }
//    return $shard_id;
//  }

//  /**
//   * Get the value of a required field from a shard.
//   *
//   * @param FieldCollectionItem $shard Field collection item
//   *        with shard insertion data.
//   * @param string $field_name Name of the field whose value is needed.
//   * @return mixed Field's value.
//   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
//   */
//  protected function getRequiredShardValue(FieldCollectionItem $shard, $field_name) {
//    $value = $shard->{$field_name}->getString();
//    if ( strlen($value) == 0 ) {
//      throw new ShardMissingDataException(
//        sprintf('Missing required shard field value: %s', $field_name)
//      );
//    }
//    return $value;
//  }
//
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
    $dbHtml = '<body>' . $dbHtml . '</body>';
    //Put it in a DOMDocument.
    $domDocument = $this->domProcessor->createDomDocumentFromHtml($dbHtml);
    //Work through all of the defined sharders.
    foreach($this->metadata->getShardTypeNames() as $shardTypeName) {
      //Process the first shard tag found. Recurse while there are more.
      //Doing one at a time allows for tag nesting.
      $this->dbHtmlToCkHtmlProcessOneTag($domDocument, $shardTypeName);
    }
    //Get the new content.
    $body = $domDocument->getElementsByTagName('body')->item(0);
    $ckHtml = $domDocument->saveHTML( $body );
    //Strip the body tag.
    preg_match("/\<body\>(.*)\<\/body\>/msi", $ckHtml, $matches);
    $ckHtml = $matches[1];
    return $ckHtml;
  }

  /**
   * Convert one shard tag from DB to CKEditor format.
   *
   * @param \DOMDocument $domDocument The document with the tag.
   * @param string $shardTypeName Shard type to process.
   * @throws \Drupal\shard\Exceptions\ShardNotFoundException
   * @throws \Drupal\shard\Exceptions\ShardUnexptectedValueException
   */
  public function dbHtmlToCkHtmlProcessOneTag(\DOMDocument $domDocument, $shardTypeName ) {
    /* @var \DOMNodeList $divs */
    $divs = $domDocument->getElementsByTagName('div');
    //Is there are shard tag in the document.
    /* @var \DOMElement $first */
    $first = $this->domProcessor->findFirstUnprocessedShardTag($divs, $shardTypeName);
    if ($first) {
      $shardId = $this->domProcessor->getRequiredElementAttribute(
        $first, ShardMetaData::SHARD_ID_TAG);
      //Load the shard.
      $shard = new Shard(
        \Drupal::service('database'),
        \Drupal::service('shard.metadata'),
        \Drupal::service('entity_type.manager'),
        \Drupal::service('renderer'),
        \Drupal::service('shard.dom_processor')
      );
      $shard->setShardId($shardId);
      $shard->loadShardCollectionItemFromStorage();
      //Add the guest nid to the element for CK.
      $first->setAttribute(ShardMetaData::SHARD_GUEST_NID_TAG, $shard->getGuestNid());
      //Add the view mode to the element for CK.
      $viewMode = $shard->getViewMode();
      $first->setAttribute(ShardMetaData::SHARD_VIEW_FORMAT, $viewMode);
      //Add the class that the widget uses to see that an element is a widget.
      $first->setAttribute('class',
        str_replace('[type]', $shardTypeName, ShardMetaData::CLASS_IDENTIFYING_WIDGET));
      //Make the HTML element to show the embed.
      $embeddingElement = $shard->createShardEmbeddingElement();
      //Insert the HTML into the wrapper tag.
      $this->domProcessor->replaceElementChildren($first, $embeddingElement);
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
      //Replace the onner tags of the DB version of the shard insertion tag with the view
      // version, keeping the wrapper tag in place.
//      $this->domProcessor->removeElementChildren($first);
//      $this->domProcessor->copyElementChildren(
//        $view_document->getElementsByTagName('body')->item(0),
//        $first
//      );
      //Done with this tag.
      //Process next tag.
      $this->dbHtmlToCkHtmlProcessOneTag($domDocument, $shardTypeName);
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