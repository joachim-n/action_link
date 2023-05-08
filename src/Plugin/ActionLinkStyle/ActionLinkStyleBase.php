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
  public function alterLinksBuild(&$build, ActionLinkInterface $action_link, AccountInterface $user, $named_parameters, $scalar_parameters) {
    // Do nothing by default.
  }

}
