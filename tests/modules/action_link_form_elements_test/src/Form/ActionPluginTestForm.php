<?php

namespace Drupal\action_link_form_elements_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Test form for the action_plugin form element.
 */
class ActionPluginTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'action_link_form_elements_test_action_plugin_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['action_link'] = [
      '#type' => 'action_plugin',
      '#title' => $this->t('Select a plugin'),
      '#description' => $this->t('Enter a description'),
      '#default_value' => [
        'plugin_id' => \Drupal::state()->get('action_link_form_elements_test:ActionPluginTestForm:plugin_id', ''),
        'plugin_configuration' => \Drupal::state()->get('action_link_form_elements_test:ActionPluginTestForm:plugin_configuration', []),
      ],
      '#required' => TRUE,
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
    $this->messenger()->addMessage($this->t("You selected the @action-link action link plugin.", [
      '@action-link' => $form_state->getValue(['action_link', 'plugin_id']),
    ]));

    \Drupal::state()->set('action_link_form_elements_test:ActionPluginTestForm:plugin_id', $form_state->getValue(['action_link', 'plugin_id']));
    \Drupal::state()->set('action_link_form_elements_test:ActionPluginTestForm:plugin_configuration', $form_state->getValue(['action_link', 'plugin_configuration']));
  }

}
