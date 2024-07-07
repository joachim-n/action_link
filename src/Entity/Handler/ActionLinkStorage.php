<?php

namespace Drupal\action_link\Entity\Handler;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Storage handler for the Action Link entity type.
 */
class ActionLinkStorage extends ConfigEntityStorage {

  /**
   * Loads all action link entities configured to use the given output plugin.
   *
   * @param string $output_plugin_id
   *   An action link output plugin ID.
   *
   * @return array
   *   An array of all action link entities that are configured to output using
   *   the given output plugin.
   */
  public function loadByUsingOutput(string $output_plugin_id): array {
    // @todo Optimise this with caching. The lookup_keys property won't work
    // as the value we are interested in is nested.
    $action_links = $this->loadMultiple();
    return array_filter($action_links, fn ($action_link) => isset($action_link->get('output')[$output_plugin_id]));
  }

}
