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
   * TODO - change this to just dynamic?
   *
   * These names are use as route parameters, and therefore the following names
   * are reserved: link_style, direction, state, user.
   *
   * @var array
   *   An array of the directions for this plugin. Keys are direction machine
   *   names, and values are human-readable labels. Keys are used in URLs and
   *   must therefore may not contain special characters.
   *
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::getDynamicParametersFromRouteMatch()
   */
  public $parameters = [];

  // global

  // cyclical?

  // state count

}
