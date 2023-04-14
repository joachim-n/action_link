<?php

namespace Drupal\action_link\Entity;

use Drupal\action_link\Plugin\ActionLinkStyle\ActionLinkStyleInterface;
use Drupal\action_link\Plugin\StateAction\StateActionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface for Action Link entities.
 */
interface ActionLinkInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets a render array of all the reachable directions for the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get links for.
   * @param mixed ...$parameters
   *   Dynamic parameters specific to the action link's state action plugin.
   *
   * @return array
   *   A render array of links. This may be empty if no links are available.
   *
   * @throws \ArgumentCountError
   *   Throws an error if the $parameter don't match up with the dynamic
   *   parameters defined by this entity's state action plugin.
   */
  public function buildLinkSet(AccountInterface $user, ...$parameters);

  /**
   * Gets a render array of the given direction for the user.
   *
   * @param string $direction
   *   The direction to get the link for.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get links for.
   * @param mixed ...$parameters
   *   Dynamic parameters specific to the action link's state action plugin.
   *
   * @return array
   *   A render array of links. This may be empty if no links are available.
   *
   * @throws \ArgumentCountError
   *   Throws an error if the $parameter don't match up with the dynamic
   *   parameters defined by this entity's state action plugin.
   */
  public function buildSingleLink(string $direction, AccountInterface $user, ...$parameters): array;

  /**
   * Gets the label for a link, with tokens replaced.
   *
   * @param string $direction
   *   The direction of the link.
   * @param string $state
   *   The target state of the link.
   * @param mixed ...$parameters
   *   Dynamic parameters specific to the action link's state action plugin.
   *
   * @return string
   *   The link label.
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string;

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
   * Advances the state of the action link.
   *
   * This performs no access or logic checks at all, and so must only be called
   * once access, operability, and reachability have been checked.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to perform the action. This is not necessarily the current user.
   * @param string $state
   *   The state to advance to.
   * @param ...$parameters
   *   Dynamic parameters specific to this action link's state action plugin.
   */
  public function advanceState(AccountInterface $account, string $state, ...$parameters): void;

  /**
   * Gets the message to show the user when an action state change is completed.
   *
   * @param string $direction
   *   The direction of the action.
   * @param string $state
   *   The state that has been reached.
   * @param mixed ...$parameters
   *   Dynamic parameters specific to the action link's state action plugin.
   *
   * @return string
   *   The message.
   */
  public function getMessage(string $direction, string $state, ...$parameters): string;

  /**
   * Gets the message to show the user when an action state change has failed.
   *
   * This is shown when the failure is due to the action link not being operable
   * or the state not being reachable. It is not shown for an access failure.
   *
   * @param string $direction
   *   The direction of the action.
   * @param string $state
   *   The state that was the target of the action. Because the action state
   *   change has failed, this is not the current state.
   * @param mixed ...$parameters
   *   Dynamic parameters specific to the action link's state action plugin.
   *
   * @return string
   *   The message.
   */
  public function getFailureMessage(string $direction, string $state, ...$parameters): string;

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
