<?php
/**
 * Created by PhpStorm.
 * User: kieran
 * Date: 10/9/16
 * Time: 1:54 PM
 */

namespace Drupal\sloth\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;

class EligibleFields implements EligibleFieldsInterface {

  /**
   * Configuration storage service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /* $slothConfigs Configuration of the sloths, set by admin. */
  protected $slothConfigs;

  /* $configAllowedContentTypes What content types are allowed to have
   * sloths embedded in them. Stores an array of content type names.*/
  protected $configAllowedContentTypes;

  /* $config_allowed_ What fields are allowed to have
   * sloths embedded in them. Stores an array of field names. */
  protected $configAllowedFields;

  /* $configAllowedTieldTypes What field types are allowed to have
   * sloths embedded in them. Stores an array of type names. */
  protected $configAllowedFieldTypes;


  /**
   * EligibleFields constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              EntityFieldManagerInterface $entity_field_manager) {
    $this->configFactory = $config_factory;
    $this->entityFieldManager = $entity_field_manager;
    //Load module configs.
    $this->slothConfigs = $this->configFactory->get('sloth.settings');
    //Which content types have sloths embedded?
    $this->configAllowedContentTypes = $this->slothConfigs->get('content_types');
    //Get allowed fields.
    $this->configAllowedFields = $this->slothConfigs->get('fields');
    //Get allowed field types.
    $this->configAllowedFieldTypes = explode(',', $this->slothConfigs->get('field_types'));

  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Return a list of the names of fields that are allowed to have
   * sloth shards in them.
   *
   * @param EntityInterface $entity Entity with the fields.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEntityEligibleFields(EntityInterface $entity) {
    $entity_type_name = $entity->getEntityTypeId();
    $bundle_name = $entity->bundle();
    return $this->listEligibleFields( $entity_type_name, $bundle_name );
  }

  /**
   * Return a list of the names of fields that are allowed to have
   * sloth shards in them.
   *
   * @param string $entity_type_name Name of the entity type, e.g., node.
   * @param string $bundle_name Name of the bundle type, e.g., article.
   * @return array Names of fields that may have sloths embedded.
   */
  public function listEligibleFields($entity_type_name, $bundle_name) {
    $field_names = [];
    //Is this a node?
    if ($entity_type_name == 'node') {
      //Is this content type allowed?
      if (in_array($bundle_name, $this->configAllowedContentTypes)) {
        //Get definitions of the fields in the bundle.
        $field_defs = $this->entityFieldManager->getFieldDefinitions('node', $bundle_name);
        //Loop across fields.
        foreach ($field_defs as $field_name => $field_def) {
          //Is the field allowed?
          if (in_array($field_name, $this->configAllowedFields)) {
            //Is the field type allowed?
            if (in_array(
              $field_def->getFieldStorageDefinition()->getType(),
              $this->configAllowedFieldTypes
            )) {
              //The field can have sloths in it.
              $field_names[] = $field_name;
            } //End field type is allowed.
          } //End field is allowed.
        } //End foreach.
      } //End content type is allowed.
    } //End entity is a node.
    return $field_names;
  }

}