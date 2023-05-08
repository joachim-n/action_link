<?php

namespace Drupal\action_link;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\action_link\Annotation\ActionLinkStyle;
use Drupal\action_link\Plugin\ActionLinkStyle\ActionLinkStyleInterface;

/**
 * Manages discovery and instantiation of Action Link Style plugins.
 */
class ActionLinkStyleManager extends DefaultPluginManager {

  /**
   * Constructs a new ActionLinkStyleManagerManager.
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
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct(
      'Plugin/ActionLinkStyle',
      $namespaces,
      $module_handler,
      ActionLinkStyleInterface::class,
      ActionLinkStyle::class
    );

    $this->alterInfo('action_link_style_info');
    $this->setCacheBackend($cache_backend, 'action_link_style_plugins');
  }

}
