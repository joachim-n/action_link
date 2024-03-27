<?php

namespace Drupal\action_link\Plugin\StateAction;

/**
 * Interface for action link plugins which target an entity.
 */
interface EntityActionLinkInterface {

  /**
   * Gets the ID of the entity type the action link works on.
   *
   * @return string
   *   The entity type ID.
   */
  public function getTargetEntityTypeId(): string;

}