<?php

namespace Drupal\shard\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
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
  protected $entity_display_repository;
  /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
  protected $entity_type_manager;
  /* @var \Drupal\Core\Render\RendererInterface $renderer */
  protected $renderer;

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
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityDisplayRepositoryInterface $entity_display_repository,
    EntityTypeManagerInterface $entity_type_manager,
    RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->entity_display_repository = $entity_display_repository;
    $this->entity_type_manager = $entity_type_manager;
    $this->renderer = $renderer;
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
      \Drupal::service('entity_display.repository'),
      \Drupal::service('entity_type.manager'),
      \Drupal::service('renderer')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @param null $nid
   * @param null $view_mode
   * @return \Drupal\rest\ResourceResponse Throws exception expected.
   * Throws exception expected.
   * @throws \Drupal\shard\Exceptions\ShardMissingDataException
   * @throws \Drupal\shard\Exceptions\ShardNotFoundException
   * @throws \Drupal\shard\Exceptions\ShardUnexpectedValueException
   */
  public function get($nid = NULL, $view_mode = NULL) {
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    //Were both params given?
    if ( is_null($nid) || is_null($view_mode) ) {
      throw new ShardMissingDataException($this->t('Missing argument getting shard display.'));
    }
    //Does the view mode exist?
    $all_view_modes = $this->entity_display_repository->getViewModes('node');
    if ( ! key_exists($view_mode, $all_view_modes) ) {
      throw new ShardUnexpectedValueException($this->t('Unknown shard view mode: ' . $view_mode));
    }
    //Load the shard.
    $shard_node = $this->entity_type_manager->getStorage('node')->load($nid);
    //Does the shard exist?
    if ( is_null($shard_node) ) {
      throw new ShardNotFoundException('Cannot find shard ' . $nid);
    }
    //Render the selected display of the shard.
    $view_builder = $this->entity_type_manager->getViewBuilder('node');
    $render_array = $view_builder->view($shard_node, $view_mode);
    $html = (string)$this->renderer->renderRoot($render_array);
    return new ResourceResponse($html);
  }

}
