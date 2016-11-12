<?php

namespace Drupal\shard\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
//use Drupal\shard\ShardMetadata;
use Drupal\shard\ShardMetadataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
//use Drupal\Core\Config\ConfigFactoryInterface;

//use Drupal\shard\Exceptions\ShardMissingDataException;

/**
 * Provides a resource to get eligible view modes.
 *
 * @RestResource(
 *   id = "shard_allowed_field_list",
 *   label = @Translation("Shard allowed field list"),
 *   uri_paths = {
 *     "canonical" = "/shard/allowed-fields"
 *   }
 * )
 */
class ShardAllowedFieldList extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
//  protected $config_factory;

  /* @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   * $entityDisplayRepository */
  protected $entityDisplayRepository;


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
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   * @param \Drupal\shard\ShardMetadata|\Drupal\shard\ShardMetadataInterface $metadata
   * @internal param \Psr\Log\LoggerInterface $logger A logger instance.*   A logger instance.
   * @internal param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
//    ConfigFactoryInterface $config_factory,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ShardMetadataInterface $metadata) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
//    $this->config_factory = $config_factory;
    $this->entityDisplayRepository = $entity_display_repository;
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
//      $container->get('config.factory'),
      $container->get('entity_display.repository'),
      $container->get('shard.metadata')
    );
  }

  /**
   * Send the eligible view modes.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    /* @var string[] $viewModes */
    $viewModes = $this->metadata->getAllowedF();
//    $config_settings = $this->config_factory->get('shard.settings');
//    $config_view_modes = $config_settings->get('view_modes');
//    if ( sizeof($config_view_modes) == 0 ) {
//      throw new ShardMissingDataException('No shard view modes available.');
//    }
    //Get definitions of all the view modes that exist for nodes,
    //so can return the label of the Chosen Ones.
    $allViewModes = $this->entityDisplayRepository->getViewModes('node');
    $viewModeList = [];
    foreach($viewModes as $viewMode) {
      //Look up the label of the view mode.
      $viewModeList[] = [
        'machineName' => $viewMode,
        'label' => $allViewModes[$viewMode]['label'],
      ];
    }
    return new ResourceResponse($viewModeList);
  }

}
