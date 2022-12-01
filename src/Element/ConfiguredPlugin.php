<?php

namespace Drupal\action_link\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * TODO: class docs.
 *
 * @FormElement("configured_plugin")
 */
class ConfiguredPlugin extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    // dsm("!!");
    $class = static::class;

    return [
      '#input' => TRUE,
      // '#element_validate' => [
      //   [$class, 'validateDatetime'],
      // ],
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
      // '#date_date_format' => $date_format,
      // '#date_date_element' => 'date',
      // '#date_date_callbacks' => [],
      // '#date_time_format' => $time_format,
      // '#date_time_element' => 'time',
      // '#date_time_callbacks' => [],
      // '#date_year_range' => '1900:2050',
      // '#date_increment' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function setAttributes(&$element, $class = []) {
    // Sets a form element's class attribute.
  }

  public static function processPlugin(&$element, FormStateInterface $form_state, &$complete_form) {
    $options = [];
    foreach (static::getPluginManager()->getDefinitions() as $plugin_id => $plugin_definition) {
      $options[$plugin_id] = $plugin_definition['label'];
    }
    natcasesort($options);

    $element['container'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'] ?? '',
      // TODO! MUST BE UNIQUE IN PAGE!
      '#attributes' => ['id' => 'plugin-container'],
    ];

    // TODO!
    // if (!isset($element['#id'])) {
    //   $element['#id'] = $element['#options']['attributes']['id'] = HtmlUtility::getUniqueId('ajax-link');
    // }


    // NOPE! we might be in TREE! need PARENTS! from $element['#array_parents']!
    if (empty($form_state->getValue('plugin_id'))) {
      $selected_plugin_id = $element['#default_value']['plugin_id'] ?? '';
    }
    else {
      // Get the value if it already exists.
      $selected_plugin_id = $form_state->getValue('plugin_id');
    }

    $element['container']['plugin_id'] = [
      '#type' => $element['#options_element_type'],
      '#options' => $options,
      '#empty_value' => '',
      '#required' => $element['#required'],
      '#default_value' => $selected_plugin_id,
      '#ajax' => [
        // When 'event' occurs, Drupal will perform an ajax request in the
        // background. Usually the default value is sufficient (eg. change for
        // select elements), but valid values include any jQuery event,
        // most notably 'mousedown', 'blur', and 'submit'.
        'callback' => get_class() . '::pluginDropdownCallback',
        'wrapper' => 'plugin-container',
        'options' => [
          'query' => [
            'element_parents' => implode('/', $element['#array_parents']),
          ],
        ],
      ],
    ];

    if ($selected_plugin_id) {
      $plugin = static::getPluginManager()->createInstance($selected_plugin_id);

      $element['container']['plugin_configuration'] = $plugin->buildConfigurationForm([], $form_state);
    }

    return $element;
  }

  public static function pluginDropdownCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    return $form;
  }

  protected static function getPluginManager() {
    // Hardcoded for now, generalise later!
    return \Drupal::service('plugin.manager.action_link_state_action');
  }

}
