<?php

namespace Drupal\action_link\Element;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\action_link\ActionLinkStyleManager;

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
 *   - TODO link style.
 *
 * Usage example:
 * @code
 * $build['action_link'] = [
 *   '#type' => 'action_linkset',
 *   '#action_link' => 'my_action_link,
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
   *
   * @return array
   *   The passed-in element containing the render elements for the link.
   */
  public static function preRenderLinkset($element) {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $action_link = $entity_type_manager->getStorage('action_link')->load($element['#action_link']);

    $element += $action_link->buildLinkSet($element['#user'] ?? \Drupal::currentUser(), ...$element['#parameters']);

    return $element;
  }

}
