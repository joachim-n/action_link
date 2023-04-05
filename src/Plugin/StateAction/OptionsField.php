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
 *   description = @Translation("Action link to control the value of an options field."),
 *   dynamic_parameters = {
 *     "entity",
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
  public function advanceState(AccountInterface $account, string $state, EntityInterface $entity = NULL) {
    $field_name = $this->configuration['field'];

    $entity->set($field_name, $state);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getStateLabel(string $state): string {
    // @todo Inject.
    $entity_field_manager = \Drupal::service('entity_field.manager');
    // To get option values, we need a dummy entity, and to make one we need
    // a bundle. Since options are usually defined as a property of the
    // storage, it doesn't matter which bundle we use.
    $bundle = reset($entity_field_manager->getFieldMap()[$this->getTargetEntityTypeId()][$this->getTargetFieldName()]['bundles']);

    $ids = (object) [
      'entity_type' => $this->getTargetEntityTypeId(),
      'bundle' => $bundle,
      'entity_id' => NULL,
    ];
    $dummy_entity = _field_create_entity_from_ids($ids);

    $options_field_storage_definition = $entity_field_manager->getFieldStorageDefinitions($this->getTargetEntityTypeId())[$this->getTargetFieldName()];
    $allowed_options = options_allowed_values($options_field_storage_definition, $dummy_entity);

    return $allowed_options[$state] ?? $state;
  }

}
