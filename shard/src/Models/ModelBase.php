<?php
/**
 * @file
 * Base class for shard data object models.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\sloth\Models;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;


class ModelBase {

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
   * Values that are known to be valid nids.
   *
   * @var int[]
   */
  static protected $existingNids = NULL;

  /**
   * Names of node view modes Drupal knows about.
   *
   * @var string[]
   */
  static protected $viewModes = NULL;

  /**
   * Names of content types Drupal knows about.
   *
   * @var string[]
   */
  static protected $contentTypes = NULL;

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
      EntityTypeBundleInfoInterface $bundle_info_manager
      ) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->entityQuery = $entity_query;
    $this->bundleInfoManager = $bundle_info_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_display.repository'),
      $container->get('entity.query'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * @param $value
   * @return bool
   */
  protected function isValidNid($value) {
    //Must be a number.
    if ( ! is_numeric($value) ) {
      return FALSE;
    }
    //Can be unknown.
    if ( $value == self::UNKNOWN ) {
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
    if ( is_array(self::$existingNids) ) {
      //Already loaded.
      return;
    }
    $query = $this->entityQuery->get('node');
    $result = $query->execute();
    self::$existingNids = array_values($result);
  }


  protected function isValidViewMode($value) {
    //Can't be MT.
    if ( ! $value ) {
      return FALSE;
    }
    $this->loadValidViewModes();
    return in_array($value, self::$viewModes);
  }

  protected function loadValidViewModes() {
    if ( is_array(self::$viewModes) ) {
      //Already loaded.
      return;
    }
    //Load the view mode names.
    self::$viewModes = $this->entityDisplayRepository->getViewModes('node');
  }

  protected function isValidContentType($value) {
    //Can't be MT.
    if ( ! $value ) {
      return FALSE;
    }
    $this->loadValidContentTypes();
    return in_array($value, self::$contentTypes);
  }

  protected function loadValidContentTypes() {
    if ( is_array(self::$contentTypes) ) {
      //Already loaded.
      return;
    }
    //Load the content type names.
    self::$contentTypes = $this->bundleInfoManager->getBundleInfo('node');
  }

}