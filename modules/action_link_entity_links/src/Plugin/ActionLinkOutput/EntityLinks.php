<?php

namespace Drupal\action_link_entity_links\Plugin\ActionLinkOutput;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\action_link\Attribute\ActionLinkOutput;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\ActionLinkOutput\ActionLinkOutputBase;
use Drupal\action_link\Plugin\StateAction\EntityActionLinkInterface;

/**
 * Output plugin for showing action links in entity links.
 */
#[ActionLinkOutput(
  id: "entity_links",
  label: new TranslatableMarkup("Entity links"),
  description: new TranslatableMarkup("Display the action links in node or comment links"),
)]
class EntityLinks extends ActionLinkOutputBase {

  /**
   * {@inheritdoc}
   */
  public static function applies(ActionLinkInterface $action_link): bool {
    $state_action_plugin = $action_link->getStateActionPlugin();

    if (!$state_action_plugin instanceof EntityActionLinkInterface) {
      return FALSE;
    }

    // We can only show an action link in entity links if we know how to pass it
    // dynamic parameter, so only show this which have only the entity as a
    // parameter.
    if ($state_action_plugin->getDynamicParameterNames() != ['entity']) {
      return FALSE;
    }

    // TODO also only on node or comment!!!!! OMG
    // ARGH this is ony on entity base! will CRASH ON OTHERS
    // need an interface for this!!!
    if (!in_array($state_action_plugin->getTargetEntityTypeId(), ['node', 'comment'])) {
      return FALSE;
    }

    return TRUE;
  }

}
