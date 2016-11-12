<?php

namespace Drupal\shard\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\shard\ShardMetadataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
//use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\shard\Exceptions\ShardMissingDataException;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;
use Drupal\shard\Exceptions\ShardNotFoundException;

/**
 * Provides a REST resource to get a display (view) of a shard.
 *
 * @RestResource(
 *   id = "shard_preview",
 *   label = @Translation("Shard preview"),
 *   uri_paths = {
 *     "canonical" = "/shard/preview/{nid}/{viewmode}"
 *   }
 * )
 */
class ShardPreview extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /* @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
//  protected $entity_display_repository;
  /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
  protected $entityTypeManager;
  /* @var \Drupal\Core\Render\RendererInterface $renderer */
  protected $renderer;

  /**
   * Object holding metadata for fields and nodes.
   *
   * @var ShardMetadataInterface
   */
  protected $metadata;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\shard\ShardMetadataInterface $metadata
   * @internal param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
//    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer,
    ShardMetadataInterface $metadata) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
//    $this->entity_display_repository = $entity_display_repository;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
    $this->metadata = $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('shard'),
      $container->get('current_user'),
//      $container->get('entity_display.repository'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('shard.metadata')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param null $nid
   * @param null $viewMode
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   * @throws \Drupal\shard\Exceptions\ShardNotFoundException
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function get($nid = NULL, $viewMode = NULL) {
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    //Were both params given?
    if ( is_null($nid) || is_null($viewMode) ) {
      throw new ShardMissingDataException($this->t('Missing argument getting shard display.'));
    }
    //Does the view mode exist?
    if ( ! in_array($viewMode, $this->metadata->getAllowedViewModes()) ) {
      throw new ShardUnexpectedValueException(
        sprintf('View mode not allowed: ', $viewMode)
      );
    }
    //Load the shard.
    $guestNode = $this->entityTypeManager->getStorage('node')->load($nid);
    //Does the shard exist?
    if ( is_null($guestNode) ) {
      throw new ShardNotFoundException(
        sprintf('Cannot find shard ' . $nid)
      );
    }
    //Render the selected display of the shard.
    $viewBuilder = $this->entityTypeManager->getViewBuilder('node');
    $renderArray = $viewBuilder->view($guestNode, $viewMode);
    $html = (string)$this->renderer->renderRoot($renderArray);
    return new ResourceResponse($html);
  }

}
