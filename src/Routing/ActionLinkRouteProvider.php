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

    $base_path = '/action-link/{action_link}/{direction}/{state}/{user}';

    $action_link_entities = $this->entityTypeManager->getStorage('action_link')->loadMultiple();
    foreach ($action_link_entities as $action_link_id => $action_link_entity) {
      /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link_entity */
      $state_action_plugin = $action_link_entity->getStateActionPlugin();

      $routes['action_link.action_link.' . $action_link_id] = $state_action_plugin->getActionRoute();
      continue;

      $path = $base_path;

      $routes_method = new \ReflectionMethod($state_action_plugin, 'routeController');
      $route_method_extra_parameters = array_slice($routes_method->getParameters(), 6);

      foreach ($route_method_extra_parameters as $route_method_parameter) {
        $path .= '/{' . $route_method_parameter->getName() . '}';
      }

      $routes['action_link.action_link.' . $action_link_id] = new Route(
        $path,
        [
          '_controller' => $state_action_plugin::class . '::routeController',
          'callback_type' => 'controller',
        ],
        [
          '_custom_access'  => $state_action_plugin::class . '::routeAccess',
          // '_csrf_token' => TRUE,
        ],
        [
          // 'parameters' => [
          //   'entity' =>
          //   type: entity:foobar
          // ],
        ],
      );

      // hand over to plugin for alteration?
    }

    return $routes;
  }

}
