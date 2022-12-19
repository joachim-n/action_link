<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Interface for State Action plugins.
 */
interface StateActionInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ConfigurableInterface {

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

  public function checkOperability(string $direction, string $state, AccountInterface $account): bool;

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
  public function getMessage(string $state, ...$parameters): string;

}
