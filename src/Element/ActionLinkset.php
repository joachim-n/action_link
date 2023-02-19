<?php

namespace Drupal\action_link\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Render element for an action link's linkset.
 *
 * Properties:
 *   - #action_link: The action link entity ID.
 *   - #user: (optional) The user to get the links for. Defaults to the current
 *     user.
 *   - #parameters: (optional) The parameters for the action link's state
 *     action plugin. These should be raw values as used in the action link
 *     URLs, not upcasted objects.
 *   - #link_style: (optional) The ID of an action link style plugin to override
 *     the link style set in the action link config entity.
 *
 * Usage example:
 * @code
 * $build['links'] = [
 *   '#type' => 'action_linkset',
 *   '#action_link' => 'my_action_link',
 *   '#link_style' => 'ajax',
 *   '#parameters' => [
 *     42,
 *   ],
 * ];
 * @endcode
 *
 * @RenderElement("action_linkset")
 */
class ActionLinkset extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;
    return [
      '#pre_render' => [
        [$class, 'preRenderLinkset'],
      ],
      '#parameters' => [],
    ];
  }

  /**
   * Pre-render callback: Renders an action linkset.
   *
   * Doing so during pre_render allows elements to be altered by field
   * formatters.
   *
   * @param array $element
   *   An associative array with the following properties:
   *   - #action_link: The action link entity ID.
   *   - #user: (optional) The user to get the links for.
   *   - #parameters: (optional) The parameters for the action link's state
   *     action plugin.
   *   - #link_style: (optional) The ID of an action link style plugin to
   *     override the action link's configuration.
   *
   * @return array
   *   The passed-in element containing the render elements for the link.
   */
  public static function preRenderLinkset($element) {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $action_link = $entity_type_manager->getStorage('action_link')->load($element['#action_link']);

    // Temporarily switch the link style. This avoids having an additional
    // parameter to buildLinkSet() which can't be optional because it comes
    // before the variadic parameters, which would be further-reaching ugliness.
    // This hack works because ActionLinkController respects the link style
    // given in the path.
    if (!empty($element['#link_style'])) {
      $action_link->set('link_style', $element['#link_style']);
    }

    $element += $action_link->buildLinkSet($element['#user'] ?? \Drupal::currentUser(), ...$element['#parameters']);

    return $element;
  }

}
