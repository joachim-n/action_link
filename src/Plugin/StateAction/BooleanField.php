<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * State action for toggling a boolean field on an entity.
 *
 * Defines just a single direction, so internally this is actually a 2-state
 * loop rather than a toggle.
 *
 * @StateAction(
 *   id = "boolean_field",
 *   label = @Translation("Boolean field"),
 *   description = @Translation("Action link to control the value of a boolean field."),
 *   dynamic_parameters = {
 *     "entity",
 *   },
 *   directions = {
 *     "toggle" = "toggle",
 *   },
 *   states = {
 *     "true",
 *     "false",
 *   },
 * )
 */
class BooleanField extends EntityFieldStateActionBase {

  use ToggleTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($element, $form_state);

    $element['entity_type_field']['#field_types'] = ['boolean'];

    $element['texts']['state']['true']['link_label']['#title'] = $this->t('Link label for setting the field value to TRUE');
    $element['texts']['state']['false']['link_label']['#title'] = $this->t('Link label for setting the field value to FALSE');

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
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
  public function advanceState(AccountInterface $account, string $state, EntityInterface $entity = NULL) {
    $field_name = $this->configuration['field'];

    $new_field_value = match($state) {
      'true' => 1,
      'false' => 0,
    };

    $entity->set($field_name, $new_field_value);
    $entity->save();
  }

}
