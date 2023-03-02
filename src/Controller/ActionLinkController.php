<?php

namespace Drupal\action_link\Controller;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for action links.
 *
 * This causes the action link to advance the state if access and operability
 * are allowed. It then hands over to the link style plugin specified in the
 * request path, which determines the response.
 */
class ActionLinkController {

  use StringTranslationTrait;

  /**
   * Callback for the action_link.action_link.* routes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
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
   */
  public function action(Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $link_style, string $direction, string $state, UserInterface $user) {
    $state_action_plugin = $action_link->getStateActionPlugin();

    $parameters = $state_action_plugin->getDynamicParametersFromRouteMatch($route_match);

    // Use the given link style rather than the one configured in the action
    // link entity. This allows for graceful degradation of JS links, and for
    // overriding the action link's configured style in theming.
    $link_style_plugin = \Drupal::service('plugin.manager.action_link_style')->createInstance($link_style);


    // array_slice($route_match->getParameters()->all(), 4);

    // dump($route_match->getParameters()->all());
    // dump($parameters);

    // TODO: validate!

    // Access is already checked, which covers whether the user is allowed to
    // use the action link on the given parameters. We now check whether the
    // state is valid. Unlike the access check, a state check check fails
    // without error. This is because the user could simply have clicked an
    // action link which was output before a change to the system made it
    // obsolete. For example, user A loads a page on which is a 'publish node'
    // action link. Meanwhile, user B publishes the node. User A then clicks the
    // link. This should either fail silently, or tell the user the action has
    // done nothing because the system is already in the state they wish to take
    // it to.
    $operable = $action_link->validateTargetState($direction, $state, $user, ...$parameters);

    if ($operable) {
      $action_link->advanceState($user, $state, ...$parameters);
    }

    return $link_style_plugin->handleActionRequest(
      $operable,
      $request,
      $route_match,
      $action_link,
      $direction,
      $state,
      $user,
      ...$parameters
    );
  }

  /**
   * Checks access for the action_link.action_link.* routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction for the action.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access for the route.
   */
  public function access(RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, AccountInterface $account): AccessResultInterface {
    $state_action_plugin = $action_link->getStateActionPlugin();

    $parameters = $state_action_plugin->getDynamicParametersFromRouteMatch($route_match);

    if ($user->id() != $account->id()) {
      // @todo Implement proxy use of action links.
      return AccessResult::forbidden();
    }

    // @todo Validate direction and parameters
    // TODO. validate $parameters, state, user with the plugin?
    // or is that done above??

    return $action_link->checkAccess($direction, $state, $user, ...$parameters);
  }

}
