<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for State Action plugins.
 */
abstract class StateActionBase extends PluginBase implements StateActionInterface {

  // todo interface.
  public function convertParametersForRoute(array $parameters): array {
    $dynamic_parameter_indexes = array_flip($this->pluginDefinition['parameters']['dynamic']);

    if (isset($dynamic_parameter_indexes['entity'])) {
      $index = $dynamic_parameter_indexes['entity'];
      $parameters[$index] = $parameters[$index]->id();
    }

    return $parameters;
  }


  public function upcastRouteParameters(array $parameters): array {
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $dynamic_parameter_indexes = array_flip($this->pluginDefinition['parameters']['dynamic']);

    if (isset($dynamic_parameter_indexes['entity'])) {
      $index = $dynamic_parameter_indexes['entity'];
      $parameters[$index] = $entity_type_manager->getStorage($this->configuration['entity_type'])->load($parameters[$index]);
    }

    return $parameters;
  }

}
