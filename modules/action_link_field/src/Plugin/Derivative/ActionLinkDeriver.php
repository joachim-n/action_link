<?php

namespace Drupal\action_link_field\Plugin\Derivative;

use Drupal\action_link\Plugin\StateAction\EntityFieldStateActionBase;
use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for action_link computed field plugins.
 *
 * Derived computed fields are defined as either base or bundle bundles, to
 * match the field affected by the action link.
 */
class ActionLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FieldUiLocalAction object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $action_link_entities = $this->entityTypeManager->getStorage('action_link')->loadMultiple();
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link_entity */
    foreach ($action_link_entities as $action_link_entity_id => $action_link_entity) {
      $computed_field_setting = $action_link_entity->getThirdPartySetting('action_link_field', 'computed_field', FALSE);
      if (!$computed_field_setting) {
        continue;
      }

      $action_link_state_action_plugin = $action_link_entity->getStateActionPlugin();

      if (empty($action_link_state_action_plugin->getConfiguration()['entity_type_id'])) {
        throw new PluginException("Missing entity_type_id on $action_link_entity_id");
      }
      if (empty($action_link_state_action_plugin->getConfiguration()['field'])) {
        throw new PluginException("Missing field on $action_link_entity_id");
      }

      // The entity type whose field value the action link changes.
      $host_entity_type_id = $action_link_state_action_plugin->getConfiguration()['entity_type_id'];
      // The name of the field that the action link changes.
      $field_name = $action_link_state_action_plugin->getConfiguration()['field'];

      $this->derivatives[$action_link_entity_id] = [
        'label' => $action_link_entity->label(),
        'attach' => [
          // Omit scope and bundles, which may or may not apply, as we can't
          // determine these here without circularity.
          'field_name' => "action_link_{$action_link_entity_id}",
          // Set the name of the controlled field for the plugin class to find.
          'controlled_field' => $field_name,
          // 'scope' => $scope,
          'entity_types' => [
            $host_entity_type_id => [],
          ],
        ],
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
