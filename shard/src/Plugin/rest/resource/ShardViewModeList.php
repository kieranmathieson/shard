<?php

namespace Drupal\sloth\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Provides a resource to get eligible view modes.
 *
 * @RestResource(
 *   id = "sloth_view_mode_list",
 *   label = @Translation("Sloth view mode list"),
 *   uri_paths = {
 *     "canonical" = "/sloth/view-modes"
 *   }
 * )
 */
class SlothViewModeList extends ResourceBase {

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
  protected $config_factory;

  /* @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
  protected $entity_display_repository;


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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
    EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
    $this->config_factory = $config_factory;
    $this->entity_display_repository = $entity_display_repository;
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
      $container->get('logger.factory')->get('sloth'),
      $container->get('current_user'),
      \Drupal::service('config.factory'),
      \Drupal::service('entity_display.repository')
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
    $config_settings = $this->config_factory->get('sloth.settings');
    $config_view_modes = $config_settings->get('view_modes');
    if ( sizeof($config_view_modes) == 0 ) {
      throw new MissingOptionsException('No sloth view modes available.');
    }
    //Get definitions of all the view modes that exist for nodes,
    //so can return the label of the Chosen Ones.
    $all_view_modes = $this->entity_display_repository->getViewModes('node');
    $view_mode_list = [];
    foreach($config_view_modes as $view_mode_machine_name) {
      //Look up the label of the view mode.
      $view_mode_list[] = [
        'machineName' => $view_mode_machine_name,
        'label' => $all_view_modes[$view_mode_machine_name]['label'],
      ];
    }
    return new ResourceResponse($view_mode_list);
  }

}
