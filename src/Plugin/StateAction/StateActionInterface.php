<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for State Action plugins.
 */
interface StateActionInterface extends PluginInspectionInterface, DerivativeInspectionInterface {



  public function getNextStateName($user, ...$parameters): string;

  public function checkOperability();

  public function checkAccess();

}
