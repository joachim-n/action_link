<?php

namespace Drupal\action_link_field\Plugin\ComputedField;

use Drupal\computed_field\Field\ComputedFieldDefinitionWithValuePluginInterface;
use Drupal\computed_field\Plugin\ComputedField\ComputedFieldBase;
use Drupal\computed_field\Plugin\ComputedField\SingleValueTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Computed field that shows an action link's linkset.
 *
 * The deriver creates a plugin for each action link that has the computed
 * field setting enabled.
 *
 * This plugin class overrides the attachAsFooField() methods, because the
 * deriver can't determine how to attach the field. This is because to do so, it
 * needs to inspect the definition of the field that the action link controls,
 * and discovery of computed field plugins takes place when field definitions
 * are being build: therefore the process is circular:
 *  - Build field definitions
 *  - Discover automatically attaching computed field plugins
 *  - Derive plugins from action_link entities
 *  - Get the definition of the field that an action link controls: circularity!
 *
 * @ComputedField(
 *   id = "action_link",
 *   label = @Translation("Action link"),
 *   field_type = "action_linkset",
 *   no_ui = TRUE,
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
      '#dynamic_parameters' => [
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
    // TODO! not needed.
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

  /**
   * {@inheritdoc}
   */
  public function attachAsBaseField($fields, EntityTypeInterface $entity_type): bool {
    // Match the scope of the controlled field.
    return isset($fields[$this->pluginDefinition['attach']['controlled_field']]);
  }

  /**
   * {@inheritdoc}
   */
  public function attachAsBundleField($fields, EntityTypeInterface $entity_type, string $bundle): bool {
    // Match the scope and bundle of the controlled field.
    return isset($fields[$this->pluginDefinition['attach']['controlled_field']]);
  }

}
