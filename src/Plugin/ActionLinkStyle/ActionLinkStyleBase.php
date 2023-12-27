<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Base class for Action Link Style plugins.
 */
abstract class ActionLinkStyleBase extends PluginBase implements ActionLinkStyleInterface {

  /**
   * {@inheritdoc}
   */
  public function alterLinksBuild(array &$build, ActionLinkInterface $action_link, AccountInterface $user, array $named_parameters, array $scalar_parameters) {
    // Do nothing by default.
  }

}
