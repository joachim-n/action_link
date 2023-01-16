<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\flag\Ajax\ActionLinkFlashCommand;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TODO: class docs.
 *
 * @ActionLinkStyle(
 *   id = "ajax",
 *   label = @Translation("JavaScript"),
 *   description = @Translation("A link which makes an AJAX JavaScript request without reloading the page.")
 * )
 */
class Ajax extends ActionLinkStyleBase {

  /**
   * {@inheritdoc}
   */
  public function alterLinksBuild(&$build, ActionLinkInterface $action_link, AccountInterface $user, ...$parameters) {
    foreach ($build as $direction => $direction_link_build) {
      $build[$direction]['#attributes']['class'][] = 'use-ajax';
    }

    // TODO!
    // $build['#attached']['library'][] = 'action_link/action_link.ajax';
  }

}
