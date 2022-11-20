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
  // todo wrong order!
  public function access(ActionLinkInterface $action_link, string $state, string $parameters, UserInterface $user): AccessResultInterface {
    // dsm($parameters);
    // TODO.
    return AccessResult::allowed();
  }

}
