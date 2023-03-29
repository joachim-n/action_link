<?php

namespace Drupal\action_link;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\action_link\Annotation\StateAction;
use Drupal\action_link\Plugin\StateAction\StateActionInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;

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

  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach ($definition['dynamic_parameters'] as $parameter) {
      if (in_array($parameter, ['link_style', 'direction', 'state', 'user'])) {
        throw new InvalidPluginDefinitionException($plugin_id, sprintf('The %s parameter name is reserved.', $parameter));
      }
    }

    // @todo Further validation of definition:
    // - geometry traits need plugin to also implement form interface!
  }


}
