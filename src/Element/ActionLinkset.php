<?php

namespace Drupal\action_link\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Routing\RouteObjectInterface;

/**
 * Render element for an action link's linkset.
 *
 * This can be used directly, or obtained from:
 * @code
 * $action_link_entity->buildLinkSet()
 * @code
 *
 * This uses a lazy builder as links are per-user and therefore considered
 * uncacheable.
 *
 * Properties:
 *   - #action_link: The action link entity ID.
 *   - #user: (optional) The user to get the links for. Defaults to the current
 *     user.
 *   - #dynamic_parameters: (optional) The parameters for the action link's
 *     state action plugin. These must be raw values as used in the action link
 *     URLs, rather than upcasted objects. They must be in the same order as the
 *     declaration of the dynamic parameters in the state action plugin's
 *     definition. Keys may either be numeric, or the parameter names.
 *   - #link_style: (optional) The ID of an action link style plugin to override
 *     the link style set in the action link config entity.
 *   - #direction: (optional) The name of a direction. If specified, the linkset
 *     shows only this direction.
 *
 * Usage example:
 * @code
 * $build['links'] = [
 *   '#type' => 'action_linkset',
 *   '#action_link' => 'my_action_link',
 *   '#link_style' => 'ajax',
 *   '#dynamic_parameters' => [
 *     $entity->id(),
 *   ],
 * ];
 * @endcode
 *
 * Whether links are output depends on operability of the action link, and
 * access and reachability of each direction. (For an overview of these
 * concepts, see the ActionLink entity class.)
 *
 * There are three possible outputs for a link:
 *   - Nothing
 *   - The link with the action URL
 *   - The link as an empty span
 *
 * The reason for the empty span outut is the case where the direction for the
 * link is not accessible or reachable. Either of these could change if a
 * different direction link is used, because the target state in that direction
 * will then change. With the AJAX link style, the empty link is replaced
 * with the new, normal link.
 *
 * For example, with an 'add to cart' action link,
 * if the user has no products in their cart, the 'remove' direction is not
 * reachable. An empty link is output. If the user then clicks the 'add' link,
 * the empty 'remove' link is replaced by AJAX with a normal 'remove' link
 * because it is now possible to remove an item from the cart.
 *
 * The full flow of code is:
 * - (optional) \Drupal\action_link\Entity\ActionLinkInterface::buildLinkSet()
 * - static::preRenderLinkset()
 * - static::linksetLazyBuilder()
 * - \Drupal\action_link\Plugin\StateAction\StateAction::buildLinkSet()
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
      '#dynamic_parameters' => [],
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
   *   - #user: (optional) The user to get the links for. If empty, all users
   *     get a link for their own account.
   *   - #dynamic_parameters: (optional) The parameters for the action link's
   *     state action plugin.
   *   - #link_style: (optional) The ID of an action link style plugin to
   *     override the action link's configuration.
   *
   * @return array
   *   The passed-in element containing the render elements for the link.
   */
  public static function preRenderLinkset(array $element) {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $entity_type_manager->getStorage('action_link')->load($element['#action_link']);
    assert(!empty($action_link));

    $element['linkset'] = [
      '#lazy_builder' => [
        static::class . '::linksetLazyBuilder', [
          $element['#action_link'],
          // Don't replace a NULL for the user value with the current user ID at
          // this point, as that would pollute the cache.
          $element['#direction'] ?? NULL,
          $element['#user'] ?? NULL,
          $element['#link_style'] ?? NULL,
          // We have to strip the keys again here, as otherwise PHP will try to
          // match keys to method parameter names. We can't pass the array as a
          // single parameter, because lazy builder callbacks don't allow that.
          ...array_values($element['#dynamic_parameters']),
        ],
      ],
      '#create_placeholder' => TRUE,
    ];

    return $element;
  }

  /**
   * Lazy builder for the linkset.
   *
   * @param string $action_link_id
   *   The action link entity ID.
   * @param string|null $direction
   *   The direction to show, or NULL to show all reachable directions.
   * @param int|null $user_id
   *   The user ID to return the linkset for, or NULL for a linkset which is
   *   for the current user.
   * @param string|null $link_style
   *   The link style to use, or NULL to use the link style that is set on the
   *   action link entity.
   * @param mixed ...$scalar_dynamic_parameters
   *   The dynamic parameters for the state action plugin.
   */
  public static function linksetLazyBuilder(string $action_link_id, ?string $direction, ?int $user_id, ?string $link_style, ...$scalar_dynamic_parameters) {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $entity_type_manager->getStorage('action_link')->load($action_link_id);
    $state_action_plugin = $action_link->getStateActionPlugin();

    // Temporarily switch the link style. This avoids having an additional
    // parameter to buildLinkSet() which can't be optional because it comes
    // before the variadic parameters, which would be further-reaching ugliness.
    // This hack works because
    // \Drupal\action_link\Controller\ActionLinkController respects the link
    // style given in the path, and because all the function calls from this
    // point pass the action link entity rather than load it from storage.
    if (!empty($link_style)) {
      $action_link->set('link_style', $link_style);
    }

    if ($scalar_dynamic_parameters) {
      // Give the dynamic parameters their parameter names as keys.
      $scalar_dynamic_parameters = array_combine($state_action_plugin->getDynamicParameterNames(), $scalar_dynamic_parameters);

      // Use the routing system to upcast the dynamic parameters.
      $route_provider = \Drupal::service('router.route_provider');
      $route = $route_provider->getRouteByName($action_link->getRouteName());

      /** @var \Drupal\Core\ParamConverter\ParamConverterManagerInterface $param_converter_manager */
      $param_converter_manager = \Drupal::service('paramconverter_manager');

      // Make a dummy defaults array so we can use the parameter converting
      // system to upcast the dynamic parameters.
      $dummy_defaults = $scalar_dynamic_parameters;
      $dummy_defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;

      $converted_defaults = $param_converter_manager->convert($dummy_defaults);

      unset($converted_defaults[RouteObjectInterface::ROUTE_OBJECT]);

      $dynamic_parameters = $converted_defaults;
    }
    else {
      $dynamic_parameters = [];
    }

    if ($user_id) {
      $user = $entity_type_manager->getStorage('user')->load($user_id);
    }
    else {
      $user = \Drupal::currentUser();
    }

    if ($direction) {
      return $state_action_plugin->buildSingleLink($action_link, $direction, $user, $scalar_dynamic_parameters, $dynamic_parameters);
    }
    else {
      return $state_action_plugin->buildLinkSet($action_link, $user, $scalar_dynamic_parameters, $dynamic_parameters);
    }
  }

}
