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

  public function checkOperability(string $direction, string $state, AccountInterface $account, ...$parameters): bool;

  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult;

  public function getRedirectUrl(AccountInterface $account): ?Url;

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
   */
  public function getStateActionPermissions(ActionLinkInterface $action_link): array;

}
