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

class Shard extends ModelBase {


  /**
   * The nid of the node where the shard is being inserted. Null when not known.
   *
   * @var integer
   */
  protected $hostNid = self::UNKNOWN;

  /**
   * The nid of the node being inserted. Null when not known.
   *
   * @var integer
   */
  protected $guestNid = self::UNKNOWN;

  /**
   * The name of the field the guest is inserted into.
   *
   * @var string
   */
  protected $guestFieldName = null;

  /**
   * Which value of the field has the guest inserted.
   * Fields can be multivalued.
   *
   * @var integer
   */
  protected $guestFieldDelta = null;

  /**
   * The approximate location of the guest tag in the host field's content.
   *
   * @var integer
   */
  protected $locationInField = null;

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
    QueryFactory $entity_query,
    RendererInterface $renderer,
    Connection $database_connection) {
    $this->eligibleFields = new EligibleFields(
      \Drupal::service('config.factory'),
      \Drupal::service('entity_field.manager')
    );
    $this->entityTypeManager = $entity_type_manager;
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
   * @return string
   */
  public function getGuestFieldName() {
    return $this->guestFieldName;
  }

  /**
   * @param string $field_name
   * @return Shard
   */
  public function setGuestFieldName($field_name) {
    $this->guestFieldName = $field_name;
    return $this;
  }

  /**
   * @return int
   */
  public function getGuestFieldDelta() {
    return $this->guestFieldDelta;
  }

  /**
   * @param int $guestFieldDelta
   * @return Shard
   */
  public function setGuestFieldDelta($guestFieldDelta) {
    $this->guestFieldDelta = $guestFieldDelta;
    return $this;
  }

  /**
   * @return int
   */
  public function getLocationInField() {
    return $this->locationInField;
  }

  /**
   * @param int $locationInField
   * @return \Drupal\sloth\Models\Shard
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function setLocationInField($locationInField) {
    if ( ! is_numeric($locationInField) || $locationInField < 0 ) {
      throw new ShardUnexpectedValueException(
        sprintf('Location not valid: %s', $locationInField)
      );
    }
    $this->locationInField = $locationInField;
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
    if ( ! $this->isValidViewMode($view_mode) ) {
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