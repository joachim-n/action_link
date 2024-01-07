<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for Action Link Style plugins.
 *
 * An action link style plugin determines how the links for an action link
 * entity will behave in the UI.
 *
 * For example, a link could be a plain HTML link which reloads the current
 * page, or a JavaScript link which receives an AJAX response.
 *
 * An action link's link style setting affects how links are output, but does
 * not restrict which link styles are responded to. This means that any link
 * style URL will work for an action link entity. This is to allow for graceful
 * degradation of JavaScript links.
 */
interface ActionLinkStyleInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Alters the render array for an action link entity's set of links.
   *
   * This is only called if the render array has at least one link.
   *
   * @param array &$build
   *   The render array, passed by reference. The keys are direction names.
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user account the links are for.
   * @param array $named_parameters
   *   The aditional dynamic parameters specific to the action link's state
   *   action plugin, as upcasted objects. Keys are the parameter names.
   * @param array $scalar_parameters
   *   The raw values of the dynamic parameters.
   */
  public function alterLinksBuild(array &$build, ActionLinkInterface $action_link, AccountInterface $user, array $named_parameters, array $scalar_parameters);

  /**
   * Handle the request for an action link.
   *
   * This is called by the action link controller. It is only called if the
   * user has access to the route, but is called both if the action link is
   * operable and if it is not.
   *
   * Action link style plugins should produce feedback which either announces
   * success or that the action could not be carried out. The status of this is
   * given by the $action_completed parameter.
   *
   * An action not being operable happens typically when the link is out of date
   * and the site no longer in the state that the link's parameters assume.
   * Unlike a denial of access where we fail silently, the user should be shown
   * helpful feedback to explain why the link is not doing what they expect.
   *
   * @param bool $action_completed
   *   Whether the action could be completed. If FALSE, this means that the
   *   action wasn't operable or the target state wasn't reachable.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction of the link.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param mixed ...$parameters
   *   Additional parameters specific to the action link plugin.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   */
  public function handleActionRequest(bool $action_completed, Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, ...$parameters): Response;

}
