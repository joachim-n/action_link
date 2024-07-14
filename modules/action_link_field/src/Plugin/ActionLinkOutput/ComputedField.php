<?php

namespace Drupal\action_link_field\Plugin\ActionLinkOutput;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\action_link\Attribute\ActionLinkOutput;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\ActionLinkOutput\ActionLinkOutputBase;
use Drupal\action_link\Plugin\StateAction\EntityActionLinkInterface;

/**
 * Outputs an action link as a computed field on controlled entities.
 */
#[ActionLinkOutput(
  id: "computed_field",
  label: new TranslatableMarkup("Computed field"),
  description: new TranslatableMarkup("Defines a computed field on the entity which shows the action links."),
)]
class ComputedField extends ActionLinkOutputBase {

  /**
   * {@inheritdoc}
   */
  public static function appliesToActionLink(ActionLinkInterface $action_link): bool {
    $state_action_plugin = $action_link->getStateActionPlugin();

    if (!$state_action_plugin instanceof EntityActionLinkInterface) {
      return FALSE;
    }

    // We can only show an action link as a computed field if we know how to
    // pass it dynamic parameter, so only show those which have only the entity
    // as a parameter.
    if ($state_action_plugin->getDynamicParameterNames() != ['entity']) {
      return FALSE;
    }

    return TRUE;
  }

}
