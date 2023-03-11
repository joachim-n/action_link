<?php

namespace Drupal\action_link\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
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

    // Workaround for https://www.drupal.org/project/drupal/issues/2360639.
    $form_state->disableCache();

    /** @var \Drupal\action_link\Entity\ActionLinkInterface */
    $action_link = $this->entity;
    // dsm($action_link);

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

    $form['plugin'] = [
      '#type' => 'action_plugin',
      '#title' => $this->t('Action plugin'),
      '#required' => TRUE,
      '#default_value' => [
        'plugin_id' => $action_link->get('plugin_id'),
        'plugin_configuration' => $action_link->get('plugin_config'),
      ],
    ];

    $form['link_style'] = [
      '#type' => 'action_link_plugin',
      '#title' => $this->t('Link style'),
      '#required' => TRUE,
      '#default_value' => $action_link->get('link_style'),
      '#plugin_type' => 'action_link.link_style',
      '#options_element_type' => 'radios',
    ];

    return $form;
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

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    // dsm($form_state->getValue(['plugin', 'plugin_id']));
    // dsm($form_state->getValue(['plugin', 'plugin_configuration']));

    $entity->set('plugin_id', $form_state->getValue(['plugin', 'plugin_id']));
    $entity->set('plugin_config', $form_state->getValue(['plugin', 'plugin_configuration']) ?? []);

    $entity->set('link_style', $form_state->getValue(['link_style']));

    $entity->getStateActionPlugin()->copyFormValuesToEntity($entity, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $t_args = ['%name' => $this->entity->label()];
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The action link %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The action link %name has been added.', $t_args));
    }

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $status;
  }

}
