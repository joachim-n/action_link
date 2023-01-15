<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for Action Link Style plugins.
 */
abstract class ActionLinkStyleBase extends PluginBase implements ActionLinkStyleInterface {

  public function alterLinksBuild($build, $action_link, $user, ...$parameters) {
    // Do nothing by default.
  }

}
