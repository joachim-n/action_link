<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Base class for State Action plugins that operate on an entity field.
 *
 * This expects an 'entity' dynamic parameter.
 */
abstract class EntityFieldStateActionBase extends StateActionBase implements ConfigurableInterface, PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type_id' => NULL,
      'field' => NULL,
    ]
    + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($element, $form_state);

    $element['entity_type_field'] = [
      '#type' => 'entity_type_field',
      '#title' => $this->t('Entity field'),
      '#field_options_filters' => [
        [static::class, 'fieldOptionsFilter'],
      ],
      '#element_validate' => [
        [static::class, 'entityFieldElementValidate'],
      ],
      '#default_value' => [
        'entity_type_id' => $element['#default_value']['entity_type_id'] ?? '',
        'field' => $element['#default_value']['field'] ?? '',
      ],
      '#required' => TRUE,
    ];

    return $element;
  }

  /**
   * Field options filter callback.
   */
  public static function fieldOptionsFilter(&$field_options, $selected_entity_type_id, $field_map_for_entity_type, $form_state) {
    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface */
    $entity_field_manager = \Drupal::service('entity_field.manager');

    // Remove computed fields.
    foreach ($field_options as $field_id => $label) {
      foreach ($field_map_for_entity_type[$field_id]['bundles'] as $bundle) {
        $field_definition = $entity_field_manager->getFieldDefinitions($selected_entity_type_id, $bundle)[$field_id];
        if ($field_definition->isComputed()) {
          unset($field_options[$field_id]);
        }
      }
    }
  }

  /**
   * Element validate callback.
   */
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
  }

  /**
   * {@inheritdoc}
   */
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
    // of the surplus 'container' nesting. Therefore we do it directly, which is
    // a hack, as this plugin shouldn't be aware of the form structure it's used
    // in.
    if (isset($values['entity_type_field']['entity_type_id'])) {
      $form_state->getCompleteFormState()->setValue(['plugin', 'plugin_configuration', 'entity_type_id'], $values['entity_type_field']['entity_type_id']);
    }
    if (isset($values['entity_type_field']['field'])) {
      $form_state->getCompleteFormState()->setValue(['plugin', 'plugin_configuration', 'field'], $values['entity_type_field']['field']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getActionRoute(ActionLinkInterface $action_link): Route {
    $route = parent::getActionRoute($action_link);

    $route->setOption('parameters', [
      'entity' => [
        'type' => 'entity:' . $this->configuration['entity_type_id'],
      ],
    ]);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateParameters(array $parameters) {
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
  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, EntityInterface $entity = NULL): AccessResult {
    // Check access both to edit the entity, and to edit the specific field.
    $entity_access = $entity->access('update', $account, TRUE);

    $field_name = $this->configuration['field'];

    // @todo Inject.
    $this->entityTypeManager = \Drupal::service('entity_type.manager');
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler($entity->getEntityTypeId());
    $field_access = $access_control_handler->fieldAccess('edit', $entity->getFieldDefinition($field_name), $account, NULL, TRUE);

    return $entity_access->andIf($field_access);
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperability(ActionLinkInterface $action_link, EntityInterface $entity = NULL): bool {
    // Fail operability if the action link's affected field is empty.
    $field_name = $this->configuration['field'];

    if ($entity->get($field_name)->isEmpty()) {
      return FALSE;
    }

    return parent::checkOperability($action_link);
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenData(EntityInterface $entity = NULL) {
    return [
      $this->configuration['entity_type_id'] => $entity,
    ];
  }

  /**
   * Gets the ID of the entity type the action link works on.
   *
   * @return string
   *   The entity type ID.
   */
  protected function getTargetEntityTypeId(): string {
    return $this->configuration['entity_type_id'];
  }

  /**
   * Gets the name of the field the action link works on.
   *
   * @return string
   *   The field name.
   */
  protected function getTargetFieldName(): string {
    return $this->configuration['field'];
  }

}
