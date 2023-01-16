<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for Action Link Style plugins.
 *
 * An action link style plugin determines how the links for an action link
 * entity will behave in the UI.
 *
 * For example, a link could be a plain HTML link which reloads the current
 * page, or a JavaScript link which receives an AJAX response.
 *
 * An action link's link style setting affects how links are output, but does
 * not restrict which link styles are responded to. This means that any link
 * style URL will work for an action link entity. This is to allow for graceful
 * degradation of JavaScript line.
 */
interface ActionLinkStyleInterface extends PluginInspectionInterface, DerivativeInspectionInterface {

}
