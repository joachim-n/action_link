<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Interface for State Action plugins.
 */
interface StateActionInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  public function buildConfigurationForm(array $plugin_form, FormStateInterface $form_state);

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
   *   The name of the next state for the action, in the given direction if
   *   this action defines directions. If there is no valid state, NULL is
   *   returned.
   */
  public function getNextStateName($user): ?string;

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

  public function checkOperability(AccountInterface $account, string $state): bool;

  public function checkAccess();

  public function getRedirectUrl(AccountInterface $account): ?Url;

}
