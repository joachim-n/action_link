<?php

namespace Drupal\action_link\Entity\Handler;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * Provides the storage handler for the Action Link entity.
 */
class ActionLinkStorage extends ConfigEntityStorage {

  /**
   * Loads action link entities that are configured to use the output plugin.
   *
   * @param string $output_plugin_id
   *   An output plugin ID.
   *
   * @return array
   *   An array of all action link entities that are configured to output using
   *   the given output plugin.
   */
  public function loadByUsingOutput(string $output_plugin_id): array {
    // @todo Optimise this, ideally using lookup_keys.
    $action_links = $this->loadMultiple();
    return array_filter($action_links, fn ($action_link) => isset($action_link->get('output')[$output_plugin_id]));
  }

}
