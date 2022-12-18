<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * TODO: class docs.
 *
 * @StateAction(
 *   id = "boolean_field",
 *   label = @Translation("Boolean field"),
 *   description = @Translation("Boolean field TODO"),
 *   parameters = {
 *     "dynamic" = {
 *       "entity",
 *     },
 *     "configuration" = {
 *       "entity_type",
 *       "field",
 *     },
 *   },
 * )
 */
// TODO: allow customising state names -- eg published, flagged, yes, no. for nicer URLs.
class BooleanField extends EntityStateActionBase {

  use ToggleTrait;

  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $plugin_form = [];

    $plugin_form['entity_type_field'] = [
      '#type' => 'entity_type_field',
      '#title' => $this->t('Entity field'),
      '#field_types' => ['boolean'],

      '#element_validate' => [
        [static::class, 'entityFieldElementValidate'],
      ],
      '#default_value' => [
        'entity_type_id' => $element['#default_value']['plugin_configuration']['entity_type_id'] ?? '',
        'field' => $element['#default_value']['plugin_configuration']['field'] ?? '',
      ],
    ];

    // dsm($element);


    // $plugin_form['entity_type'] = [

    //   '#type' => 'textfield', // todo options
    //   '#title' => $this->t('Entity type'),
    //   // '#options' => [],
    // ];

    // $plugin_form['field'] = [
    //   '#type' => 'textfield', // todo options
    //   '#title' => $this->t('field'),
    //   // '#options' => [],
    // ];

    // TODO: field delta????? ARGH!

    $plugin_form['labels'] = [
      // '#parents' => ['labels'],
      '#tree' => TRUE,
    ];
    $plugin_form['labels'] = $this->buildTextsConfigurationForm($plugin_form['labels'], $form_state);

    $plugin_form['labels']['state']['true']['link_label']['#title'] = $this->t('Link label for setting the field value to TRUE');
    $plugin_form['labels']['state']['false']['link_label']['#title'] = $this->t('Link label for setting the field value to FALSE');

    return $plugin_form;
  }

  public static function entityFieldElementValidate(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $form_state->getValue($element['#parents']);

    // ARGH hardcoded array structure :(
    // Can't get this from slicing up $element['#parents'] because of the
    // 'container' from the plugin form element.
    $form_state->setValue(['plugin', 'plugin_configuration'], $value);
    // $form_state->setValue('cake', 'value');
    // dsm($form_state->getValues());
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    dsm($form_state->getValues());
  }

  public function copyFormValuesToEntity($entity, array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName($user, EntityInterface $entity = NULL): ?string {
    // dump($this);

    $field_name = $this->configuration['field'];

    $value = $entity->get($field_name)->value;
    return match ((bool) $value) {
      FALSE => 'true',
      TRUE  => 'false',
    };
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess() {
    // .
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState($account, $state, $parameters) {
    // $parameters = $this->upcastRouteParameters($parameters);
    list($entity) = $parameters;

    $field_name = $this->configuration['field'];

    $new_field_value = match($state) {
      'true' => 1,
      'false' => 0,
    };

    $entity->set($field_name, $new_field_value);
    $entity->save();
  }

}
