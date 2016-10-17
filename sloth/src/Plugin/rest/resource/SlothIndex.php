<?php

namespace Drupal\sloth\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Provides a REST resource to get a list of sloths.
 *
 * @RestResource(
 *   id = "sloth_index",
 *   label = @Translation("Get sloth index"),
 *   uri_paths = {
 *     "canonical" = "/sloth/index"
 *   }
 * )
 */
class SlothIndex extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  protected $entityTypeManager;

  protected $entityQuery;

  protected $nodeStorage;

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
    $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $type_manager,
    QueryFactory $entity_query
) {
    parent::__construct($configuration, $plugin_id, $plugin_definition,
      $serializer_formats, $logger);
    $this->container = $container;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $type_manager;
    $this->entityQuery = $entity_query;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
           array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('sloth'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity.query')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
//  public function get() {
//
//    return new ResourceResponse('Dogs are good!');

//    if (!$this->currentUser->hasPermission('access content')) {
//      throw new AccessDeniedHttpException();
//    }
//    $node = $this->container->get('entity_type.manager')->getStorage('node')->load($nid);
//    $title = $node->get('title')->value;  //$node->title->getValue();
//    $weight = $node->get('field_weight')->value;
//    $notes = $node->get('field_notes')->value;
//    $friends = $node->get('field_friends')->getValue();
//    $friends_result = [];
//    foreach($friends as $friend) {
//      $id = $friend['target_id'];
//      if ( is_numeric($id) ) {
//        $id = (integer)$id;
//      }
//      $friends_result[] = $id;
//    }
//    $dog = [
//      'title' => $title,
//      'field_weight' => $weight,
//      'field_notes' => $notes,
//      'field_friends' => $friends_result,
//    ];
//
//    return new ResourceResponse($dog);
//  }

  public function get() {
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }
    $query = $this->entityQuery->get('node')
      ->condition('type', 'sloth')
      ->condition('status', 1)
      ->sort('title');
    $result = $query->execute();
    $nodes = $this->nodeStorage->loadMultiple($result);
    $sloths = [];
    foreach ($nodes as $node) {
      $sloths[] = [
        'nid' => $node->nid->value,
        'title' => $node->title->value,
        'previews' => [],
      ];
    }
    return new ResourceResponse($sloths);
  }
}
