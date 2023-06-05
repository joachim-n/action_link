<?php

namespace Drupal\action_link\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Form element for selecting a plugin.
 *
 * Optional properties include:
 *  - #options_element_type: The element type to use for selecting the plugin.
 *   May be one of 'select' (the default) or 'radios'.
 *  - #default_value: The plugin ID.
 *
 * @todo Replace this with Plugin module when
 * https://www.drupal.org/project/plugin/issues/3197304 is fixed. Any changes to
 * this class should be submitted to the merge request on that issue.
 *
 * @FormElement("action_link_style_plugin")
 */
class LinkStylePlugin extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;

    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processPlugin'],
      ],
      '#options_element_type' => 'select',
    ];
  }

  /**
   * Process callback.
   */
  public static function processPlugin(&$element, FormStateInterface $form_state, &$complete_form) {
    $plugin_manager = static::getPluginManager();

    $plugin_id_parents = $element['#array_parents'];
    $plugin_id_parents[] = 'plugin_id';

    $element['#tree'] = TRUE;

    // This needs to be a nested element so the radio or select element
    // processing and theming takes place.
    $element['plugin_id'] = [
      // TODO: enforce either select or radios.
      '#type' => $element['#options_element_type'],
      '#title' => $element['#title'],
      '#options' => [],
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#default_value' => $element['#default_value'] ?? '',
    ];

    // Build the plugin options.
    $options = [];
    foreach ($plugin_manager->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];

      // Add plugin descriptions to radios, if they exist.
      if (isset($plugin_definition['description']) && $element['#options_element_type'] == 'radios') {
        $element['plugin_id'][$plugin_id]['#description'] = $plugin_definition['description'];
      }
    }
    natcasesort($options);
    $element['plugin_id']['#options'] = $options;

    $element['#element_validate'] = [[static::class, 'validatePlugin']];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return $element['#default_value'] ?? '';
    }
    elseif (is_null($input)) {
      return $element['#default_value'] ?? '';
    }
    else {
      return $input['plugin_id'];
    }
  }

  public static function validatePlugin(&$element, FormStateInterface $form_state, &$complete_form) {
    $plugin_id = $element['plugin_id']['#value'];

    // Set the value at the top level of the element.
    // WARNING: This doesn't work in a config entity form, because when the
    // form is rebuilt on submission, copyFormValuesToEntity() is called and
    // calls $entity->getPluginCollections(), and at that point, the values of
    // this element is an array, which causes a crash in the plugin collection
    // system. The value is an array because after this element is processed
    // and sets its value to a string, the nested 'plugin_id' radios/select
    // element is processed, and sets *its* value, and that causes NestedArray
    // to create an array in the form values.
    $form_state->setValueForElement($element['plugin_id'], NULL);
    $form_state->setValueForElement($element, $plugin_id);

    return $element;
  }

  /**
   * Gets the plugin manager.
   *
   * @return mixed
   *   The plugin manager service for the plugin type.
   */
  protected static function getPluginManager() {
    // Hardcoded for now, generalise later!
    return \Drupal::service('plugin.manager.action_link_style');
  }

}
