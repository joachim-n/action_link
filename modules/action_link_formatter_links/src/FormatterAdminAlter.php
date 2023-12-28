<?php

namespace Drupal\action_link_formatter_links;

use Drupal\action_link\Plugin\StateAction\EntityFieldStateActionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides delegate implementations of admin hooks.
 */
class FormatterAdminAlter {

  /**
   * Static cache of action links that target fields.
   *
   * @var array
   */
  protected $cache = [];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a FormatterAdminAlter instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Helper for hook_field_formatter_third_party_settings_form().
   *
   * Same parameters.
   */
  public function formatterSettingsForm(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, $view_mode, array $form, FormStateInterface $form_state) {
    $element = [];

    $action_link_entities = $this->getActionLinksForField($field_definition);

    if (!$action_link_entities) {
      return $element;
    }

    $options = [];
    foreach ($action_link_entities as $action_link) {
      $options[$action_link->id()] = $action_link->label();
    }

    $element['action_links'] = [
      '#type' => 'checkboxes',
      '#title' => t('Action links'),
      '#options' => $options,
      '#default_value' => $plugin->getThirdPartySetting('action_link_formatter_links', 'action_links', []),
    ];

    return $element;
  }

  /**
   * Gets the action links that control the given field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An array of action link entities, keyed by the action link ID.
   */
  protected function getActionLinksForField(FieldDefinitionInterface $field_definition): array {
    $target_entity_type_id = $field_definition->getTargetEntityTypeId();

    $action_link_entities = $this->getActionLinksForEntityType($target_entity_type_id);

    $relevant_action_links = [];
    foreach ($action_link_entities as $action_link) {
      $state_action_plugin = $action_link->getStateActionPlugin();

      // We know this is an EntityFieldStateActionBase.
      if ($state_action_plugin->getTargetFieldName() != $field_definition->getName()) {
        continue;
      }

      $relevant_action_links[$action_link->id()] = $action_link;
    }

    return $relevant_action_links;
  }

  /**
   * Gets the action links that control a field on the given entity type.
   *
   * @param string $target_entity_type_id
   *   The entity type ID.
   *
   * @return array
   *   An array of action link entities, keyed by the action link ID.
   */
  protected function getActionLinksForEntityType(string $target_entity_type_id): array {
    // This service is called via
    // hook_field_formatter_third_party_settings_form() for every field in the
    // display, so cache the expensive work of finding all action links which
    // target fields on this entity type.
    if (!isset($this->cache[$target_entity_type_id])) {
      // Initialise the cache entry to an array, so that if there are no action
      // links, we don't come back here.
      $this->cache[$target_entity_type_id] = [];

      $action_link_entities = $this->entityTypeManager->getStorage('action_link')->loadMultiple();
      foreach ($action_link_entities as $action_link) {
        $state_action_plugin = $action_link->getStateActionPlugin();

        // Skip the action link if the plugin is not an entity field plugin.
        if (!is_subclass_of($state_action_plugin, EntityFieldStateActionBase::class)) {
          continue;
        }

        // Skip the action link if the plugin has dynamic parameters in addition
        // to the entity, as we won't know how to render them within the
        // formatter.
        if (count($state_action_plugin->getDynamicParameterNames()) != 1) {
          continue;
        }

        // dump($action_link->id());
        if ($state_action_plugin->getTargetEntityTypeId() != $target_entity_type_id) {
          continue;
        }

        $this->cache[$target_entity_type_id][$action_link->id()] = $action_link;
      }
    }

    return $this->cache[$target_entity_type_id];
  }

}
