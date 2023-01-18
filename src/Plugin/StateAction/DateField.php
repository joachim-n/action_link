<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * State action for incrementing or decrementing a date field on an entity.
 *
 * @StateAction(
 *   id = "date_field",
 *   label = @Translation("Date field"),
 *   description = @Translation("Date field TODO"),
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
 *     "dec",
 *     "inc",
 *   },
 * )
 */
class DateField extends EntityStateActionBase {

  use RepeatableTrait;

  use StringTranslationTrait;

  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $plugin_form = parent::buildConfigurationForm($element, $form_state);

    $plugin_form['entity_type_field']['#field_types'] = ['datetime'];

    $plugin_form['step'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Step interval'),
      '#description' => $this->t('The amount of time to change the date by, as a PHP DateInterval string.'),
      '#required' => TRUE,
      // TODO: validation.
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

    // Check the field has a value.
    // TODO all entity plugins need this!!!
    if ($entity->get($field_name)->isEmpty()) {
      return NULL;
    }

    $date_interval = new \DateInterval($this->configuration['step']);

    $date = $entity->get($field_name)->date;
    $date_clone = clone($date);
    $next_date = match($direction) {
      'inc' => $date_clone->add($date_interval),
      'dec' => $date_clone->sub($date_interval),
    };

    $next_value = $next_date->format(\DateTimeInterface::W3C);

    return $next_value;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    return AccessResult::allowed();
  }

  public function advanceState($account, $state, $parameters) {
    list($entity) = $parameters;

    // TODO:
    // dump($state);
    $date = new \DateTime($state);
    $value = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $field_name = $this->configuration['field'];

    $entity->set($field_name, $value);
    $entity->save();
  }

}
