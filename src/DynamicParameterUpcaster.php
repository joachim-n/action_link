<?php

namespace Drupal\action_link;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\ParamConverter\ParamConverterManagerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Routing\RouteProviderInterface;

/**
 * Converts scalar values of dynamic parameters to objects where relevant.
 *
 * @internal
 */
class DynamicParameterUpcaster {

  /**
   * Creates a DynamicParameterUpcaster instance.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $routeProvider
   *   The route provider service.
   * @param \Drupal\Core\ParamConverter\ParamConverterManagerInterface $paramconverterManager
   *   The param converter manager.
   */
  public function __construct(
    protected RouteProviderInterface $routeProvider,
    protected ParamConverterManagerInterface $paramconverterManager,
  ) {}

  /**
   * Gets the upcasted dynamic parameters from the scalar values.
   *
   * For example, for an entity ID in the given scalar values, the upcasted
   * array will contain the entity object.
   *
   * This uses the routing system to upcast the dynamic parameters.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param array $scalar_parameters
   *   The scalar values of the dynamic parameters, keyed by parameter name.
   *
   * @return array
   *   An array of the upcasted values, keyed by parameter name.
   */
  public function upcastDynamicParameters(ActionLinkInterface $action_link, array $scalar_parameters): array {
    $route = $this->routeProvider->getRouteByName($action_link->getRouteName());

    // Make a dummy defaults array so we can use the parameter converting
    // system to upcast the dynamic parameters.
    $dummy_defaults = $scalar_parameters;
    $dummy_defaults[RouteObjectInterface::ROUTE_OBJECT] = $route;

    $converted_defaults = $this->paramconverterManager->convert($dummy_defaults);

    unset($converted_defaults[RouteObjectInterface::ROUTE_OBJECT]);

    return $converted_defaults;
  }

}
