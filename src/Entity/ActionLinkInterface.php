<?php

namespace Drupal\action_link\Entity;

use Drupal\action_link\Plugin\ActionLinkStyle\ActionLinkStyleInterface;
use Drupal\action_link\Plugin\StateAction\StateActionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for Action Link entities.
 */
interface ActionLinkInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  public function buildLinkSet(AccountInterface $user, ...$parameters);

  /**
   * Verifies that a target state is the next state.
   *
   * This determines wheter the action makes logical sense for the current state
   * of the site, regardless of the current user's access.
   *
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param ...$parameters
   *   Dynamic parameters specific to this action link's state action plugin.
   *   These are upcasted values.
   *
   * @return bool
   *   TRUE if the target state is valid, FALSE if it is not.
   */
  public function checkReachable(string $direction, string $state, AccountInterface $account, ...$parameters): bool;

  /**
   * Checks access to use a link.
   *
   * Access to an action link involves checking several different things:
   *  - Main permission: Every action link entity exposes a permission to use
   *    it.
   *  - Specific permissions: Action link plugins can expose more granular
   *    permissions, for example, based on directions or states.
   *  - Operand access: The aspect of the site that the action link will change
   *    may also have its own permissions.
   *
   * The access results for these are combined as follows:
   *   ( main permission OR specific permission ) AND operand permission
   *
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param ...$parameters
   *   Dynamic parameters specific to this action link's state action plugin.
   *   These are upcasted values.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult;

  /**
   * Gets the state action plugin for this entity.
   *
   * @return \Drupal\action_link\Plugin\StateAction\StateActionInterface
   *   The configured plugin.
   */
  public function getStateActionPlugin(): StateActionInterface;

  /**
   * Gets the link style plugin for this entity.
   *
   * @return \Drupal\action_link\Plugin\ActionLinkStyle\ActionLinkStyleInterface
   *   The plugin.
   */
  public function getLinkStylePlugin(): ActionLinkStyleInterface;

  /**
   * Defines permissions for the action link.
   *
   * @return array
   *   An array of permissions, in the same format as that returned by a
   *   permissions provider.
   */
  public function getPermissions(): array;

}
