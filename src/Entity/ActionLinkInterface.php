<?php

namespace Drupal\action_link\Entity;

use Drupal\action_link\Plugin\StateAction\StateActionInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for Action Link entities.
 */
interface ActionLinkInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  public function getStateActionPlugin(): StateActionInterface;

  /**
   * Gets a link for the action link.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the link for.
   * @param mixed ...$parameters
   *   The parameters for the link. These are specific to the state action
   *   plugin.
   *
   * @return \Drupal\Core\Link
   *   A link object, or NULL if there is no valid link for the given
   *   parameters.
   */
  // public function getLink(AccountInterface $user, ...$parameters): ?Link;

}
