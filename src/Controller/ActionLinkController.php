<?php

namespace Drupal\action_link\Controller;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * TODO: class docs.
 */
class ActionLinkController {

  use StringTranslationTrait;

  /**
   * Callback for the action_link.action_link route.
   */
  public function action(UserInterface $user, ActionLinkInterface $action_link, string $state, string $parameters) {
    $parameters = explode('/', $parameters);

    dsm($parameters);

    // TODO: operability - fail silently?


    $action_link->advanceState($user, $state, ...$parameters);

    // dsm($parameters);

    // REdirect?! if no JS.
    // magic if JS?

    return [];
  }

  /**
   * Checks access for the action_link.action_link route.
   */
  public function access(UserInterface $user, ActionLinkInterface $action_link, string $state, string $parameters): AccessResultInterface {
    // 1. validate token

    // 2. validate $parameters, state, user with the plugin



    // dsm($parameters);
    // TODO.
    return AccessResult::allowed();
  }

}
