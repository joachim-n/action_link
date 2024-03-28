<?php

namespace Drupal\action_link\Plugin\ActionLinkOutput;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for Action Link Output plugins.
 */
interface ActionLinkOutputInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

  /**
   * Determines whether the plugin is usable with the action link entity.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   An action link entity.
   *
   * @return bool
   *   TRUE if the output plugin can be used with the given action link entity,
   *   FALSE otherwise.
   */
  public static function applies(ActionLinkInterface $action_link): bool;

}
