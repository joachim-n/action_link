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
      // TODO: validate the plugin configuration with the plugibn class.
      // '#element_validate' => [
      //   [$class, 'validatePluginConfiguration'],
      // ],
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

    $container_html_id = HtmlUtility::getUniqueId('ajax-link');
    $element['container'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $element['#title'] ?? '',
      '#attributes' => ['id' => $container_html_id],
    ];

    $plugin_id_parents = $element['#array_parents'];
    $plugin_id_parents[] = 'plugin_id';

    if (empty($form_state->getValue($plugin_id_parents))) {
      $selected_plugin_id = $element['#default_value'] ?? '';
    }
    else {
      // Get the value if it already exists.
      $selected_plugin_id = $form_state->getValue($plugin_id_parents);
    }

    $element['container']['plugin_id'] = [
      // TODO: enforce either select or radios.
      '#type' => $element['#options_element_type'],
      '#title' => t("Plugin"),
      '#options' => [],
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#default_value' => $selected_plugin_id,
    ];

    // Build the plugin options.
    $options = [];
    foreach ($plugin_manager->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];

      // Add plugin descriptions to radios, if they exist.
      if (isset($plugin_definition['description']) && $element['#options_element_type'] == 'radios') {
        $element['container']['plugin_id'][$plugin_id]['#description'] = $plugin_definition['description'];
      }
    }
    natcasesort($options);
    $element['container']['plugin_id']['#options'] = $options;

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      return $element['#default_value'] ?? '';
    }
    else {
      return $input['container']['plugin_id'];
    }
  }

}
