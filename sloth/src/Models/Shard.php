<?php
/**
 * @file
 * Models a single shard.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\sloth\Models;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\sloth\Exceptions\SlothBadDataTypeException;
use Drupal\sloth\Exceptions\SlothException;
use Drupal\sloth\Exceptions\SlothMissingDataException;
use Drupal\sloth\Exceptions\SlothDatatbaseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\sloth\Exceptions\SlothUnexptectedValueException;
use Drupal\sloth\Exceptions\SlothNotFoundException;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Database\Connection;


class Shard {

  /**
   * Placeholder value showing that a nid is unknown.
   *
   * Null won't work as well, since many functions use null as a no-result
   * indicator. Using a specific value distinguishes between those fallback
   * cases, and the situation where we deliberately say that the nid is
   * unknown.
   *
   * @var int
   */
  const UNKNOWN = -666;

  /**
   * The nid of the node where the shard is being inserted. Null when not known.
   *
   * @var integer
   */
  protected $hostNid = Shard::UNKNOWN;

  /**
   * The nid of the node being inserted. Null when not known.
   *
   * @var integer
   */
  protected $guestNid = Shard::UNKNOWN;

  /**
   * Values that are known to be valid nids.
   *
   * @var int[]
   */
  static protected $existingNids = null;

  /**
   * The name of the field the guest is inserted into.
   *
   * @var string
   */
  protected $fieldName = null;

  /**
   * Which value of the field has the guest inserted.
   * Fields can be multivalued.
   *
   * @var integer
   */
  protected $delta = null;

  /**
   * The approximate location of the guest tag in the host field's content.
   *
   * @var integer
   */
  protected $location = null;

  /**
   * Which view mode is used to display the guest.
   *
   * @var string
   */
  protected $viewMode = null;

  /**
   * Content local to the insertion.
   *
   * @var string
   */
  protected $localContent = null;

  /**
   * Shard HTML, with shard tags in CKEditor format.
   *
   * @var string
   */
  protected $ckHtml = null;

  /**
   * Shard HTML, with shard tags in DB format.
   *
   * @var string
   */
  protected $dbHtml = null;

  /**
   * Shard HTML, with shard tags in view format.
   *
   * @var string
   */
  protected $viewHtml = null;

  /**
   * SlothTagHandler constructor.
   *
   * Load sloth configuration data set by admin.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\Core\Database\Connection $database_connection
   * @internal param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    QueryFactory $entity_query,
    RendererInterface $renderer,
    Connection $database_connection) {
    $this->eligibleFields = new EligibleFields(
      \Drupal::service('config.factory'),
      \Drupal::service('entity_field.manager')
    );
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityQuery = $entity_query;
    $this->renderer = $renderer;
    $this->databaseConnection = $database_connection;
    //Create a logger.
    $this->logger = \Drupal::logger('shard');
    $this->slothInsertionDetails = new SlothReferenceBag();
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
//      $container->get('sloth.eligible_fields'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('renderer'),
      $container->get('database')
    );
  }


  /**
   * @return int
   */
  public function getHostNid() {
    return $this->hostNid;
  }

  /**
   * @param int $hostNid
   * @return Shard
   */
  public function setHostNid($hostNid) {
    $this->hostNid = $hostNid;
    return $this;
  }

  /**
   * @return int
   */
  public function getGuestNid() {
    return $this->guestNid;
  }

  /**
   * @param int $guestNid
   * @return Shard
   */
  public function setGuestNid($guestNid) {
    $this->hostNid = $guestNid;
    return $this;
  }

  public function isValidNid($value) {
    //Must be a number.
    if ( ! is_numeric($value) ) {
      return FALSE;
    }
    //Can be unknown.
    if ( $value == Shard::UNKNOWN ) {
      return TRUE;
    }
    //Check whether $value is a known nid.
    if ( Shard::$existingNids == NULL ) {
      //Load the known nids.

    }

  }

  /**
   * @return string
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * @param string $fieldName
   * @return Shard
   */
  public function setFieldName($fieldName) {
    $this->fieldName = $fieldName;
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
   * @return Shard
   */
  public function setDelta($delta) {
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
   * @return Shard
   */
  public function setLocation($location) {
    $this->location = $location;
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
   * @return Shard
   */
  public function setViewMode($viewMode) {
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
   * @return Shard
   */
  public function setLocalContent($localContent) {
    $this->localContent = $localContent;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getCkHtml() {
    return $this->ckHtml;
  }

  /**
   * @param mixed $ckHtml
   * @return Shard
   */
  public function setCkHtml($ckHtml) {
    $this->ckHtml = $ckHtml;
    return $this;
  }

  /**
   * @return string
   */
  public function getDbHtml() {
    return $this->dbHtml;
  }

  /**
   * @param string $dbHtml
   * @return Shard
   */
  public function setDbHtml($dbHtml) {
    $this->dbHtml = $dbHtml;
    return $this;
  }
}