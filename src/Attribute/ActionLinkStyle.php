<?php

namespace Drupal\action_link\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Action Link Style attribute object.
 *
 * Plugin namespace: ActionLinkStyle.
 */
#[\Attribute(
  \Attribute::TARGET_CLASS,
)]
class ActionLinkStyle extends Plugin {

  /**
   * Constructs an ActionLinkStyle attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The human-readable description of the plugin.
   * @param bool $handle_state_change
   *   (optional) Whether this plugin takes care of advancing the action state.
   *   If FALSE, the state change is carried out by ActionLinkController before
   *   invoking the style plugin. If TRUE, ActionLinkController does not change
   *   state and it is up to the style plugin to do this. See
   *   \Drupal\action_link\Plugin\ActionLinkStyle\ActionLinkStyleInterface::handleActionRequest().
   * @param bool $no_ui
   *   (optional) A boolean stating that this style should not appear in the
   *   form for configuring an action link.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
    public readonly bool $handle_state_change = FALSE,
    public readonly bool $no_ui = FALSE,
  ) {
  }

}
