<?php

namespace Drupal\action_link_field\Plugin\ComputedField;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\action_link\ActionLinkStyleManager;
use Drupal\computed_field_plugin\Plugin\ComputedFieldBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TODO: class docs.
 *
 * @ComputedField(
 *   id = "action_link_field_action_link",
 *   label = @Translation("Action Link"),
 *   type = "TODO: replace this with a value",
 *   entity_types = {
 *     "TODO" = "array values",
 *   },
 *   bundles = {
 *     "TODO" = "array values",
 *   },
 * )
 */
class ActionLink extends ComputedFieldBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The action link style manager.
   *
   * @var \Drupal\action_link\ActionLinkStyleManager
   */
  protected $actionLinkStyleManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.action_link_action_link_style'),
    );
  }

  /**
   * Creates a ActionLink instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\action_link\ActionLinkStyleManager $action_link_style_manager
   *   The action link style manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ActionLinkStyleManager $action_link_style_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->actionLinkStyleManager = $action_link_style_manager;
  }

}
