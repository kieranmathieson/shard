<?php
/**
 * @file
 * Base class for shard data object models.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\shard;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

use Drupal\shard\Exceptions\ShardMissingDataException;

class ShardMetaData implements ShardMetadataInterface {

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
  protected $configAllowedContentTypes;

  /* What fields are allowed to have
   * shards embedded in them. An array of field names.
   *
   * @var string[]
   */
  protected $configAllowedFields;

  /*  What field types are allowed to have
   * shards embedded in them. An array of field type names.
   *
   * @var string[]
   */
  protected $configAllowedFieldTypes;

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


  protected $bundleInfoManager;

  public function __construct(
      EntityDisplayRepositoryInterface $entity_display_repository,
      QueryFactory $entity_query,
      EntityTypeBundleInfoInterface $bundle_info_manager,
      ConfigFactoryInterface $config_factory,
      EntityFieldManagerInterface $entity_field_manager
      ) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityQuery = $entity_query;
    $this->bundleInfoManager = $bundle_info_manager;
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    //Load valid nids.
    $query = $this->entityQuery->get('node');
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
    //Get allowed field types.
    $this->configAllowedFieldTypes = explode(',', $this->shardConfigs->get('field_types'));
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('entity_type.bundle.info'),
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * @param $value
   * @return bool
   */
  public function isValidNid($value) {
    //Must be a number.
    if ( ! is_numeric($value) ) {
      return FALSE;
    }
    //Can be unknown.
    if ( $value == self::UNKNOWN ) {
      return TRUE;
    }
    return in_array($value, $this->existingNids);
  }


  /**
   * @param $value
   * @return bool
   */
  public function isValidViewMode($value) {
    return in_array($value, $this->viewModes);
  }

  /**
   * @param $value
   * @return bool
   */
  public function isValidContentType($value) {
    return in_array($value, $this->contentTypes);
  }

  public function addValidGuestField($field_name) {
    if ( ! $field_name ) {
      throw new ShardMissingDataException(
        sprintf('Missing field name')
      );
    }
    $this->validGuestFields[] = $field_name;
    return $this;
  }

  /**
   * Return a list of the names of fields that are allowed to have
   * sloth shards in them.
   *
   * @param EntityInterface $entity Entity with the fields.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEntityEligibleFields(EntityInterface $entity) {
    $entity_type_name = $entity->getEntityTypeId();
    $bundle_name = $entity->bundle();
    return $this->listEligibleFields( $entity_type_name, $bundle_name );
  }

  /**
   * Return a list of the names of fields that are allowed to have
   * sloth shards in them.
   *
   * @param string $entity_type_name Name of the entity type, e.g., node.
   * @param string $bundle_name Name of the bundle type, e.g., article.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEligibleFields($entity_type_name, $bundle_name) {
    $field_names = [];
    //Is this a node?
    if ($entity_type_name == 'node') {
      //Is this content type allowed?
      if (in_array($bundle_name, $this->configAllowedContentTypes)) {
        //Get definitions of the fields in the bundle.
        $field_defs = $this->entityFieldManager->getFieldDefinitions('node', $bundle_name);
        //Loop across fields.
        foreach ($field_defs as $field_name => $field_def) {
          //Is the field allowed?
          if (in_array($field_name, $this->configAllowedFields)) {
            //Is the field type allowed?
            if (in_array(
              $field_def->getFieldStorageDefinition()->getType(),
              $this->configAllowedFieldTypes
            )) {
              //The field can have sloths in it.
              $field_names[] = $field_name;
            } //End field type is allowed.
          } //End field is allowed.
        } //End foreach.
      } //End content type is allowed.
    } //End entity is a node.
    return $field_names;
  }


}