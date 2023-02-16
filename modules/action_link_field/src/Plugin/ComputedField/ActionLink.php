<?php

namespace Drupal\action_link_field\Plugin\ComputedField;

use Drupal\computed_field\Field\ComputedFieldDefinitionWithValuePluginInterface;
use Drupal\computed_field\Plugin\ComputedField\ComputedFieldBase;
use Drupal\computed_field\Plugin\ComputedField\SingleValueTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;

/**
 * TODO: class docs.
 *
 * @ComputedField(
 *   id = "action_link",
 *   label = @Translation("Action link"),
 *   field_type = "action_link_field",
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
      '#action_link' => 'test_date', // !!!! from derivative plugin ID!
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
