<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * State action for cycling through an options field on an entity.
 *
 * @StateAction(
 *   id = "options_field",
 *   label = @Translation("Options field"),
 *   description = @Translation("Options field TODO"),
 *   parameters = {
 *     "dynamic" = {
 *       "entity",
 *     },
 *     "configuration" = {
 *       "entity_type",
 *       "field",
 *     },
 *   },
 *   directions = {
 *     "inc" = "forward",
 *     "dec" = "back",
 *   },
 * )
 */
class OptionsField extends EntityFieldStateActionBase {

  use RepeatableTrait;

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($element, $form_state);

    $element['entity_type_field']['#field_types'] = [
      'list_float',
      'list_integer',
      'list_string',
    ];

    // delta??

    $element['labels'] = [
      '#tree' => TRUE,
    ];
    $element['labels'] = $this->buildTextsConfigurationForm($element['labels'], $form_state);

    $element['labels']['direction']['inc']['link_label']['#title'] = $this->t('Link label for moving the field value forward');
    $element['labels']['direction']['dec']['link_label']['#title'] = $this->t('Link label for moving the field value back');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
    $field_name = $this->configuration['field'];

    $value = $entity->get($field_name)->value;

    $values = $entity->get($field_name)->get(0)->getPossibleValues($user);

    $current_value_index = array_search($value, $values);

    $new_index = match ($direction) {
      'inc' => ($current_value_index + 1) % count($values),
      'dec' => $current_value_index - 1,
    };

    if ($new_index < 0) {
      $new_index = count($values) - 1;
    }

    $next_value = $values[$new_index];

    return $next_value;
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState(AccountInterface $account, string $state, ...$parameters) {
    list($entity) = $parameters;

    $field_name = $this->configuration['field'];

    $entity->set($field_name, $state);
    $entity->save();
  }

}
