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
   * @param bool $no_ui
   *   (optional) A boolean stating that this style should not appear in the
   *   form for configuring an action link.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
    public readonly bool $no_ui = FALSE,
  ) {
  }

}
