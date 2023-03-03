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
 * @see TODO.
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

      // // Only act for EntityFieldStateActionBase.
      // if (!$action_link_state_action_plugin instanceof EntityFieldStateActionBase) {
      //   continue;
      // }

      if (empty($action_link_state_action_plugin->getConfiguration()['entity_type_id'])) {
        throw new PluginException("Missing entity type on $action_link_entity_id");
      }
      // dump($action_link_state_action_plugin->getConfiguration()['entity_type_id']);

      // dump($action_link_state_action_plugin);

      $this->derivatives[$action_link_entity_id] = [
        // NOT,
        // $action_link_state_action_plugin->getPluginDefinition()['label']
        // NO, remove suffix???
        // REALLY need admin label for manage display page!!
        'label' => $action_link_entity->label() . ' ' . t('action link'),
        'attach' => [
          'field_name' => "action_link_{$action_link_entity_id}",
          'scope' => 'base', // TODO, match the action link's field.
          'entity_types' => [
            // TODO bundles if bundle scope.
            $action_link_state_action_plugin->getConfiguration()['entity_type_id'] => [],
          ],
        ],
      ] + $base_plugin_definition;
    }
    // dump($this->derivatives);

    return $this->derivatives;
  }

}
