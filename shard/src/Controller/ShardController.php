<?php

namespace Drupal\shard\Controller;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\shard\Exceptions\ShardException;
use Drupal\shard\Exceptions\ShardUnexpectedValueException;
use Drupal\shard\ShardDataRepository;
use Drupal\shard\ShardMetadataInterface;
use Drupal\shard\ShardMetadata;
use Drupal\shard\ShardModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ShardController.
 *
 * @package Drupal\shard\Controller
 */
class ShardController extends ControllerBase {

  /**
   * Object holding metadata for fields and nodes.
   *
   * @var ShardMetadataInterface
   */
  protected $metadata;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(
    ShardMetadataInterface $metadata,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->metadata = $metadata;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shard.metadata'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Finish shardable node creation or edit.
   *
   * @return string
   *   Return Hello string.
   */
  public function processSavedNode() {
    $dataRepo = new ShardDataRepository();
    $dataRepo->unserialize(
      $this->metadata->fetchStringFromConfig(
        ShardMetadata::SHARD_NODE_SAVE_DATA_REPO
      )
    );
    //Create the field collection items, replacing the placeholder
    //host nid with the actual value.
    $actualHostNid = $dataRepo->getActualHostNid();
    $placeHolderHostNid = $dataRepo->getHostPlaceholderNid();
    $shardModels = $dataRepo->getNewShardCollectionItems();
    /* @var ShardModel  $shardModel */
    foreach ($shardModels as $shardModel) {
      //Store the host node's nid.
      $shardModel->setHostNid($actualHostNid);
      //Save to DB. The item id is added to the model during the process.
      $shardModel->saveNewShardCollectionItem();
    }
    //Now run through each of the eligible fields in the host node,
    //replacing the shard id placeholder with the real value.
    /* @var EntityInterface $hostNode */
    $hostNode = $this->entityTypeManager->getStorage('node')
      ->load($actualHostNid);
    //Make an array, key is temp id (UUID) of a shard model, value
    //is the actual id, supplied during saving of entity.
    $idMap = $this->mapPlaceHolderIdsToActual($shardModels);
    /* @var ShardMetadata $metadata */
    $metadata = \Drupal::service('shard.metadata');
    $eligibleFields = $metadata->listEligibleFieldsForNode($hostNode);
    foreach($eligibleFields as $fieldName) {
      //Does the field exist in the node? Not all eligible fields are
      //used in a every content type.
      if ($hostNode->get($fieldName)) {
        try {
          $fieldValues = $hostNode->get($fieldName)->getValue();
          if ($fieldValues) {
            //Loop over each value for this field (could be multivalued).
            for ($delta = 0; $delta < sizeof($fieldValues); $delta++) {
              if ( $fieldValues[$delta]['value'] ) {
                $fieldValues[$delta]['value']
                  = $this->replacePlaceHolderIdsWithActual(
                      $fieldValues[$delta]['value'],
                      $idMap
                  );
              } //End there is a value.
            } //End for each delta.
          } //End there are values.
          $hostNode->set($fieldName, $fieldValues);
        } catch (ShardException $e) {

        }
      } //End the field exists.
    }// End for each eligible field.
    $hostNode->save();
    //Show the new node.
    return $this->redirect(
      'entity.node.canonical',
      ['node' => $actualHostNid]
    );
  }

  /**
   * In some HTML, replace UUIDs with real ids.
   *
   * @param string $html
   * @param array[] $idMap Keys are UUIDs, values are real ids.
   * @return string
   */
  protected function replacePlaceHolderIdsWithActual($html, $idMap) {
    $newHtml = preg_replace_callback(
      '/' . Uuid::VALID_PATTERN . '/i',
      function ($matches) use ($idMap) {
        $uuid = $matches[0];
        if ( ! array_key_exists($uuid, $idMap) ) {
          throw new ShardUnexpectedValueException(
            sprintf('UUID unknown: %s.', $uuid)
          );
        }
        return $idMap[$uuid];
      },
      $html
    );
    return $newHtml;
  }

  /**
   * @param ShardModel[] $shardModels
   * @return array
   */
  protected function mapPlaceHolderIdsToActual($shardModels) {
    $idmap = [];
    foreach ($shardModels as $shardModel) {
      $placeholder = $shardModel->getShardPlaceHolderId();
      if ( ! $shardModel->isValidPlaceHolderId($placeholder) ) {
        throw new ShardUnexpectedValueException(
          sprintf('Placeholder nid should be UUID. Got: %s', $placeholder)
        );
      }
      $actual = $shardModel->getShardId();
      if ( ! $shardModel->isValidShardId($actual) ) {
        throw new ShardUnexpectedValueException(
          sprintf('Bad shard id: %s', $actual)
        );
      }
      $idmap[$placeholder] = $actual;
    }
    return $idmap;
  }

}
