<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for State Action plugins.
 */
interface StateActionInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  public function buildConfigurationForm(array $plugin_form, FormStateInterface $form_state);

  public function getNextStateName($user, ...$parameters): string;

  public function advanceState($account, $state, $parameters);

  public function checkOperability();

  public function checkAccess();

}
