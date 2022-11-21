<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Form\FormStateInterface;

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
class BooleanField extends StateActionBase {

  public function buildConfigurationForm(array $plugin_form, FormStateInterface $form_state) {
    $plugin_form['entity_type'] = [
      '#type' => 'textfield', // todo options
      '#title' => $this->t('Entity type'),
      // '#options' => [],
    ];

    $plugin_form['field'] = [
      '#type' => 'textfield', // todo options
      '#title' => $this->t('field'),
      // '#options' => [],
    ];

    // delta??

    return $plugin_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName($user, ...$parameters): string {
    list($entity) = $parameters;
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
  public function checkOperability() {
    // .

    // also sanity check $state is true/false!
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
    $parameters = $this->upcastRouteParameters($parameters);
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
