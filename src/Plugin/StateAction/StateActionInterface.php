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

  public function getNextStateName($user, ...$parameters): string;

  public function advanceState($account, $state, $parameters);

  public function checkOperability(AccountInterface $account, string $state): bool;

  public function checkAccess();

  public function getRedirectUrl(AccountInterface $account): ?Url;

}
