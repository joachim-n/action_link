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
   *   The entity type ID, or NULL if this is not yet set in configuration.
   */
  public function getTargetEntityTypeId(): ?string;

}