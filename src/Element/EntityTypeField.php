<?php

namespace Drupal\action_link\Element;

use Drupal\action_link\Utility\NestedArrayRecursive;
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
 *
 * @FormElement("entity_type_field")
 */
class EntityTypeField extends FormElement {

  // use CompositeFormElementTrait?

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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function setAttributes(&$element, $class = []) {
    // Sets a form element's class attribute.
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
    // TODO: why do we need 'container' here but not in the plugin form element????
    // $entity_type_id_parents[] = 'container';
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


    $options = [];
    foreach (\Drupal::service('entity_type.manager')->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->getGroup() != 'content') {
        continue;
      }
      $options[$entity_type_id] = $entity_type->getLabel();
    }
    natcasesort($options);

    $element['container']['entity_type_id'] = [
      '#type' => $element['#options_element_type'],
      '#title' => t('Entity type'),
      '#options' => $options,
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

    $element['container']['choose_entity_type_id'] = [
      '#type' => 'submit',
      '#value' => t('Choose entity type'),
      // '#attributes' => ['class' => ['ajax-example-hide', 'ajax-example-inline']],
    ];

    if ($selected_entity_type_id) {
      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] */
      $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($selected_entity_type_id);

      $field_options = [];
      foreach ($field_storage_definitions as $field_id => $field_storage_definition) {
        if (!empty($element['#field_types'])) {
          if (!in_array($field_storage_definition->getType(), $element['#field_types'])) {
            continue;
          }
        }

        $field_options[$field_id] = $field_storage_definition->getLabel();
      }
      natcasesort($field_options);

      $element['container']['field'] = [
        '#type' => 'select',
        '#title' => t('Entity field'),
        '#options' => $field_options,
        '#empty_value' => '',
        '#required' => $element['#required'],
      ];
    }

    return $element;
  }

  /**
   * Element validate callback.
   */
  public static function validateEntityType($element, FormStateInterface &$form_state, $form) {
    $form_state->setRebuild();
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
    else {
      return $input['container'];
    }
  }

}
