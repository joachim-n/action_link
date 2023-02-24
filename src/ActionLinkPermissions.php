<?php

namespace Drupal\action_link;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines permissions for each action_link entity.
 */
class ActionLinkPermissions {

  /**
   * Defines dynamic permissions for action_link entities.
   *
   * @return array
   *   An array of permissions.
   */
  public function permissions(): array {
    $permissions = [];
    // Generate permissions for each TODO
    $action_links = \Drupal::service('entity_type.manager')->getStorage('action_link')->loadMultiple();
    uasort($action_links, ConfigEntityBase::class . '::sort');

    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    foreach ($action_links as $action_link) {
      $permissions += $action_link->getPermissions();
    }

    return $permissions;
  }

}
