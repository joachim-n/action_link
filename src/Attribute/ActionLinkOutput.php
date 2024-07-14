<?php

namespace Drupal\action_link\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Action Link Output attribute object.
 *
 * Plugin namespace: ActionLinkOutput.
 */
#[\Attribute(
  \Attribute::TARGET_CLASS,
)]
class ActionLinkOutput extends Plugin {

  /**
   * Constructs a ActionLinkOutput attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The plugin label.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The plugin description.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
  ) {
  }

}
