<?php

namespace Drupal\action_link\Element;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\action_link\Utility\NestedArrayRecursive;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form element for selecting a plugin.
 *
 * TODO: Replace this with Plugin module when
 * https://www.drupal.org/project/plugin/issues/3197304 is fixed.
 *
 * Required settings:
 *  - #plugin_type: The name of a plugin type. Note that not all plugin types
 *   work, as some have plugins with nonstandard constructors, such as field
 *   formatter and widget plugins.
 * Optional properties include:
 *  - #options_element_type: The element type to use for selecting the plugin.
 *   May be one of 'select' (the default) or 'radios'.
 *  - #default_value: The plugin ID.
 *
 * @FormElement("action_link_plugin")
 */
class Plugin extends FormElement {

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
    $plugin_manager = \Drupal::service('plugin.plugin_type_manager')->getPluginType($element['#plugin_type'])->getPluginManager();

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
      return '';
    }
    else {
      return $input['plugin_id'];
    }
  }

  public static function validatePlugin(&$element, FormStateInterface $form_state, &$complete_form) {
    $plugin_id = $element['plugin_id']['#value'];

    // Set the value at the top level of the element.
    $form_state->setValueForElement($element['plugin_id'], NULL);
    $form_state->setValueForElement($element, $plugin_id);

    return $element;
  }

}
