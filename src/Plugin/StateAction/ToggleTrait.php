<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

trait ToggleTrait {

  // labels for each state.

  public function getLinkLabel(string $state, ...$parameters): TranslatableMarkup {
    $label = $this->configuration['labels']['link'][$state] ?? t("Change state");

    return $label;
  }

}
