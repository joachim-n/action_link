<?php

namespace Drupal\action_link\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a State Action attribute object.
 *
 * Plugin namespace: StateAction.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class StateAction extends Plugin {

  /**
   * Constructs a StateAction attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The human-readable name of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $description
   *   The human-readable description of the plugin.
   * @param array $directions
   *   (optional) The directions for the plugin's links. An array whose keys are
   *   machine names and whose values are labels. May be omitted if directions
   *   are derived, in which case StateActionInterface::getDirections() must
   *   define them.
   * @param array $dynamic_parameters
   *   (optional) The dynamic parameters for this plugin. These names are used
   *   as route parameters, and therefore the following names are reserved:
   *   link_style, direction, state, user. If any parameters need to be upcasted
   *   in the route, then the parameter type should be declared in the plugin's
   *   getActionRoute().
   * @param array $states
   *   (optional) The states for this plugin, if they are finite and fixed. This
   *   property cannot be used in the following cases:
   *    - plugins which have dynamic states, which should override
   *      StateActionInterface::getStates() instead.
   *    - plugins which have infinite states, which should override
   *      StateActionInterface::getStateLabel() instead.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   *
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::getDynamicParametersFromRouteMatch()
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::getDynamicParameterNames()
   * @see \Drupal\action_link\Plugin\StateAction\StateActionInterface::getActionRoute()
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly TranslatableMarkup $description,
    public readonly array $directions = [],
    public readonly ?array $dynamic_parameters = [],
    public readonly ?array $states = [],
    public readonly ?string $deriver = NULL,
  ) {
  }

}
