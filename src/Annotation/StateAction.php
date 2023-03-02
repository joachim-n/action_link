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
   * @var array
   */
  public $parameters = [];

  // global

  // cyclical?

  // state count

}
