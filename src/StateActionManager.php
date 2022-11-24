<?php

namespace Drupal\action_link;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\action_link\Annotation\StateAction;
use Drupal\action_link\Plugin\StateAction\StateActionInterface;

/**
 * Manages discovery and instantiation of State Action plugins.
 */
class StateActionManager extends DefaultPluginManager {

  /**
   * Constructs a new StateActionManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/StateAction',
      $namespaces,
      $module_handler,
      StateActionInterface::class,
      StateAction::class
    );

    $this->alterInfo('state_action_info');
    $this->setCacheBackend($cache_backend, 'state_action_plugins');
  }

  // TODO: check that if 'directions' is defined IFF there's also a 'direction' dynamic param defined.

}
