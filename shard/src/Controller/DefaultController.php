<?php

namespace Drupal\shard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\shard\ShardRegister;

/**
 * Class DefaultController.
 *
 * @package Drupal\shard\Controller
 */
class DefaultController extends ControllerBase {

  protected $shardRegister;

  public function __construct(ShardRegister $shardRegister) {
    $this->shardRegister = $shardRegister;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shard.register_plugins')
    );
  }


  /**
   * List plugins.
   *
   * @return string
   *   Return Hello string.
   */
  public function listPlugins() {
    $output = 'Plugins: ';
    foreach($this->shardRegister->getPlugins() as $plugin) {
      $output .= $plugin . ' ';
    }


    return [
      '#type' => 'markup',
      '#markup' => $this->t($output)
    ];
  }

}
