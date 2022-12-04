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
 * @FormElement("entity_field")
 */
class EntityField extends FormElement {

  // use CompositeFormElementTrait?

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;

    return [
      '#input' => TRUE,
      // '#element_validate' => [
      //   [$class, 'validatePluginConfiguration'],
      // ],
      '#process' => [
        [$class, 'processEntityType'],
        // [$class, 'processAjaxForm'],
        // [$class, 'processGroup'],
      ],
      '#options_element_type' => 'select',
      // '#pre_render' => [
      //   [$class, 'preRenderGroup'],
      // ],
      // '#theme' => 'datetime_form',
      // '#theme_wrappers' => ['datetime_wrapper'],
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
      // '#type' => 'container',
      '#type' => 'details',
      '#open' => TRUE,
      // '#open' => TRUE,
      '#title' => $element['#title'] ?? 'Entity type',
      '#attributes' => ['id' => $container_html_id],
    ];

    $element['container']['time'] = [
      '#markup' => REQUEST_TIME,
    ];

    // Try to get a default value for the entity_type_id element.
    $entity_type_id_parents = $element['#array_parents'];
    $entity_type_id_parents[] = 'container';
    $entity_type_id_parents[] = 'entity_type_id';

    $values = $form_state->getValues();
    if ($selected_entity_type_id = $form_state->getValue($entity_type_id_parents)) {
      // A value set in the form by the user prior to an AJAX submission takes
      // precedence.
    }
    elseif (isset($element['#default_value']['entity_type_id'])) {
      // A default value in the form build.
      $selected_entity_type_id = $element['#default_value']['entity_type_id'];
    }
    else {
      // Finally, an empty value.
      $selected_entity_type_id = '';
    }

    $element['container']['et'] = [
      '#markup' => 'SELECTED: ' . $selected_entity_type_id,
    ];


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
      '#title' => t("Entity type ELEMENT"),
      '#options' => $options,
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#default_value' => $selected_entity_type_id,
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

    // $selected_entity_type_id = 'node';
    if ($selected_entity_type_id) {
      $field_storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($selected_entity_type_id);
      // dsm($field_storage_definitions);

      $field_options = [];
      foreach ($field_storage_definitions as $field_id => $field_storage_definition) {
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
  public static function XvalueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return $element['#default_value'] ?? [];
    }
    else {
      return $input['container'];
    }
  }

}
