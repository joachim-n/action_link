<?php

namespace Drupal\action_link_field\Plugin\ComputedField;

use Drupal\computed_field\Field\ComputedFieldDefinitionWithValuePluginInterface;
use Drupal\computed_field\Plugin\ComputedField\ComputedFieldBase;
use Drupal\computed_field\Plugin\ComputedField\SingleValueTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;

/**
 * Computed field that shows an action link's linkset.
 *
 * The deriver creates a plugin for each action link that has this enabled.
 *
 * @ComputedField(
 *   id = "action_link",
 *   label = @Translation("Action link"),
 *   field_type = "action_linkset",
 *   deriver = "Drupal\action_link_field\Plugin\Derivative\ActionLinkDeriver"
 * )
 */
class ActionLink extends ComputedFieldBase {

  use SingleValueTrait;

  /**
   * {@inheritdoc}
   */
  public function singleComputeValue(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): mixed {
    $build['links'] = [
      '#type' => 'action_linkset',
      // The plugin derivative ID is set to the action link entity ID in the
      // deriver.
      '#action_link' => $this->getDerivativeId(),
      '#parameters' => [
        $host_entity,
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function useLazyBuilder(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheability(EntityInterface $host_entity, ComputedFieldDefinitionWithValuePluginInterface $computed_field_definition): ?CacheableMetadata {
    $cacheability = new CacheableMetadata();

    $cacheability->setCacheContexts(['user']);
    $cacheability->setCacheMaxAge(0);

    return $cacheability;
  }

}
