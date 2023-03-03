<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;

/**
 * Base class for State Action plugins that operate on an entity field.
 *
 * This expects an 'entity' dynamic parameter.
 */
abstract class EntityFieldStateActionBase extends StateActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type_id' => NULL,
      'field' => NULL,
    ];
  }

  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $plugin_form = [];

    $plugin_form['entity_type_field'] = [
      '#type' => 'entity_type_field',
      '#title' => $this->t('Entity field'),
      '#element_validate' => [
        [static::class, 'entityFieldElementValidate'],
      ],
      '#default_value' => [
        'entity_type_id' => $element['#default_value']['entity_type_id'] ?? '',
        'field' => $element['#default_value']['field'] ?? '',
      ],
      '#required' => TRUE,
    ];

    return $plugin_form;
  }

  public static function entityFieldElementValidate(&$element, FormStateInterface $form_state, &$complete_form) {
    $element_parents = $element['#parents'];
    $plugin_form_parents = $element_parents;
    array_pop($plugin_form_parents);

    $element_value = $form_state->getValue($element_parents);

    if ($element_value) {
      // This doesn't work, because it goes into the 'container' level for the
      // plugin form element, and that copies that up one level, but the
      // copying happens later. ARGH.
      // $form_state->setValue([...$plugin_form_parents, 'entity_type_id'], $element_value['entity_type_id']);
      // $form_state->setValue([...$plugin_form_parents, 'field'], $element_value['field']);
    }

    // // ARGH hardcoded array structure :(
    // // Can't get this from slicing up $element['#parents'] because of the
    // // 'container' from the plugin form element.
    // $plugin_configuration_values = $form_state->getValue(['plugin', 'plugin_configuration']);

    // $merged_values = $plugin_configuration_values + $element_value;

    // $form_state->setValue(['plugin', 'plugin_configuration'], $merged_values);
  }


  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // IMPORTANT: It is essential that child classes that override this
    // implementation call it with parent::, as it handles copying values from
    // the 'entity_type_field' form element into the right place.
    $values = $form_state->getValues();

    // @todo Setting values on the subform state, which is the correct way,
    // doesn't work because SubformState sees that we are in the 'container'
    // element in the 'action_plugin' form element, and so the values get set
    // there. That apparently happens later than the 'action_plugin' form
    // element's valueCallback() setting the form values one level up to get rid
    // of the surplus 'container' nesting.
    // $form_state->setValue(['entity_type_id'], $values['entity_type_field']['entity_type_id']);
    // $form_state->setValue(['field'], $values['entity_type_field']['field']);
    // Therefore we do it directly, which is a hack, as this plugin shouldn't
    // be aware of the form structure it's used in.
    $form_state->getCompleteFormState()->setValue(['plugin', 'plugin_configuration', 'entity_type_id'], $values['entity_type_field']['entity_type_id']);
    $form_state->getCompleteFormState()->setValue(['plugin', 'plugin_configuration', 'field'], $values['entity_type_field']['field']);
  }

  /*
 get links:
  - get directions
    - is there a next state? AHA!




  */

  public function getActionRoute(ActionLinkInterface $action_link): Route {
    $route = parent::getActionRoute($action_link);

    $route->setOption('parameters', [
      'entity' => [
        'type' => 'entity:node', // TODO!!!
      ],
    ]);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function validateParameters(array $parameters) {
    parent::validateParameters($parameters);

    if ($parameters['entity']->getEntityTypeId() != $this->configuration['entity_type_id']) {
      throw new \ArgumentCountError(sprintf("Wrong entity type for state action plugin %s, expects %s, got %s",
        $this->getPluginId(),
        $this->configuration['entity_type_id'],
        $parameters['entity']->getEntityTypeId(),
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertParametersForRoute(array $parameters): array {
    $parameters = parent::convertParametersForRoute($parameters);

    // Convert the entity parameter to an entity ID.
    // TODO: this needs to be able to complain if a param is bad.
    // e.g. no node exists.
    $parameters['entity'] = $parameters['entity']->id();

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    // Check access both to edit the entity, and to edit the specific field.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    list($entity) = $parameters;

    $entity_access = $entity->access('update', $account, TRUE);

    $field_name = $this->configuration['field'];

    // TODO: inject.
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity->getEntityTypeId());
    $field_access = $access_control_handler->fieldAccess('edit', $entity->getFieldDefinition($field_name), $account, NULL, TRUE);

    return $entity_access->andIf($field_access);
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperability(ActionLinkInterface $action_link, ...$parameters): bool {
    // Fail operability if the action link's affected field is empty.
    list($entity) = $parameters;
    $field_name = $this->configuration['field'];

    if ($entity->get($field_name)->isEmpty()) {
      return FALSE;
    }

    return parent::checkOperability($action_link, ...$parameters);
  }

}