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
    $plugin_form = parent::buildConfigurationForm($element, $form_state);

    $plugin_form['entity_type_field']['#field_types'] = ['boolean'];

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

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // dsm($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
    // dsm($direction);
    // dump($this);
    // to get the next state name we have to look at the current value of the field
    // and do calculation on it.
    //
    // to chec if next state is operable, we have to look at... THE CURRENT VALUE OF THE FIELD!
    // for entity actions it's a bit repetitive.
    // what about non entity ones?
    // like... FLAG!
    // next state: get current flagging - is there one? -> tells you unflag/flag
    // operable: stuff to do with bundles?? does flag apply to given entity? etc.
    // which is stuff that should/could be checked BEFORE we try loading a flagging, probably!

    $field_name = $this->configuration['field'];
    // dump($this->configuration);

    $value = $entity->get($field_name)->value;




    return match ((bool) $value) {
      FALSE => 'true',
      TRUE  => 'false',
    };
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState(AccountInterface $account, string $state, array $parameters) {
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
