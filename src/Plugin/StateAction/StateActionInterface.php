<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Interface for State Action plugins.
 */
interface StateActionInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface {

  /**
   * Undocumented function
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
   *   Additional parameters depending on the plugin.
   *
   * @return \Drupal\Core\Link|null
   *   The link object, or NULL if no link is applicable.
   */
  public function getLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user): ?Link;

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
  public function advanceState($account, $state, $parameters);

  public function checkOperability(string $direction, string $state, AccountInterface $account, ...$parameters): bool;

  public function checkAccess();

  public function getRedirectUrl(AccountInterface $account): ?Url;

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

}
