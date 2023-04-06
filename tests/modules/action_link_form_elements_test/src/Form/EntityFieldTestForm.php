<?php

namespace Drupal\action_link_form_elements_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Test form for the entity_type_field form element.
 */
class EntityFieldTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'action_link_form_elements_test_entity_field_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['entity_type_field'] = [
      '#type' => 'entity_type_field',
      '#title' => $this->t('Entity field'),
      '#default_value' => [
        'entity_type_id' => \Drupal::state()->get('action_link_form_elements_test:EntityFieldTestForm:entity_type_id', ''),
        'field' => \Drupal::state()->get('action_link_form_elements_test:EntityFieldTestForm:field', ''),
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->messenger()->addMessage($this->t("You selected '@entity-type' field '@field'.", [
      '@entity-type' => $form_state->getValue(['entity_type_field', 'entity_type_id']),
      '@field' => $form_state->getValue(['entity_type_field', 'field']),
    ]));

    \Drupal::state()->set('action_link_form_elements_test:EntityFieldTestForm:entity_type_id', $form_state->getValue(['entity_type_field', 'entity_type_id']));
    \Drupal::state()->set('action_link_form_elements_test:EntityFieldTestForm:field', $form_state->getValue(['entity_type_field', 'field']));
  }

}
