<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Trait for actions which are repeatable in one or more directions.
 *
 * For example, an action which increases or decreases the value of a numeric
 * field on an entity is repeatable, because the value can be increased over and
 * over again, and has two directions: increase and decrease. A similar action
 * on a colour value field would have six directions: increase and decrease each
 * of the red, green, blue components.
 */
trait RepeatableTrait {

  // labels for each direction

  public function getLinkLabel(string $state, ...$parameters): TranslatableMarkup {

    $label = $this->configuration['labels']['link'][$state] ?? t("Change state");

    return $label;
  }

}