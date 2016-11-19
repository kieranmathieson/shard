<?php
/**
 * @file
 * Base class for shard data object models.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
//use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityInterface;
//use Drupal\shard\Exceptions\ShardMissingDataException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Component\Uuid\Uuid;


class ShardMetadata implements ShardMetadataInterface {

  /**
   * Names of defined shard types.
   *
   * @var string[]
   */
  protected $shardTypeNames = [];

  /**
   * Values that are known to be valid nids.
   *
   * @var int[]
   */
  protected $existingNids = NULL;

  /**
   * Names of node view modes Drupal knows about.
   *
   * @var string[]
   */
  protected $viewModes = NULL;

  /**
   * Names of content types Drupal knows about.
   *
   * @var string[]
   */
  protected $contentTypes = NULL;

  /**
   * Names of valid guest fields.
   *
   * @var string[]
   */
  protected $validGuestFields = [];

  /**
   * Configuration storage service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /* Configuration of the sloths, set by admin. */
  protected $shardConfigs;

  /* What content types are allowed to have
   * shards embedded in them. An array of content type names.
   *
   * @var string[]
   */
  protected $configAllowedContentTypes = [];

  /* What fields are allowed to have
   * shards embedded in them. An array of field names.
   *
   * @var string[]
   */
  protected $configAllowedFields = [];

  /* What view modes are allowed to be shown. An array of view mode names.
   *
   * @var string[]
   */
  protected $configAllowedViewModes = [];

  /*  What field types are allowed to have
   * shards embedded in them. An array of field type names.
   *
   * @var string[]
   */
  protected $configAllowedFieldTypes = [];

  /**
   * Entity metadata service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Entity query object.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Used to interact with sharders (modules that implement shards).
   *
   * @var EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Cache of eligible field names for bundles.
   *
   * @var string[]
   */
  protected $eligibleFieldsCache = [];


  protected $bundleInfoManager;

  public function __construct(
      EntityDisplayRepositoryInterface $entity_display_repository,
      EntityTypeBundleInfoInterface $bundle_info_manager,
      ConfigFactoryInterface $config_factory,
      EntityFieldManagerInterface $entity_field_manager,
      EventDispatcherInterface $eventDispatcher
      ) {
    $this->entityDisplayRepository = $entity_display_repository;
//    $this->entityQuery = $entity_query;
    $this->bundleInfoManager = $bundle_info_manager;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    $this->eventDispatcher = $eventDispatcher;
    //Load valid nids.
//    $query = $this->entityQuery->get('node');
//    $result = $query->execute();
    $query = \Drupal::entityQuery('node');
    $result = $query->execute();
    $this->existingNids = array_values($result);
    //Load the view mode names.
    $this->viewModes = $this->entityDisplayRepository->getViewModes('node');
    //Load the content type names.
    $this->contentTypes = $this->bundleInfoManager->getBundleInfo('node');
    //Load module configs.
    $this->shardConfigs = $this->configFactory->get('shard.settings');
    //Which content types have sloths embedded?
    $this->configAllowedContentTypes = $this->shardConfigs->get('content_types');
    //Get allowed fields.
    $this->configAllowedFields = $this->shardConfigs->get('fields');
    //Get allowed view modes.
    $this->configAllowedViewModes = $this->shardConfigs->get('view_modes');
    //Get allowed field types.
    $this->configAllowedFieldTypes = explode(',', $this->shardConfigs->get('field_types'));
    //Ask sharders (modules that implement shards) to register.
    $pluginRegisterEvent = new ShardTypeRegisterEvent();
    $this->eventDispatcher->dispatch('shard.register_shard_types', $pluginRegisterEvent);
    //Send shard type names to metadata object.
    $this->setShardTypeNames($pluginRegisterEvent->getRegisteredShardTypes());
  }

  /**
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_display.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * @return \string[]
   */
  public function getShardTypeNames() {
    return $this->shardTypeNames;
  }

  /**
   * @param \string[] $shardTypeNames
   * @return ShardMetadata
   */
  public function setShardTypeNames($shardTypeNames) {
    $this->shardTypeNames = $shardTypeNames;
    return $this;
  }

  /**
   * Check whether a name is a valid shard type name.
   *
   * @param string $name Name to check.
   * @return bool Is it valid?
   */
  public function isValidShardTypeName($name) {
    return in_array($name, $this->getShardTypeNames());
  }

  /**
   * @param $value
   * @return bool
   */
  public function isValidNid($value) {
    //UUIDs used as placeholders during node insert.
    if ( Uuid::isValid($value) ) {
      return TRUE;
    }
    //Must be a number.
    if ( ! is_numeric($value) ) {
      return FALSE;
    }
    //Must be positive.
    if ( $value <= 0 ) {
      return FALSE;
    }
    return in_array($value, $this->existingNids);
  }

  /**
   * Get the valid view modes.
   *
   * @return string[] View modes
   */
  public function getViewModes(){
    return $this->viewModes;
  }

  /**
   * @param $value
   * @return bool
   */
  public function isValidViewModeName($value) {
    return key_exists($value, $this->viewModes);
  }

  /**
   * @param $value
   * @return bool
   */
  public function isValidContentTypeName($value) {
    return in_array($value, $this->contentTypes);
  }

  /**
   * @return array Allowed view modes.
   */
  public function getAllowedViewModes() {
    return $this->configAllowedViewModes;
  }

  /**
   * Is a field name eligible for shards?
   *
   * @param string $fieldName Name of the field, e.g., body
   * @return bool True if the field is allowed.
   */
  public function isFieldEligible($fieldName) {
    return in_array($fieldName, $this->configAllowedFields);
  }

  /**
   * Return a list of the names of fields that are allowed to have
   * shards in them.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   * @return array Names of fields that may have sloths embedded.
   * @internal param \Drupal\Core\Entity\EntityInterface $entity Entity with the fields.
   */
  public function listEligibleFieldsForNode(EntityInterface $node) {
    $bundle_name = $node->bundle();
    return $this->listEligibleFieldsForBundle( $bundle_name );
  }

  /**
   * Return a list of the names of fields that are allowed to have
   * shards in them.
   *
   * @param string $bundleName Name of the bundle type, e.g., article.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEligibleFieldsForBundle($bundleName) {
    if ( isset($this->eligibleFieldsCache[$bundleName]) ) {
      return $this->eligibleFieldsCache[$bundleName];
    }
    $fieldNames = [];
      //Is this content type allowed?
    if (in_array($bundleName, $this->configAllowedContentTypes)) {
      //Get definitions of the fields in the bundle.
      $fieldDefinitions = $this->entityFieldManager
        ->getFieldDefinitions('node', $bundleName);
      //Loop across fields.
      foreach ($fieldDefinitions as $fieldName => $fieldDef) {
        //Is the field allowed?
        if (in_array($fieldName, $this->configAllowedFields)) {
          //Is the field type allowed?
          if (in_array(
            $fieldDef->getFieldStorageDefinition()->getType(),
            $this->configAllowedFieldTypes
          )) {
            //The field can have sloths in it.
            $fieldNames[] = $fieldName;
          } //End field type is allowed.
        } //End field is allowed.
      } //End foreach.
    } //End content type is allowed.
    //Cache.
    $this->eligibleFieldsCache[$bundleName] = $fieldNames;
    return $fieldNames;
  }

  /**
   * Does a node have eligible fields?
   *
   * @param \Drupal\node\NodeInterface $node
   * @return bool True if has eligible fields.
   */
  public function nodeHasEligibleFields(NodeInterface $node) {
    return sizeof($this->listEligibleFieldsForNode($node)) > 0;
  }


  /**
   * @param $key
   * @param $dataToSave
   * @internal param $data
   */
  public function stashStringInConFig($key, $dataToSave) {
    $configSettings = $this->configFactory->getEditable('shard.settings');
    $configSettings->set($key, $dataToSave);
    $configSettings->save();
  }

  /**
   * @param $key
   * @return array|mixed|null
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   */
  public function fetchStringFromConfig($key) {
    $this->shardConfigs = $this->configFactory->get('shard.settings');
    $savedData = $this->shardConfigs->get($key);
    return $savedData;
  }

}