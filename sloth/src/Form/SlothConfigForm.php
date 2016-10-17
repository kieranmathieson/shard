<?php
/**
 * @file
 * Configuration for the sloth module.
 *
 * @author Kieran Mathieson
 */

namespace Drupal\sloth\Form;

use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;

class SlothConfigForm extends FormBase {

  /* @var \Symfony\Component\DependencyInjection\ContainerInterface $container */
  protected $container;
  /* @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_manager */
  protected $bundle_info_manager;
  /* @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
  protected $entity_field_manager;
  /* @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository */
  protected $entity_display_repository;
  /* @var \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager */
  protected $typed_data_manager;
  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config_factory;

  public function __construct(
      ContainerInterface $container,
      EntityTypeBundleInfoInterface $bundle_info_manager,
      EntityFieldManagerInterface $entity_field_manager,
      EntityDisplayRepositoryInterface $entity_display_repository,
      ConfigFactoryInterface $config_factory,
      TypedDataManagerInterface $typed_data_manager) {
    $this->container = $container;
    $this->bundle_info_manager = $bundle_info_manager;
    $this->entity_field_manager = $entity_field_manager;
    $this->entity_display_repository = $entity_display_repository;
    $this->config_factory = $config_factory;
    $this->typed_data_manager = $typed_data_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      \Drupal::getContainer(),
      \Drupal::service('entity_type.bundle.info'),
      \Drupal::service('entity_field.manager'),
      \Drupal::service('entity_display.repository'),
      \Drupal::service('config.factory'),
      \Drupal::service('typed_data_manager')
    );
  }

  /**
   * Build the simple form.
   *
   * A build form method constructs an array that defines how markup and
   * other form elements are included in an HTML form.
   *
   * @param array $form
   *   Default form array structure.
   * @param FormStateInterface $form_state
   *   Object containing current form state.
   *
   * @return array
   *   The render array defining the elements of the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //Get current settings.
    $config = $this->config_factory->get('sloth.settings');
    $current_content_types = $config->get('content_types')
      ? $config->get('content_types') : [];
    $current_fields = $config->get('fields')
      ? $config->get('fields') : [];
    $current_view_modes = $config->get('view_modes')
      ? $config->get('view_modes') : [];
    //Field types that can have sloths inserted. They are CK enabled. most likely.
    $current_field_types = $config->get('field_types')
      ? explode(',', $config->get('field_types')) : ['text_with_summary', 'text_long' ];

    //Don't flatten the results in $form_state.
    $form['#tree'] = TRUE;

    //Get the content types.
    $node_bundle_info = $this->bundle_info_manager->getBundleInfo('node');
    //Ask about content types.
    $form['content_types'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Content types'),
    ];
    foreach ($node_bundle_info as $bundle_machine_name => $bundle_label_element) {
      $set = in_array($bundle_machine_name, $current_content_types);
      $form['content_types'][$bundle_machine_name] = [
        '#type' => 'checkbox',
        '#title' => $this->t($bundle_label_element['label']),
        '#default_value' => $set,
      ];
    }

    //Load fields for each content type.
    /* @var CandidateField[] $candidate_fields */
    $candidate_fields = [];
    //Loop over content types.
    foreach ($node_bundle_info as $bundle_machine_name=>$bundle_label_element) {
      //Get the fields in a content type.
      $bundle_fields = $this->entity_field_manager->getFieldDefinitions('node', $bundle_machine_name);
      //Loop across the field definitions.
      /* @var \Drupal\field\Entity\FieldConfig $field_def */
      foreach($bundle_fields as $field_name => $field_def) {
        //Is the field of a type we allow to have sloth info in?
        $field_type = $field_def->getType();
        if ( in_array($field_type, $current_field_types) ) {
          //This field is a candidate.
          //Add to the candidate list if it isn't there.
          if ( ! key_exists($field_name, $candidate_fields) ) {
            $candidate_fields[$field_name] =
              new CandidateField($field_name, (string)$this->t($field_def->getLabel()));
          }
          $candidate_fields[$field_name]->addContentType( (string)$this->t($bundle_label_element['label']) );
        }
      }//End foreach field in bundle.
    }//End for each bundle.
    //Show the candidate fields.
    $form['fields'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('Fields'),
    ];
    if ( sizeof($candidate_fields) == 0 ) {
      $form['fields']['problem'] = [
        '#type' => 'markup',
        '#markup' => $this->t(
          "Strange. No fields are eligible. Please check the field types below,
           and make sure that fields of those types exist in a content type."
        ),
      ];
    }
    else {
      /* @var CandidateField $candidate_field */
      foreach ($candidate_fields as $field_machine_name => $candidate_field) {
        $set = in_array($field_machine_name, $current_fields);
        $form['fields'][$field_machine_name] = [
          '#type' => 'checkbox',
          '#title' => $this->t('@displayName (machine name: @machineName)',
            [
              '@displayName' => $candidate_field->getDisplayName(),
              '@machineName' => $field_machine_name,
            ]
          ),
          '#description' => $this->t('Used in @fieldList',
            [
              '@fieldList' => $candidate_field->getContentTypeListString()
            ]
          ),
          '#default_value' => $set,
        ];
      }
    }

    //View modes.
    $view_modes = $this->entity_display_repository->getViewModes('node');
    $form['view_modes'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
      '#title' => $this->t('View modes'),
    ];
    foreach($view_modes as $view_mode_machine_name => $view_mode) {
      $set = in_array($view_mode_machine_name, $current_view_modes);
      $label = $view_mode['label'];
      $form['view_modes'][$view_mode_machine_name] = [
        '#type' => 'checkbox',
        '#title' => $label,
        '#default_value' => $set,
      ];
    }

    //Field types
    $form['field_types'] = [
      '#type' => 'details',
      '#title' => $this->t('Field types'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];
    $form['field_types']['type_list'] = [
      '#type' => 'textfield',
      '#size' => 50,
      '#title' => $this->t('List of field types'),
      '#description' => 'Comma separated list of field types',
      '#default_value' => implode(',',$current_field_types),
    ];

    // Group submit handlers in an actions element with a key of "actions" so
    // that it gets styled correctly, and so that other modules may add actions
    // to the form. This is not required, but is convention.
    $form['actions'] = [
      '#type' => 'actions',
    ];

    // Add a submit button that handles the submission of the form.
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Getter method for Form ID.
   *
   * The form ID is used in implementations of hook_form_alter() to allow other
   * modules to alter the render array built by this form controller.  it must
   * be unique site wide. It normally starts with the providing module's name.
   *
   * @return string
   *   The unique ID of the form defined by this class.
   */
  public function getFormId() {
    return 'sloth_config_form';
  }

  /**
   * Implements form validation.
   *
   * The validateForm method is the default method called to validate input on
   * a form.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    //At least one content type should be selected.
    if ( ! $this->at_least_one_chosen($form_state, 'content_types')) {
      $form_state->setErrorByName('content_types',
        $this->t('Please choose at least one content type.'));
    }
    //At least one field should be selected.
    if ( ! $this->at_least_one_chosen($form_state, 'fields')) {
      $form_state->setErrorByName('fields',
        $this->t('Please choose at least one field.'));
    }
    //At least one view mode should be selected.
    if ( ! $this->at_least_one_chosen($form_state, 'view_modes')) {
      $form_state->setErrorByName('view_modes',
        $this->t('Please choose at least one view mode.'));
    }
    //Were field types given?
    $field_type_contents = trim($form_state->getValue('field_types')['type_list']);
    if ( ! $field_type_contents ) {
      $form_state->setErrorByName('field_types',
        $this->t('Please specify some field types, separated by commas. When in '
          . 'doubt, use this: text_with_summary,text_long'));
    }
    else {
      //Make sure each of the field types exists.
      $submitted_field_types = explode(',', $field_type_contents);
      $bad_field_types = [];
      //Get a list of available field types.
      $data_type_defs = $this->typed_data_manager->getDefinitions();
      $data_type_names = array_keys($data_type_defs);
      //Keys are things like field_item:integer
      $available_field_types = [];
      foreach ($data_type_names as $data_type_name) {
        $name_chunks = explode(':', $data_type_name);
        if ($name_chunks[0] == 'field_item') {
          $available_field_types[] = $name_chunks[1];
        }
      }
      //Are the submitted field types in the list?
      foreach ($submitted_field_types as $submitted_field_type) {
        $submitted_field_type = trim($submitted_field_type);
        if (!in_array($submitted_field_type, $available_field_types)) {
          $bad_field_types[] = $submitted_field_type;
        }
      }
      if (sizeof($bad_field_types) > 0) {
        $message_part = sizeof($bad_field_types) == 1
          ? 'this field type is unknown'
          : 'these field types are unknown';
        $message = $this->t('Sorry, ' . $message_part . ': @fieldTypes',
          ['@fieldTypes' => implode(' ', $bad_field_types)]);
        $form_state->setErrorByName('field_types', $message);
      }
    }
  }

  /**
   * Was at least one option chosen from a checkbox list?
   * @param FormStateInterface $form_state Form state.
   * @param string $field_name Field to check.
   * @return bool True if a value was chosen, else false.
   */
  protected function at_least_one_chosen(FormStateInterface $form_state, $field_name) {
    $submitted = $form_state->getValue($field_name);
    $chosen = FALSE;
    foreach ($submitted as $key => $is_selected) {
      if ( $is_selected ) {
        $chosen = TRUE;
        break;
      }
    }
    return $chosen;
  }

  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param FormStateInterface $form_state
   *   Object describing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_settings = $this->config_factory->getEditable('sloth.settings');
    //Save content types.
    $content_types_submitted = $form_state->getValue('content_types');
    $content_types_to_save = [];
    foreach ($content_types_submitted as $machine_name => $is_selected) {
      if ( $is_selected ) {
        $content_types_to_save[] = $machine_name;
      }
    }
    $config_settings->set('content_types', $content_types_to_save);

    //Fields.
    $fields_submitted = $form_state->getValue('fields');
    $fields_to_save = [];
    foreach ($fields_submitted as $machine_name => $is_selected) {
      if ( $is_selected ) {
        $fields_to_save[] = $machine_name;
      }
    }
    $config_settings->set('fields', $fields_to_save);

    //Save view modes.
    $view_modes_submitted = $form_state->getValue('view_modes');
    $view_modes_to_save = [];
    foreach ($view_modes_submitted as $machine_name => $is_selected) {
      if ( $is_selected ) {
        $view_modes_to_save[] = $machine_name;
      }
    }
    $config_settings->set('view_modes', $view_modes_to_save);

    //Save field types.
    $field_types_submitted = $form_state->getValue('field_types')['type_list'];
    //Remove spaces.
    $field_types_submitted = str_replace(' ', '', $field_types_submitted);
    $config_settings->set('field_types', $field_types_submitted);

    $config_settings->save();

    drupal_set_message($this->t('The configuration has been saved.'));
  }

}
