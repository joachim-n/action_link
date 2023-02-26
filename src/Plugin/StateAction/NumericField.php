<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * State action for incrementing or decrementing a numeric field on an entity.
 *
 * @StateAction(
 *   id = "numeric_field",
 *   label = @Translation("Numeric field"),
 *   description = @Translation("Numeric field TODO"),
 *   parameters = {
 *     "dynamic" = {
 *       "entity",
 *     },
 *     "configuration" = {
 *       "entity_type",
 *       "field",
 *       "step",
 *     },
 *   },
 *   directions = {
 *     "dec" = "decrease",
 *     "inc" = "increase",
 *   },
 * )
 */
class NumericField extends EntityFieldStateActionBase {

  use RepeatableTrait;

  use StringTranslationTrait;

  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $plugin_form = parent::buildConfigurationForm($element, $form_state);

    $plugin_form['entity_type_field']['#field_types'] = ['integer', 'decimal', 'float'];

    // delta??

    $plugin_form['step'] = [
      '#type' => 'number',
      '#title' => $this->t('Step'),
      '#required' => TRUE,
    ];

    $plugin_form['labels'] = [
      '#tree' => TRUE,
    ];
    $plugin_form['labels'] = $this->buildTextsConfigurationForm($plugin_form['labels'], $form_state);

    $plugin_form['labels']['direction']['inc']['link_label']['#title'] = $this->t('Link label for increasing the field value');
    $plugin_form['labels']['direction']['dec']['link_label']['#title'] = $this->t('Link label for decreasing the field value');

    return $plugin_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
    $field_name = $this->configuration['field'];

    $step = $this->configuration['step'];

    $value = $entity->get($field_name)->value;

    $next_value = match($direction) {
      'inc' => $value + $step,
      'dec' => $value - $step,
    };

    return $next_value;
  }

  public function advanceState(AccountInterface $account, string $state, array $parameters) {
    list($entity) = $parameters;

    // TODO:

    $field_name = $this->configuration['field'];

    $entity->set($field_name, $state);
    $entity->save();
  }

}
