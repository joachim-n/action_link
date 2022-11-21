<?php

namespace Drupal\action_link\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the default form handler for the Action Link entity.
 */
class ActionLinkForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\action_link\Entity\ActionLinkInterface */
    $action_link = $this->entity;
    dsm($action_link);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $action_link->label(),
      '#description' => $this->t('A short, descriptive title for this action link.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -3,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $action_link->id(),
      '#description' => $this->t('The machine-name for this action link. It may be up to 32 characters long and may only contain lowercase letters, underscores, and numbers. It will be used in URLs and in all API calls.'),
      '#weight' => -2,
      '#machine_name' => [
        'exists' => ['Drupal\action_link\Entity\ActionLink', 'load'],
        'source' => ['label'],
      ],
      '#disabled' => !$action_link->isNew(),
      '#required' => TRUE,
    ];


    $this->stateActionManager = \Drupal::service('plugin.manager.action_link_state_action');
    // $state_actions_options [];

    $form['state_action'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Action'),
      // '#description' => $this->t('Flags are usually controlled through links that allow users to toggle their behavior. You can choose how users interact with flags by changing options here. It is legitimate to have none of the following checkboxes ticked, if, for some reason, you wish <a href="@placement-url">to place the the links on the page yourself</a>.', ['@placement-url' => 'http://drupal.org/node/295383']),
      '#tree' => FALSE,
      '#prefix' => '<div id="state-action-type-settings-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['state_action']['plugin_id'] = [
      '#type' => 'radios',
      '#title' => $this->t('Link type'),
      '#options' => [],
      '#default_value' => $action_link->get('plugin_id'),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateSelectedPluginType',
        'wrapper' => 'state-action-type-settings-wrapper',
        'event' => 'change',
        'method' => 'replace',
      ],
    ];

    foreach ($this->stateActionManager->getDefinitions() as $plugin_id => $definition) {
      $form['state_action']['plugin_id']['#options'][$plugin_id] = $definition['label'];
      $form['state_action']['plugin_id'][$plugin_id]['#description'] = $definition['description'];
    }

    $form['state_action']['plugin_id_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update'),
      // '#submit' => ['::submitSelectPlugin'],  TODO WTF
      '#weight' => 20,
      '#attributes' => ['class' => ['js-hide']],
    ];

    // $form['display']['settings'] = [
    //   '#type' => 'container',
    //   '#weight' => 21,
    // ];

    dsm($form_state->getValues());
    $form['state_action']['plugin_config'] = [
      '#parents' => ['plugin_config'],
      '#tree' => TRUE,
    ];
    if (!$action_link->isNew()) {
      $state_action_plugin = $action_link->getStateActionPlugin();
      $form['state_action']['plugin_config'] = $state_action_plugin->buildConfigurationForm($form['state_action']['plugin_config'], $form_state);
    }


    return $form;
  }

  /**
   * Ajax callback: switches the configuration type selector.
   */
  public function updateSelectedPluginType($form, FormStateInterface $form_state) {
    return $form['state_action'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    dsm($form_state->getValues());

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    // $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $saved;
  }

}
