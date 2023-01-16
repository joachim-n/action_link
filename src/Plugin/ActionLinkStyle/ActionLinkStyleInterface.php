<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Session\AccountInterface;

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
 * degradation of JavaScript line.
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
   * @param \Drupal\user\UserInterface $user
   *   The user account the links are for.
   * @param mixed ...$parameters
   *   Additional parameter specific to the action link's state action plugin.
   */
  public function alterLinksBuild(&$build, ActionLinkInterface $action_link, AccountInterface $user, ...$parameters);

}
