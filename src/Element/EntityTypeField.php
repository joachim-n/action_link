<?php

namespace Drupal\action_link\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form element for selecting an entity type and field.
 *
 * Available properties:
 *  - '#field_types': (optional) An array of field types to include. If empty,
 *    fields of all types are shown.
 *  - '#field_options_filters': (optional) An array of callbacks which can
 *    alter the field options. These receive the following parameters:
 *     - $field_options: The array of field options.
 *     - $selected_entity_type_id: The selected entity type ID.
 *     - $field_map_for_entity_type: The field map for the entity type.
 *     - $form_state: The form state.
 *
 * The #default_value property may be set in the following format:
 * @code
 * [
 *  'entity_type_id' => $entity_type_id,
 *  'field' => $field_name,
 * ]
 * @endcode
 *
 * @FormElement("entity_type_field")
 */
class EntityTypeField extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;

    return [
      '#input' => TRUE,
      '#field_types' => [],
      '#process' => [
        [$class, 'processEntityType'],
      ],
      '#options_element_type' => 'select',
      // @todo Not yet implemented.
      '#entity_type_options_filters' => [],
      '#field_options_filters' => [],
    ];
  }

  /**
   * Process callback.
   */
  public static function processEntityType(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    $container_html_id = HtmlUtility::getUniqueId('cake');
    $element['container'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $element['#title'] ?? 'Entity type and field',
      '#attributes' => ['id' => $container_html_id],
    ];

    // Try to get a default value for the entity_type_id element.
    $entity_type_id_parents = $element['#parents'];
    $entity_type_id_parents[] = 'entity_type_id';

    if ($selected_entity_type_id = $form_state->getValue($entity_type_id_parents)) {
      // A value set in the form by the user prior to an AJAX submission takes
      // precedence.
    }
    elseif (isset($element['#default_value']['entity_type_id'])) {
      // A default value in the form build.
      $selected_entity_type_id = $element['#default_value']['entity_type_id'];
    }
    else {
      // If we still don't have anything, use an empty value.
      $selected_entity_type_id = '';
    }

    $entity_type_options = [];
    foreach (\Drupal::service('entity_type.manager')->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->getGroup() != 'content') {
        continue;
      }
      $entity_type_options[$entity_type_id] = $entity_type->getLabel();
    }
    natcasesort($entity_type_options);

    $element['container']['entity_type_id'] = [
      '#type' => $element['#options_element_type'],
      '#title' => t('Entity type'),
      '#options' => $entity_type_options,
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#default_value' => $selected_entity_type_id,
      '#element_validate' => [
        [static::class, 'validateEntityType'],
      ],
      '#ajax' => [
        'callback' => get_class() . '::entityTypeDropdownCallback',
        'wrapper' => $container_html_id,
        'options' => [
          // Pass the array parents to the AJAX callback in a query parameter,
          // so that it can determine where in the form our element is located.
          'query' => [
            'element_parents' => implode('/', $element['#array_parents']),
          ],
        ],
      ],
    ];

    $elementX['container']['choose_entity_type_id'] = [
      '#type' => 'submit',
      '#value' => t('Choose entity type'),
      // '#attributes' => ['class' => ['ajax-example-hide', 'ajax-example-inline']],
    ];

    if ($selected_entity_type_id) {
      /** @var \Drupal\Core\Entity\EntityFieldManagerInterface */
      $entity_field_manager = \Drupal::service('entity_field.manager');
      $field_map_for_entity_type = $entity_field_manager->getFieldMap()[$selected_entity_type_id];

      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] */
      $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($selected_entity_type_id);

      $field_options = [];
      foreach ($field_map_for_entity_type as $field_id => $field_map_data) {
        if (!empty($element['#field_types'])) {
          if (!in_array($field_map_data['type'], $element['#field_types'])) {
            continue;
          }
        }

        // @todo Labels for config field storages are ugly!
        if (isset($field_storage_definitions[$field_id])) {
          $label = $field_storage_definitions[$field_id]->getLabel();
        }
        else {
          $label = $field_id;
        }
        $field_options[$field_id] = $label;
      }

      // Execute field options filter callbacks.
      foreach ($element['#field_options_filters'] as $filter_callback) {
        call_user_func_array($form_state->prepareCallback($filter_callback), [&$field_options, $selected_entity_type_id, $field_map_for_entity_type, $form_state]);
      }

      natcasesort($field_options);

      $element['container']['field'] = [
        '#type' => 'select',
        '#title' => t('Entity field'),
        '#options' => $field_options,
        '#empty_value' => '',
        '#default_value' => $element['#default_value']['field'] ?? NULL,
        '#required' => $element['#required'],
      ];

      if (empty($field_options)) {
        $element['container']['field']['#empty_option'] = t('No suitable fields on the @entity-type entity type. Please select another.', [
          '@entity-type' => $entity_type_options[$selected_entity_type_id],
        ]);
      }
    }

    return $element;
  }

  /**
   * Element validate callback.
   */
  public static function validateEntityType($element, FormStateInterface &$form_state, $form) {
    $triggering_element = $form_state->getTriggeringElement();

    // dsm($triggering_element);
    // $form_state->setRebuild(); // argh prevents save! but needed to handle no-JS button!
  }

  /**
   * AJAX callback for the entity type ID select element.
   */
  public static function entityTypeDropdownCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return $element['#default_value'] ?? [];
    }
    elseif ($input === NULL) {
      // Not sure how this happens.
      return '';
    }
    else {
      return $input['container'];
    }
  }

}
