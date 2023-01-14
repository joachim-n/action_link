<?php

namespace Drupal\action_link\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the Action Link Style plugin annotation object.
 *
 * Plugin namespace: ActionLinkStyle.
 *
 * @Annotation
 */
class ActionLinkStyle extends Plugin {

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

}
