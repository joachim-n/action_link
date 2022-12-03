<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * TODO: class docs.
 *
 * @StateAction(
 *   id = "numeric_field",
 *   label = @Translation("Numeric field"),
 *   description = @Translation("Numeric field TODO"),
 *   parameters = {
 *     "dynamic" = {
 *       "entity",
 *       "direction",
 *     },
 *     "configuration" = {
 *       "entity_type",
 *       "field",
 *       "step",
 *     },
 *   },
 *   directions = {
 *     "inc",
 *     "dec",
 *   },
 * )
 */
class NumericField extends EntityStateActionBase {

  use RepeatableTrait;

  use StringTranslationTrait;

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

    $plugin_form['step'] = [
      '#type' => 'number',
      '#title' => $this->t('Step'),
      '#required' => TRUE,
    ];

    return $plugin_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName($user, EntityInterface $entity = NULL, string $direction = ''): ?string {
    $field_name = $this->configuration['field'];

    $step = $this->configuration['step'];

    $value = $entity->get($field_name)->value;

    $next_value = match($direction) {
      'inc' => $value + $step,
      'dec' => $value - $step,
    };

    return $next_value;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess() {
    // .
  }

  public function advanceState($account, $state, $parameters) {
    list($entity, $direction) = $parameters;

    // TODO:

    $field_name = $this->configuration['field'];

    $entity->set($field_name, $state);
    $entity->save();
  }

}
