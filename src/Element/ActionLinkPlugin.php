<?php

namespace Drupal\action_link\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\computed_field\Utility\NestedArrayRecursive;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * Form element for selecting and configuring a state action plugin.
 *
 * TODO: Replace this with Plugin module when
 * https://www.drupal.org/project/plugin/issues/3197304 is fixed.
 *
 * @FormElement("action_plugin")
 */
class ActionLinkPlugin extends FormElement {

  // use CompositeFormElementTrait?

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;

    return [
      '#input' => TRUE,
      '#element_validate' => [
        [$class, 'validatePluginConfiguration'],
      ],
      '#process' => [
        [$class, 'processPlugin'],
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
  public static function processPlugin(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    $container_html_id = Html::getUniqueId('ajax-link');
    $element['container'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $element['#title'] ?? '',
      '#attributes' => ['id' => $container_html_id],
    ];

    $plugin_id_parents = $element['#array_parents'];
    $plugin_id_parents[] = 'plugin_id';

    if (empty($form_state->getValue($plugin_id_parents))) {
      $selected_plugin_id = $element['#default_value']['plugin_id'] ?? '';
    }
    else {
      // Get the value if it already exists.
      $selected_plugin_id = $form_state->getValue($plugin_id_parents);
    }

    $element['container']['plugin_id'] = [
      '#type' => $element['#options_element_type'],
      '#title' => t("Action type"),
      '#options' => [],
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#default_value' => $selected_plugin_id,
      '#ajax' => [
        'callback' => get_class() . '::pluginDropdownCallback',
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

    // Build the plugin options.
    $options = [];

    $plugins_method = $element['#plugins_method'] ?? 'getDefinitions';
    foreach (static::getPluginManager()->{$plugins_method}() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];

      // Add plugin descriptions to radios, if they exist.
      if (isset($plugin_definition['description']) && $element['#options_element_type'] == 'radios') {
        $element['container']['plugin_id'][$plugin_id]['#description'] = $plugin_definition['description'];
      }
    }
    natcasesort($options);
    $element['container']['plugin_id']['#options'] = $options;

    // Add the plugin's configuration form, if it provides one.
    if ($selected_plugin_id) {
      $plugin = static::getPluginManager()->createInstance($selected_plugin_id);
      if ($plugin instanceof PluginFormInterface) {
        $plugin_subform = [
          '#default_value' => $element['#default_value']['plugin_configuration'],
        ];
        $element['container']['plugin_configuration'] = $plugin->buildConfigurationForm($plugin_subform, SubformState::createForSubform($plugin_subform, $form_state->getCompleteForm(), $form_state));

        // If this is the original load of the form, set the default values on
        // the plugin configuration.
        if (isset($element['#default_value']['plugin_id']) && $element['#default_value']['plugin_id'] == $selected_plugin_id) {
          $plugin_configuration_form = &$element['container']['plugin_configuration'];

          // Recurse into nested configuration values.
          NestedArrayRecursive::arrayWalkNested($element['#default_value']['plugin_configuration'], function($value, $parents) use (&$plugin_configuration_form) {
            $default_value_parents = $parents;
            $default_value_parents[] = '#default_value';

            // Allow plugin forms to set default values themselves.
            $default_value_key_exists = NULL;
            NestedArray::getValue($plugin_configuration_form, $default_value_parents, $default_value_key_exists);

            if (!$default_value_key_exists) {
              NestedArray::setValue($plugin_configuration_form, $default_value_parents, $value);
            }
          });
        }
      }
      else {
        $element['container']['plugin_configuration'] = [
          '#markup' => t("The @label plugin has no configuration.", [
            '@label' => $plugin->getPluginDefinition()['label'],
          ]),
        ];
      }
    }

    return $element;
  }

  /**
   * AJAX callback for the plugin ID select element.
   */
  public static function pluginDropdownCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    return $form;
  }

  /**
   * Element validate callback.
   */
  public static function validatePluginConfiguration($element, FormStateInterface &$form_state, $form) {
    $plugin_id_parents = $element['#array_parents'];
    $plugin_id_parents[] = 'plugin_id';
    $selected_plugin_id = $form_state->getValue($plugin_id_parents);
    if ($selected_plugin_id) {
      $plugin = static::getPluginManager()->createInstance($selected_plugin_id);
      if ($plugin instanceof PluginFormInterface) {
        $plugin->validateConfigurationForm($element['container']['plugin_configuration'], SubformState::createForSubform($element['container']['plugin_configuration'], $form_state->getCompleteForm(), $form_state));
      }
    }
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

  /**
   * Gets the plugin manager.
   *
   * @return mixed
   *   The plugin manager service for the plugin type.
   */
  protected static function getPluginManager() {
    // Hardcoded for now, generalise later!
    return \Drupal::service('plugin.manager.action_link_state_action');
  }

}
