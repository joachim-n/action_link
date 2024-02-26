<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Interface for State Action plugins.
 *
 * State action plugins define what actually happens when a user clicks an
 * action link.
 *
 * This is largely internal with respect to callers; in other words, you should
 * probably only call methods on this plugin if you are extending the module in
 * some way. In general, there are corresponding methods on the entity class
 * that should be used instead.
 *
 * State action plugins that are configurable should also implement:
 *  - \Drupal\Core\Plugin\PluginFormInterface
 *  - \Drupal\Component\Plugin\ConfigurableInterface.
 */
interface StateActionInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Gets a linkset of all the reachable directions for the user.
   *
   * This render array does not have a lazy builder and is therefore
   * uncacheable. In general, you should instead call buildLinkSet() on an
   * action link.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get links for.
   * @param array $scalar_parameters
   *   (optional) The scalar values of the dynamic parameters for the state
   *   action plugin, keyed by the parameter names.
   * @param array $parameters
   *   (optional) The upcasted values of the dynamic parameters for the state
   *   action plugin, keyed by the parameter names.
   *
   * @return array
   *   A render array of links. This may be empty if no links are available.
   *
   * @throws \ArgumentCountError
   *   Throws an error if the $parameter don't match up with the dynamic
   *   parameters defined by this plugin.
   */
  public function buildLinkSet(ActionLinkInterface $action_link, AccountInterface $user, array $scalar_parameters = [], array $parameters = []): array;

  /**
   * Gets the link for a specific direction.
   *
   * This render array does not have a lazy builder and is therefore
   * uncacheable. In general, you should instead call buildLinkSet() on an
   * action link.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction of the link.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the link for.
   * @param array $scalar_parameters
   *   (optional) The scalar values of the dynamic parameters for the state
   *   action plugin, keyed by the parameter names.
   * @param array $parameters
   *   (optional) The upcasted values of the dynamic parameters for the state
   *   action plugin, keyed by the parameter names.
   *
   * @return \Drupal\Core\Link|null
   *   The link object, or NULL if no link is applicable.
   */
  public function buildSingleLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user, array $scalar_parameters = [], array $parameters = []): array;

  /**
   * Gets a plain render array of all the reachable directions for the user.
   *
   * This render array does not have a lazy builder and is therefore
   * uncacheable. In general, you should instead call buildLinkSet() on an
   * action link.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get links for.
   * @param array $scalar_parameters
   *   (optional) The scalar values of the dynamic parameters for the state
   *   action plugin, keyed by the parameter names.
   * @param array $parameters
   *   (optional) The upcasted values of the dynamic parameters for the state
   *   action plugin, keyed by the parameter names.
   *
   * @return array
   *   A render array of links. This may be empty if no links are available.
   *
   * @throws \ArgumentCountError
   *   Throws an error if the $parameter don't match up with the dynamic
   *   parameters defined by this plugin.
   */
  public function buildLinkArray(ActionLinkInterface $action_link, AccountInterface $user, array $scalar_parameters = [], array $parameters = []): array;

  /**
   * Gets the next state for the given parameters, or NULL if there is none.
   *
   * Subclasses will add parameters to this.
   *
   * @param string $direction
   *   The direction.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the direction for.
   *
   * @return string|null
   *   The name of the next state in the given direction for the action. If
   *   there is no valid state, NULL is returned. The return value should be
   *   checked against is_null() rather than empty(), as a value such as '0' can
   *   be a valid state name.
   */
  public function getNextStateName(string $direction, AccountInterface $user): ?string;

  /**
   * Advance to the given state.
   *
   * This should made the necessary changes to put the operand into the given
   * state. The state has already been checked for operability and access.
   *
   * This method is responsible for clearing caches as necessary.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to perform the action. This is not necessarily the current user.
   * @param string $state
   *   The state to advance to.
   * @param ...
   *   Dynamic parameters specific to the action link's state action plugin.
   */
  public function advanceState(AccountInterface $account, string $state);

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
   *   A numeric array of parameters, in the same order that they are defined in
   *   the plugin annotation. Set route options in self::getActionRoute() to
   *   have parameters upcasted by the routing system.
   */
  public function getDynamicParametersFromRouteMatch(RouteMatchInterface $route_match): array;

  /**
   * Checks whether the action is logically possible in any direction.
   *
   * This should not check any kind of user access, or check the state, it is
   * merely about whether the general state of the site makes the action
   * logically possible.
   *
   * For example:
   *  - The action is to increment a numeric field on an entity, but the field
   *    value is empty: the operability is FALSE because a NULL value can't be
   *    incremented.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param mixed ...
   *   Additional dynamic parameters.
   *
   * @return bool
   *   TRUE if the link is operable, FALSE if not.
   */
  public function checkOperability(ActionLinkInterface $action_link): bool;

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
   * The permission access is ORed with the main 'use ID action links'
   * permission.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\Core\Session\AccountInterface $account
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
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to perform the action. This is not necessarily the current user.
   * @param mixed ...
   *   Additional dynamic parameters.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @see \Drupal\action_link\Entity\ActionLinkInterface::checkAccess()
   * @see self::checkPermissionAccess()
   */
  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account): AccessResult;

  /**
   * Gets the names of the plugin's dynamic parameters.
   *
   * @return array
   *   An array of names.
   */
  public function getDynamicParameterNames(): array;

  /**
   * Gets the directions for this plugin.
   *
   * @return array
   *   An array of the directions defined in the plugin definition. Keys are
   *   direction machine names, and values are the labels.
   */
  public function getDirections(): array;

  /**
   * Gets the states for this plugin, if they are finite and defined.
   *
   * Plugins do not need to define states. This is the case for example if there
   * are an infinite number, such as for a plugin which increments and
   * decrements a numeric field value.
   *
   * This method does not return state labels, because plugins which do not
   * define states can still use static::getStateLabel() to return labels for
   * them. For example, a plugin which adds products to a shopping cart has
   * infinite states but might implement static::getStateLabel() to return a
   * label of 'Empty' for the 0 state.
   *
   * @return array
   *   A numeric array of the state machine names.
   */
  public function getStates(): array;

  /**
   * Gets the label for a state.
   *
   * @param string $state
   *   The state machine name.
   *
   * @return string
   *   A human-readable representation of the state. By default, this is
   *   identical to the machine name.
   */
  public function getStateLabel(string $state): string;

  /**
   * Gets the label for a link.
   *
   * This may contain tokens, which will be replaced by the action link entity.
   *
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param mixed ...$parameters
   *   The dynamic parameters.
   *
   * @return string
   *   The label.
   *
   * @see \Drupal\action_link\Entity\ActionLinkInterface::getLinkLabel()
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string;

  /**
   * Gets the message to show the user after an action is complete.
   *
   * This does not perform token replacement. Call the same method on the
   * action link entity to get tokens replaced.
   *
   * @param string $direction
   *   The direction for the action.
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
   * Gets the message to show the user when an action cannot be completed.
   *
   * This does not perform token replacement. Call the same method on the
   * action link entity to get tokens replaced.
   *
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The state that has been arrived at.
   * @param mixed ...$parameters
   *   The parameters.
   *
   * @return string
   *   The message string. If this is empty then no message should be shown.
   */
  public function getFailureMessage(string $direction, string $state, ...$parameters): string;

  /**
   * Gets additional token replacement data specific to this plugin.
   *
   * @param mixed ...
   *   Additional dynamic parameters.
   *
   * @return array
   *   An array of token data, in the format accepted by
   *   \Drupal\Core\Utility\Token::replace()'s $data parameter.
   */
  public function getTokenData();

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
