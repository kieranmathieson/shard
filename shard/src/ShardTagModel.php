<?php
/**
 * @file
 * Represents a shard.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Database\Connection;
use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardNotFoundException;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\Core\Render\RendererInterface;


class ShardTagModel {

  /**
   * Database connection service.
   *
   * @var Connection
   */
  protected $databaseConnection;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Render\RendererInterface $renderer
   */
  protected $renderer;

  /**
   * Dependency injection container.
   *
   * @var Container
   */
  protected $container;

  /**
   * @var ShardDomProcessor
   */
  protected $domProcessor;

  /**
   * Service with metadata about shards, e.g., eligible fields.
   *
   * @var ShardMetadataInterface
   */
  protected $metadata;

  /**
   * Id of the collection item for this shard.
   *
   * @var int
   */
  protected $shardId = NULL;

  /**
   * Shard type. Same as the module name.
   *
   * @var string
   */
  protected $shardType = NULL;

  /**
   * The nid of the node where the shard is being inserted.
   *
   * @var integer
   */
  protected $hostNid = NULL;

  /**
   * The nid of the shard being inserted.
   *
   * @var integer
   */
  protected $guestNid = NULL;

  /**
   * The name of the field the shard is inserted into.
   *
   * @var string
   */
  protected $hostFieldName = NULL;

  /**
   * Which value of the field has the shard inserted.
   * Fields can be multivalued.
   *
   * @var integer
   */
  protected $delta = NULL;

  /**
   * The approximate location of the shard tag in the host field's content.
   *
   * @var integer
   */
  protected $location = NULL;

  /**
   * Which view mode is used to display the shard.
   *
   * @var string
   */
  protected $viewMode = NULL;

  /**
   * Content local to the insertion.
   *
   * @var string
   */
  protected $localContent = NULL;

  public function __construct(
      Connection $databaseConnection,
      ShardMetadataInterface  $metadata,
      EntityTypeManagerInterface $entityTypeManager,
      RendererInterface $renderer,
      ShardDomProcessor $domProcessor
      ) {
    $this->databaseConnection = $databaseConnection;
    $this->metadata = $metadata;
    $this->entityTypeManager = $entityTypeManager;
    $this->renderer = $renderer;
    $this->domProcessor = $domProcessor;
  }

  /**
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('shard.metadata'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('shard.dom_processor')
    );
  }

  /**
   * @return int
   */
  public function getShardId() {
    return $this->shardId;
  }

  /**
   * @param int $shardId
   * @return \Drupal\shard\ShardTagModel
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setShardId($shardId) {
    if ( ! $this->metadata->isValidNid($shardId) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Bad shard id: %s', $shardId)
      );
    }
    $this->shardId = $shardId;
    return $this;
  }

  /**
   * @return string
   */
  public function getShardType() {
    return $this->shardType;
  }

  /**
   * @param string $shardType
   * @return \Drupal\shard\ShardTagModel
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setShardType($shardType) {
    if ( ! $this->metadata->isValidShardTypeName($shardType) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Bad shard type: %s', $shardType)
      );
    }
    $this->shardType = $shardType;
    return $this;
  }

  /**
   * @return int
   */
  public function getHostNid() {
    return $this->hostNid;
  }

  /**
   * @param int $hostNid
   * @return \Drupal\shard\ShardTagModel
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setHostNid($hostNid) {
    if ( ! $this->metadata->isValidNid($hostNid) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Bad host nid: %s', $hostNid)
      );
    }
    $this->hostNid = $hostNid;
    return $this;
  }

  /**
   * Load the host node.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\shard\Exceptions\ShardNotFoundException
   */
  public function loadHostNodeFromStorage() {
    if ( ! $this->getHostNid() ) {
      throw new ShardNotFoundException(
        sprintf('Host nid not set.')
      );
    }
    $hostNode = $this->entityTypeManager->getStorage('node')->load($this->getHostNid());
    if ( ! $hostNode ) {
      throw new ShardNotFoundException(
        sprintf('Cannot load host node with nid: %s', $this->getHostNid())
      );
    }
    return $hostNode;
  }

  /**
   * @return int
   */
  public function getGuestNid() {
    return $this->guestNid;
  }

  /**
   * @param int $guestNid
   * @return \Drupal\shard\ShardTagModel
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setGuestNid($guestNid) {
    if ( ! $this->metadata->isValidNid($guestNid) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Bad guest nid: %s', $guestNid)
      );
    }
    $this->guestNid = $guestNid;
    return $this;
  }

  /**
   * Load the guest node.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\shard\Exceptions\ShardNotFoundException
   */
  public function loadGuestNodeFromStorage() {
    if ( ! $this->getGuestNid() ) {
      throw new ShardNotFoundException(
        sprintf('Guest nid not set.')
      );
    }
    /* @var \Drupal\Core\Entity\EntityInterface $guestNode */
    $guestNode = $this->entityTypeManager->getStorage('node')->load($this->getGuestNid());
    if ( ! $guestNode ) {
      throw new ShardNotFoundException(
        sprintf('Cannot load guest node with nid: %s', $this->getGuestNid())
      );
    }
    return $guestNode;
  }

  /**
   * @return string
   */
  public function getHostFieldName() {
    return $this->hostFieldName;
  }

  /**
   * @param string $fieldName
   * @return \Drupal\shard\ShardTagModel
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setHostFieldName($fieldName) {
    //Can check field name only if bundle is known.
    if ( $this->getHostNid() ) {
      /* @var \Drupal\Core\Entity\EntityInterface $guestNode */
      $hostNode = $this->loadHostNodeFromStorage();
      $bundle = $hostNode->bundle();
      $eligibleFields = $this->metadata->listEligibleFieldsForBundle($bundle);
      if ( ! in_array($fieldName, $eligibleFields) ) {
        throw new ShardUnexpectedValueException(
          sprintf('Field name not eligible: %s', $fieldName)
        );
      }
    }
    $this->hostFieldName = $fieldName;
    return $this;
  }

  /**
   * @return int
   */
  public function getDelta() {
    return $this->delta;
  }

  /**
   * @param int $delta
   * @return \Drupal\shard\ShardTagModel
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setDelta($delta) {
    if ( ! is_numeric($delta) || $delta < 0 ) {
      throw new ShardUnexpectedValueException(
        sprintf('Bad delta: %s', $delta)
      );
    }
    $this->delta = $delta;
    return $this;
  }

  /**
   * @return int
   */
  public function getLocation() {
    return $this->location;
  }

  /**
   * @param int $location
   * @return \Drupal\shard\ShardTagModel
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setLocation($location) {
    if ( ! is_numeric($location) || $location < 0 ) {
      throw new ShardUnexpectedValueException(
        sprintf('Bad location: %s', $location)
      );
    }    $this->location = $location;
    return $this;
  }

  /**
   * @return string
   */
  public function getViewMode() {
    return $this->viewMode;
  }

  /**
   * @param string $viewMode
   * @return \Drupal\shard\ShardTagModel
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setViewMode($viewMode) {
    if ( ! $this->metadata->isValidViewModeName($viewMode) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Bad view mode: %s', $viewMode)
      );
    }
    $this->viewMode = $viewMode;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getLocalContent() {
    return $this->localContent;
  }

  /**
   * @param mixed $localContent
   * @return ShardTagModel
   */
  public function setLocalContent($localContent) {
    $this->localContent = $localContent;
    return $this;
  }

  /**
   * @return \Drupal\shard\ShardMetadataInterface
   */
  public function getMetadata() {
    return $this->metadata;
  }

  /**
   * Load a shard collection item from storage. NB: does not
   * set the shard type.
   *
   * @param int $shardId Entity key.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   * @throws \Drupal\shard\Exceptions\ShardNotFoundException
   */
  public function loadShardCollectionItemFromStorage($shardId) {
    $this->setShardId($shardId);
    //Load the definition of the shard.
    /* @var \Drupal\field_collection\Entity\FieldCollectionItem $fieldCollectionEntity */
    $fieldCollectionEntity = $this->entityTypeManager
      ->getStorage(ShardMetadata::SHARD_ENTITY_TYPE)->load($this->getShardId());
    if ( ! $fieldCollectionEntity ) {
      throw new ShardNotFoundException(
        sprintf('Shard not found. Id: ' . $this->getShardId())
      );
    }
    //Populate each of the shard's properties.
    $this->setHostNid(
      $this->getRequiredShardValue(
        $fieldCollectionEntity,
        ShardMetadata::FIELD_NAME_HOST_NODE_ID
      )
    );
    $this->setHostFieldName(
      $this->getRequiredShardValue(
        $fieldCollectionEntity,
        ShardMetadata::FIELD_NAME_HOST_FIELD
      )
    );
    $this->setDelta(
      $this->getRequiredShardValue(
        $fieldCollectionEntity,
        ShardMetadata::FIELD_HOST_FIELD_DELTA
      )
    );
    $this->setViewMode(
      $this->getRequiredShardValue(
        $fieldCollectionEntity,
        ShardMetadata::FIELD_NAME_VIEW_MODE
      )
    );
    $this->setLocation(
      $this->getRequiredShardValue(
        $fieldCollectionEntity,
        ShardMetadata::FIELD_NAME_LOCATION
      )
    );
    $this->setLocalContent(
      $this->getRequiredShardValue(
        $fieldCollectionEntity,
        ShardMetadata::FIELD_NAME_LOCAL_CONTENT
      )
    );
  }

  /**
   * Get the value of a required field from a shard. Assumes the field
   * is single valued, so getString will be sufficient.
   *
   * @param FieldCollectionItem $shard Field collection item
   *        with shard insertion data.
   * @param string $fieldName Name of the field whose value is needed.
   * @return mixed Field's value.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  protected function getRequiredShardValue(FieldCollectionItem $shard, $fieldName) {
    $value = $shard->{$fieldName}->getString();
    if ( strlen($value) == 0 ) {
      throw new ShardMissingDataException(
        sprintf('Missing required shard field value: %s', $fieldName)
      );
    }
    return $value;
  }

  public function saveShardToStorage() {

  }

  /**
   * Create a DOMElement containing the HTML to show a shard in a
   * guest node, ready to be wrapped in the right container
   * for CKEditor format, or view format.
   *
   * @return \DOMElement
   */
  public function createShardEmbeddingElement() {
    //Load the node to be embedded.
    $guestNode = $this->loadGuestNodeFromStorage();
    //Render the selected display of the shard.
    $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
    $renderArray = $viewBuilder->view($guestNode, $this->getViewMode());
    $viewHtml = (string)$this->renderer->renderRoot($renderArray);
    //DOM it.
    //Wrap in a body tag to make processing easier.
    $viewDocument = $this->domProcessor->createDomDocumentFromHtml('<body>' . $viewHtml . '</body>');
    //Get local content.
    $localContent = $this->getLocalContent();
    //Add local content, if any, to the rendered display. The rendered view
    // mode must have a div with the class local-content.
    if ($localContent) {
      $this->insertLocalContentIntoViewHtml( $viewDocument, $localContent );
    }
    return $viewDocument->getElementsByTagName('body')->item(0);
  }

  /**
   * Add local content to HTML of a view of a shard.
   * The view HTML must has a div with the class local-content.
   *
   * @param \DOMDocument $destinationDocument HTML to insert local content into.
   * @param string $localContent HTML to insert.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  protected function insertLocalContentIntoViewHtml(\DOMDocument $destinationDocument, $localContent) {
    if ( $localContent ) {
      $destinationContainer = $this->domProcessor->findLocalContentContainerInDoc($destinationDocument);
      if (! $destinationContainer) {
        throw new ShardMissingDataException(
          'Problem detected during shard processing. Local content, but no '
          . 'local content container.'
        );
      }
      else {
        //The local content container should have no children.
        $this->domProcessor->removeElementChildren($destinationContainer);
        //Copy the children of the local content to the container.
        //Wrap in body tag to make the local content easier to find later.
        $localContentDoc = $this->domProcessor->createDomDocumentFromHtml(
          '<body>' . $localContent . '</body>'
        );
        $localContentDomified
          = $localContentDoc->getElementsByTagName('body')->item(0);
        $this->domProcessor->copyElementChildren(
          $localContentDomified, $destinationContainer);
      }
    } //End of local content.
  }

  /**
   * Render the guest node's view, add local content, and insert the result
   * into an element.
   *
   * @param $shardTagElement
   *
   */
  public function injectGuestViewHtmlIntoShardTag(
      \DOMElement $shardTagElement) {
    //Render the selected display of the guest node.
    $guestNode = $this->loadGuestNodeFromStorage();
    $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
    $renderArray = $viewBuilder->view($guestNode, $this->getViewMode());
    $htmlWithoutLocalContent = (string)$this->renderer->renderRoot($renderArray);
    //Make a DOM doc with the HTML used to show the guest node.
    //Wrap HTML in a body tag to make the document easier to use later.
    $domDocForGuestViewExLocalContent = $this->domProcessor->createDomDocumentFromHtml(
      '<body>' . $htmlWithoutLocalContent . '</body>'
    );
    //Is there local content?
    if ( $this->getLocalContent() ) {
      //Inject local content into the guest node's rendering.
      //Find local content container to put content in.
      $localContentContainerInGuestView = $this->domProcessor->
        findLocalContentContainerInDoc($domDocForGuestViewExLocalContent);
      //$localContentContainer belongs to $domDocExLocalContent,
      //So injecting content into it $localContentContainer affects
      //$domDocExLocalContent.
      if ( $localContentContainerInGuestView ) {
        //There's local content, and a place for it.
        //(Sing)There's a place for local content,
        //      Somewhere a place for local content.
        //      Find the tag, and we're halfway there...
        //I'll stop now.
        //$this->getLocalContent() supplies a string. Make a doc out
        //of it, to copy its elements.
        $localContentDoc = $this->domProcessor->createDomDocumentFromHtml(
          '<body>' . $this->getLocalContent() . '</body>'
        );
        $this->domProcessor->copyElementChildren(
          $localContentDoc->getElementsByTagName('body')->item(0),
          $localContentContainerInGuestView
        );
      } //End there is a local content container in the HTML returned
        //from rendering.
    } //End there is local content.
    //Inject the content in $domDocForGuestViewExLocalContent
    //into $shardTagElement.
    $this->domProcessor->copyElementChildren(
      $domDocForGuestViewExLocalContent->getElementsByTagName('body')->item(0),
      $shardTagElement
    );
  }


}