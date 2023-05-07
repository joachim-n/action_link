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
          $element['#user'] ?? NULL,
          $element['#link_style'] ?? NULL,
          // We have to strip the keys again here, as otherwise PHP will try to
          // match keys to method parameter names. We can't pass the array as a
          // single parameter, because lazy builder callbacks don't allow that.
          ...array_values($element['#dynamic_parameters']),
        ]],
      '#create_placeholder' => TRUE,
    ];

        // Directly create a placeholder as we need this to be placeholdered
    // regardless if this is a POST or GET request.
    // @todo remove this when https://www.drupal.org/node/2367555 lands.
    // $element = \Drupal::service('render_placeholder_generator')->createPlaceholder($element);

    // ARGH! LB params can't be objects!
    //
    // ARGH! render elements can't use LB's like this, have to createPlaceholder themselves!
    // ARGH! docs in status messages are wrong - should mention this.

    return $element;
  }

  /**
   * Lazy builder for the linkset.
   *
   * @param string $action_link_id
   *   The action link entity ID.
   * @param int|null $user_id
   *   The user ID to return the linkset for, or NULL for a linkset which is
   *   for the current user.
   * @param string|null $link_style
   *   The link style to use, or NULL to use the link style that is set on the
   *   action link entity.
   * @param mixed ...$scalar_dynamic_parameters
   *   The dynamic parameters for the state action plugin.
   */
  public static function linksetLazyBuilder(string $action_link_id, ?int $user_id, ?string $link_style, ...$scalar_dynamic_parameters) {
    $entity_type_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $entity_type_manager->getStorage('action_link')->load($action_link_id);
    $state_action_plugin = $action_link->getStateActionPlugin();

    // Temporarily switch the link style. This avoids having an additional
    // parameter to buildLinkSet() which can't be optional because it comes
    // before the variadic parameters, which would be further-reaching ugliness.
    // This hack works because
    // \Drupal\action_link\Controller\ActionLinkController respects the link
    // style given in the path.
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

      // Make a dummy defaults array so we can use the parameter converting system
      // to upcast the dynamic parameters.
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

    return $state_action_plugin->buildLinkSet($action_link, $user, $scalar_dynamic_parameters, $dynamic_parameters);
  }

}
