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
 * TODO: class docs.
 */
class ActionLinkController {

  use StringTranslationTrait;

  /**
   * Callback for the action_link.action_link route.
   *
   *    * !!! $user is the user passed IN THE ROUTE PARAMS, NOT CURRENT USER!
   */
  public function action(Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user) {
    $state_action_plugin = $action_link->getStateActionPlugin();



    $parameters = $state_action_plugin->getDynamicParametersFromRouteMatch($route_match);


    // array_slice($route_match->getParameters()->all(), 4);

    // dump($route_match->getParameters()->all());
    // dump($parameters);

    // TODO: validate!

    // Access is already checked, which covers whether the user is allowed to
    // use the action link on the given parameters. We now check operability,
    // which determines whether the state is valid. Unlike the access check,
    // an operability check fails without error. This is because the user could
    // simply have clicked an action link which was output before a change to
    // the system made it obsolete. For example, user A loads a page on which
    // is a 'publish node' action link. Meanwhile, user B publishes the node.
    // User A then clicks the link. This should either fail silently, or tell
    // the user the action has done nothing because the system is already in the
    // state they wish to take it to.
    $operable = $action_link->checkOperability($direction, $state, $user, ...$parameters);

    if ($operable) {
      $action_link->advanceState($user, $state, ...$parameters);

      $message = $action_link->getStateActionPlugin()->getMessage($direction, $state, ...$parameters);
      if ($message) {
        \Drupal::messenger()->addMessage($message);
      }
    }

    // TODO:
    // $this->messenger->addMessage($message);

    if ($redirect_url = $action_link->getRedirectUrl($user, ...$parameters)) {
      // Redirect, typically back to the entity. A passed in destination query
      // parameter will automatically override this.
      $response = new RedirectResponse($redirect_url->toString());
    }
    else {
      // TODO For entities that don't have a canonical URL (like paragraphs),
      // redirect to the front page.
      $redirect_url = Url::fromRoute('<front>');
      $response = new RedirectResponse($redirect_url->toString());
    }

    return $response;
  }

  /**
   * Checks access for the action_link.action_link route.
   *
   * !!! $user is the user passed IN THE ROUTE PARAMS, NOT CURRENT USER!
   *
   * $account is the account operating the route.
   */
  public function access(RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, AccountInterface $account): AccessResultInterface {
    // dump($account);
    // dsm($user);
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }


    // 2. validate $parameters, state, user with the plugin

    return AccessResult::allowed();
  }

}
