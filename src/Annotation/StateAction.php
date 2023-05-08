<?php

namespace Drupal\action_link\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the State Action plugin annotation object.
 *
 * Plugin namespace: StateAction.
 *
 * @Annotation
 */
class StateAction extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id = '';

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label = '';

  /**
   * The human-readable description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description = '';

  /**
   * The directions for the plugin's links.
   *
   * Array keys are machine names, array values are labels.
   *
   * @var array
   */
  public $directions = [];

  /**
   * The dynamic parameters for this plugin.
   *
   * These names are use as route parameters, and therefore the following names
   * are reserved: link_style, direction, state, user.
   *
   * If any parameters need to be upcasted in the route, then the parameter type
   * should be declared in the plugin's getActionRoute().
   *
   * @var array
   *   An array of the dynamic parameter names for this plugin.
   *
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::getDynamicParametersFromRouteMatch()
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::getDynamicParameterNames()
   * @see \Drupal\action_link\Plugin\StateAction\StateActionInterface::getActionRoute()
   */
  public $dynamic_parameters = [];

}
