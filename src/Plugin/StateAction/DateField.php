<?php

namespace Drupal\action_link\Plugin\StateAction;

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
 *   description = @Translation("Action link to control the value of a date field."),
 *   dynamic_parameters = {
 *     "entity",
 *   },
 *   directions = {
 *     "dec" = "decrease",
 *     "inc" = "increase",
 *   },
 * )
 */
class DateField extends EntityFieldStateActionBase {

  use RepeatableGeometryTrait;

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $plugin_form = parent::buildConfigurationForm($element, $form_state);

    $plugin_form['entity_type_field']['#field_types'] = ['datetime'];

    $plugin_form['step'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Step interval'),
      '#description' => $this->t('The amount of time to change the date by, as a <a href=":url">PHP DateInterval string</a>.', [
        ':url' => 'https://www.php.net/manual/en/dateinterval.construct.php',
      ]),
      '#required' => TRUE,
    ];

    $plugin_form['texts'] = [
      '#tree' => TRUE,
    ];
    $plugin_form['texts'] = $this->buildTextsConfigurationForm($plugin_form['texts'], $form_state);

    $plugin_form['texts']['direction']['inc']['link_label']['#title'] = $this->t('Link label for increasing the field value');
    $plugin_form['texts']['direction']['dec']['link_label']['#title'] = $this->t('Link label for decreasing the field value');

    return $plugin_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $step = $form_state->getValue('step');

    try {
      new \DateInterval($step);
    }
    catch (\Exception $e) {
      $form_state->setError($form['step'], $this->t('The step value must be a <a href=":url">valid PHP DateInterval string</a>.', [
        ':url' => 'https://www.php.net/manual/en/dateinterval.construct.php',
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
    $field_name = $this->configuration['field'];

    $date_interval = new \DateInterval($this->configuration['step']);

    $date = $entity->get($field_name)->date;
    $date_clone = clone($date);

    // Workaround for https://www.drupal.org/project/drupal/issues/3367543:
    // force the timezone to UTC, because the date property will sometimes
    // return a date object in UTC and sometimes in the user's timezone.
    $date_clone->setTimeZone(timezone_open('UTC'));

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
  public function advanceState(AccountInterface $account, string $state, EntityInterface $entity = NULL) {
    $date = new \DateTime($state);
    $value = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $field_name = $this->configuration['field'];

    $entity->set($field_name, $value);
    $entity->save();
  }

}
