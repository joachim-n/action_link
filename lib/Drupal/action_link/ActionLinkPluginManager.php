<?php

/**
 * @file
 * Contains \Drupal\action_link\ActionLinkPluginManager.
 */

namespace Drupal\action_link;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages image effect plugins.
 */
class ActionLinkPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, LanguageManager $language_manager, ModuleHandlerInterface $module_handler) {
    //dpm(func_get_args());
    parent::__construct('Plugin/ActionLink', $namespaces);
    // No idea. these crash now.
    $this->alterInfo($module_handler, 'action_link_info');
    $this->setCacheBackend($cache_backend, $language_manager, 'action_link_info');
  }

}
