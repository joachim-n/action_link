<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;

/**
 * Interface for State Action plugins.
 */
interface StateActionInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface {

  /**
   * Gets a render array of all the operable links for the user.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get links for. TODO ARGH WANT TO ALLOW EASY DEFAULT TO MEAN CURRENT USER!
   * @param [type] ...$parameters
   *   Dynamic parameters specific to the action link's state action plugin.
   *
   * @return array
   *   A render array of links. This may be empty if no links are available.
   */
  public function buildLinkSet(ActionLinkInterface $action_link, AccountInterface $user, ...$parameters): array;

  /**
   * Gets the action link for a specific direction.
   *
   * @internal This is liable to change if I work out a way for the plugin to be
   * aware of the action_link entity. Use
   * \Drupal\action_link\Entity\ActionLinkInterface::getLinkSet() instead.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction of the link.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the link for.
   * @param ...
   *   Dynamic parameters specific to the action link's state action plugin.
   *
   * @return \Drupal\Core\Link|null
   *   The link object, or NULL if no link is applicable.
   */
  public function buildSingleLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user): array;

  public function buildConfigurationForm(array $element, FormStateInterface $form_state);

  /**
   * Gets the next state for the given parameters, or NULL if there is none.
   *
   * Subclasses will add parameters to this.
   *
   *
   *
   * @param [type] $user
   *
   * @return string|null
   *   The name of the next state for the action, !!! in the given direction if
   *   this action defines directions. If there is no valid state, NULL is
   *   returned.
   */
  public function getNextStateName(string $direction, AccountInterface $user): ?string;

  /**
   * Undocumented function
   *
   * Also responsible for clearing any caches.
   *
   * @param [type] $account
   * @param [type] $state
   * @param [type] $parameters
   */
  public function advanceState(AccountInterface $account, string $state, array $parameters);

  /**
   * Gets the dynamic parameters from the route match.
   *
   * Helper for route controller and access callbacks.
   *
   * This is needed because route callbacks in a common controller don't work
   * with variadic parameters, and the callbacks can't be on the plugin class
   * because the routing system doesn't know how to instantiate plugins.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return array
   *   An array of parameters, in the same order that they are defined in the
   *   plugin annotation. Set route options in self::getActionRoute() to have
   *   parameters upcasted by the routing system.
   */
  public function getDynamicParametersFromRouteMatch(RouteMatchInterface $route_match): array;

  /**
   * Checks whether the action is logically possible.
   *
   * This should not check any kind of user access, it is merely about whether
   * the state of the site makes the action logically possible.
   *
   * For example:
   *  - The action is to publish a node, and the node is currently published:
   *    the operability is FALSE because the node is already in the desired
   *    state.
   *  - The action is to increment a numeric field on an entity, but the field
   *    value is empty: the operability is FALSE because a NULL value can't be
   *    incremented.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param mixed ...$parameters
   *   The dynamic parameters.
   *
   * @return bool
   *   TRUE if the link is operable, FALSE if not.
   */
  public function checkOperability(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): bool;

  /**
   * Checks the user's access based on this plugin's permissions.
   *
   * This allows a plugin to check access to the permissions it defines in
   * self::getStateActionPermissions(). This allows permissions to use more
   * granular access than the main 'use ID action links'. For example, with an
   * action link which toggles the published status of an entity, a user could
   * have permission only to unpublish an entity, and not to access the link to
   * publish it.
   *
   * The permission access is ORed with the main permission.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param mixed ...$parameters
   *   The dynamic parameters.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see \Drupal\action_link\Entity\ActionLinkInterface::checkAccess()
   * @see self::checkOperandAccess()
   * @see self::getStateActionPermissions()
   */
  public function checkPermissionAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult;

  /**
   * Checks access to the action's operand: what the action wants to do.
   *
   * This checks whether the thing that the action does is accessible to the
   * user. For example, if the action changes a value on an entity, this should
   * check the user has access to edit the entity.
   *
   * This is distinct from self::checkOperability() which checks whether the
   * action on the operand is logically possible. For example, if an action
   * publishes a node, operability checks whether the node is currently
   * unpublished, and operand access checks whether the user has admin access to
   * publish the node.
   *
   * This does not need to check permissions based on action_link entities, as
   * that is covered by self::checkPermissionAccess().
   *
   * The operand access is ANDed with access based on permissions for the action
   * link entity.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $link_style
   *   The link style plugin ID.
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param mixed ...$parameters
   *   The dynamic parameters.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see \Drupal\action_link\Entity\ActionLinkInterface::checkAccess()
   * @see self::checkPermissionAccess()
   */
  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult;

  public function getLinkLabel(string $direction, string $state, ...$parameters): string;

  /**
   * Gets the message to show the user after an action is complete.
   *
   * @param string $state
   *   The state that has been arrived at.
   * @param mixed ...$parameters
   *   The parameters.
   *
   * @return string
   *   The message string. If this is empty then no message should be shown.
   */
  public function getMessage(string $direction, string $state, ...$parameters): string;

  /**
   * Defines the route for an action link entity.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   *
   * @return \Symfony\Component\Routing\Route
   *   The route for the action link. This is added to the router with the route
   *   name 'action_link.action_link.ACTION_LINK_ID'.
   *
   * @see \Drupal\action_link\Routing\ActionLinkRouteProvider
   */
  public function getActionRoute(ActionLinkInterface $action_link): Route;

  /**
   * Gets permissions for an action link using this plugin.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   *
   * @return array
   *   An array of permissions specific to this plugin. These do not need to set
   *   dependencies for the action_link entity or the plugin: those are filled
   *   in by the caller.
   *
   * @see self::checkPermissionAccess()
   */
  public function getStateActionPermissions(ActionLinkInterface $action_link): array;

}
