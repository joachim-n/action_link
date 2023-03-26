<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

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
 *     "toggle" = "toggle",
 *   },
 *   states = {
 *     "true",
 *     "false",
 *   },
 * )
 */
// TODO: allow customising state names -- eg published, flagged, yes, no. for nicer URLs.
// two directions, but also only two states.
// how does getAllLinks know which one? OPERABILITY!
class BooleanField extends EntityFieldStateActionBase {

  use ToggleTrait;

  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($element, $form_state);

    $element['entity_type_field']['#field_types'] = ['boolean'];

    // dsm($element);


    // $element['entity_type'] = [

    //   '#type' => 'textfield', // todo options
    //   '#title' => $this->t('Entity type'),
    //   // '#options' => [],
    // ];

    // $element['field'] = [
    //   '#type' => 'textfield', // todo options
    //   '#title' => $this->t('field'),
    //   // '#options' => [],
    // ];

    // TODO: field delta????? ARGH!


    $element['labels']['state']['true']['link_label']['#title'] = $this->t('Link label for setting the field value to TRUE');
    $element['labels']['state']['false']['link_label']['#title'] = $this->t('Link label for setting the field value to FALSE');

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
