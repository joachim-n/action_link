<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * TODO: class docs.
 *
 * @StateAction(
 *   id = "date_field",
 *   label = @Translation("Date field"),
 *   description = @Translation("Date field TODO"),
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
class DateField extends EntityStateActionBase {

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

    return $plugin_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName($user, EntityInterface $entity = NULL, string $direction = ''): ?string {
    $field_name = $this->configuration['field'];

    // check HAS value!

    $date_interval = new \DateInterval($this->configuration['step']);

    $date = $entity->get($field_name)->date;
    // dump($date);

    $next_date = match($direction) {
      'inc' => $date->add($date_interval),
      'dec' => $date->sub($date_interval),
    };

    $next_value = $next_date->format(\DateTimeInterface::W3C);

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
    // dump($state);
    $date = new \DateTime($state);
    $value = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $field_name = $this->configuration['field'];

    $entity->set($field_name, $value);
    $entity->save();
  }

}
