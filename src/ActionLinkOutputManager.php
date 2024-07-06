<?php

namespace Drupal\action_link;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\action_link\Attribute\ActionLinkOutput;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\ActionLinkOutput\ActionLinkOutputInterface;

/**
 * Manages discovery and instantiation of Action Link Output plugins.
 */
class ActionLinkOutputManager extends DefaultPluginManager {

  /**
   * Constructs a new ActionLinkOutputManagerManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/ActionLinkOutput',
      $namespaces,
      $module_handler,
      ActionLinkOutputInterface::class,
      ActionLinkOutput::class
    );

    $this->alterInfo('action_link_output_info');
    $this->setCacheBackend($cache_backend, 'action_link_output_plugins');
  }

  /**
   * Gets the definitions of plugins which apply to an action link.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   *
   * @return array
   *   An array of the plugin definitions which apply to the given action link
   *   entity. Keys are plugin IDs.
   */
  public function getApplicableDefinitions(ActionLinkInterface $action_link): array {
    // Can't check for output plugins if the action link has no state action
    // plugin yet.
    if (empty($action_link->getStateActionPlugin())) {
      return [];
    }

    return array_filter(
      $this->getDefinitions(),
      fn ($definition) => $definition['class']::applies($action_link)
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getType() {
    return 'action_link_output';
  }

}
