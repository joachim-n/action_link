<?php

namespace Drupal\action_link\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class ActionLinkRouteProvider {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a RouteProvider instance.
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
   * Returns an array of routes.
   */
  public function routes(): array {
    $routes = [];

    $action_link_entities = $this->entityTypeManager->getStorage('action_link')->loadMultiple();
    foreach ($action_link_entities as $action_link_id => $action_link_entity) {

      /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link_entity */
      $state_action_plugin = $action_link_entity->getStateActionPlugin();

      $routes['action_link.action_link.' . $action_link_id] = $state_action_plugin->getActionRoute($action_link_entity);
    }

    return $routes;
  }

}
