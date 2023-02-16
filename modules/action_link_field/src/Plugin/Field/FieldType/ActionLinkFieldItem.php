<?php

namespace Drupal\action_link_field\Plugin\Field\FieldType;

use Drupal\computed_field\Plugin\Field\FieldType\ComputedRenderArrayItem;

/**
 * Computed field type for action linksets.
 *
 * This exists only to limit the available formatters.
 *
 * @FieldType(
 *   id = "action_linkset",
 *   label = @Translation("Action link field"),
 *   no_ui = TRUE,
 * )
 */
class ActionLinkFieldItem extends ComputedRenderArrayItem {

}
