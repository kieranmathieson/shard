<?php
/**
 * @file
 * Models a single shard.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\sloth\Models;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;
use Drupal\shard\Exceptions\ShardBadDataTypeException;
use Drupal\shard\Exceptions\ShardException;
use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardDatabaseException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\field_collection\Entity\FieldCollectionItem;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\shard\Exceptions\ShardNotFoundException;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Database\Connection;
use Drupal\sloth\Services\EligibleFields;
use Drupal\sloth\SlothReferenceBag;

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
   * Entity query object.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $entityQuery;


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
   * @param int $host_nid
   * @return \Drupal\sloth\Models\Shard
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setHostNid($host_nid) {
    if ( ! $this->isValidNid($host_nid) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Host nid not valid: %s', $host_nid)
      );
    }
    $this->hostNid = $host_nid;
    return $this;
  }

  /**
   * @return int
   */
  public function getGuestNid() {
    return $this->guestNid;
  }

  /**
   * @param int $guest_nid
   * @return \Drupal\sloth\Models\Shard
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setGuestNid($guest_nid) {
    if ( ! $this->isValidNid($guest_nid) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Guest nid not valid: %s',$guest_nid)
      );
    }
    $this->hostNid = $guest_nid;
    return $this;
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
    if ( $value == Shard::UNKNOWN ) {
      return TRUE;
    }
    //Check whether $value is a known nid.
    $this->loadValidNids();
    return in_array($value, Shard::$existingNids);
  }

  /**
   *
   */
  protected function loadValidNids() {
    if ( is_array(Shard::$existingNids) ) {
      //Already loaded.
      return;
    }
    $query = $this->entityQuery->get('node');
    $result = $query->execute();
    Shard::$existingNids = array_values($result);
  }

  /**
   * @return string
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * @param string $field_name
   * @return Shard
   */
  public function setFieldName($field_name) {
    $this->fieldName = $field_name;
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
   * @return \Drupal\sloth\Models\Shard
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setLocation($location) {
    if ( ! is_numeric($location) || $location < 0 ) {
      throw new ShardUnexpectedValueException(
        sprintf('Location not valid: %s', $location)
      );
    }
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
   * @param string $view_mode
   * @return $this For chaining.
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setViewMode($view_mode) {
    //Does the view mode exist?
    $all_view_modes = $this->entityDisplayRepository->getViewModes('node');
    if ( ! key_exists($view_mode, $all_view_modes) ) {
      throw new ShardUnexpectedValueException(
        sprintf('Unknown shard view mode: %s', $view_mode)
      );
    }
    $this->viewMode = $view_mode;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getLocalContent() {
    return $this->localContent;
  }

  /**
   * @param mixed $local_content
   * @return Shard
   */
  public function setLocalContent($local_content) {
    $this->localContent = $local_content;
    return $this;
  }

  /**
   * @return mixed
   */
  public function getCkHtml() {
    return $this->ckHtml;
  }

  /**
   * @param mixed $ck_tml
   * @return Shard
   */
  public function setCkHtml($ck_tml) {
    $this->ckHtml = $ck_tml;
    return $this;
  }

  /**
   * @return string
   */
  public function getDbHtml() {
    return $this->dbHtml;
  }

  /**
   * @param string $db_hHtml
   * @return Shard
   */
  public function setDbHtml($db_html) {
    $this->dbHtml = $db_html;
    return $this;
  }
}