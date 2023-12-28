<?php

namespace Drupal\action_link_formatter_links;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;

/**
 * Provides a delegate implementation of hook_entity_display_build_alter().
 */
class DisplayBuildAlter {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a DisplayBuildAlter instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Helper for hook_entity_display_build_alter().
   *
   * Same parameters.
   */
  public function hookEntityDisplayBuildAlter(&$build, $context) {
    [
      'entity' => $entity,
      'view_mode' => $view_mode,
      'display' => $display,
    ] = $context;

    foreach (Element::children($build) as $field_name) {
      $element = &$build[$field_name];

      $component = $display->getComponent($field_name);

      // Skip if the field formatter has no actions links configuration.
      if (!isset($component['third_party_settings']['action_link_formatter_links'])) {
        continue;
      }

      $settings = $component['third_party_settings']['action_link_formatter_links'];

      // Skip if the field is not configured to show actions links.
      if (empty($settings['action_links'])) {
        continue;
      }
      if (empty(array_filter($settings['action_links']))) {
        continue;
      }

      foreach (Element::children($element) as $delta) {
        foreach (array_filter($settings['action_links']) as $action_link_id) {
          /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
          $action_link = $this->entityTypeManager->getStorage('action_link')->load($action_link_id);

          // Replace the ajax plugin with our altered version.
          $link_style_plugin_id = $action_link->getLinkStylePlugin()->getPluginId();
          if ($link_style_plugin_id == 'ajax') {
            $link_style_plugin_id = 'ajax_entity_field';
          }

          $directions = $action_link->getStateActionPlugin()->getDirections();

          $original_element = $element[$delta];

          $element[$delta] = [
            '#type' => 'container',
            '#attributes' => [
              'class' => [
                $this->getWrapperCssClass($action_link, $entity, $field_name, $delta, $view_mode),
              ],
            ],
          ];

          if (count($directions) == 2) {
            // If there are exactly two directions, put one before and one after
            // the field -- we assume these are increment and decrement.
            [$direction_one, $direction_two] = array_keys($directions);

            $element[$delta]['action_link_' . $action_link_id . '_' . $direction_one] = [
              '#type' => 'action_linkset',
              '#action_link' => $action_link_id,
              '#link_style' => $link_style_plugin_id,
              '#dynamic_parameters' => [
                $entity->id(),
              ],
              '#direction' => $direction_one,
              // Pass the view mode into the extras array for the
              // ajax_entity_field plugin's alterLinksBuild() to find.
              '#extras' => [
                'view_mode' => $view_mode,
              ],
            ];

            $element[$delta]['field'] = $original_element;

            $element[$delta]['action_link_' . $action_link_id . '_' . $direction_two] = [
              '#type' => 'action_linkset',
              '#action_link' => $action_link_id,
              '#link_style' => $link_style_plugin_id,
              '#dynamic_parameters' => [
                $entity->id(),
              ],
              '#direction' => $direction_two,
              '#extras' => [
                'view_mode' => $view_mode,
              ],
            ];
          }
          else {
            // Otherwise, put the whole linkset after the field.
            $element[$delta]['field'] = $original_element;

            $element[$delta]['action_link_' . $action_link_id] = [
              '#type' => 'action_linkset',
              '#action_link' => $action_link_id,
              '#link_style' => $link_style_plugin_id,
              '#dynamic_parameters' => [
                $entity->id(),
              ],
              '#extras' => [
                'view_mode' => $view_mode,
              ],
            ];
          }
        }
      }
    }
  }

  /**
   * Gets the CSS class to use on the formatter wrapper for AJAX replacement.
   *
   * This doesn't need the entity type ID as the action link is specific to one
   * entity type, or user ID as action links that control entity fields are not
   * user-specific.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being controlled.
   * @param string $field_name
   *   The name of the field being controlled.
   * @param int $delta
   *   The delta being controlled.
   * @param string $view_mode
   *   The view mode being shown.
   */
  public function getWrapperCssClass(ActionLinkInterface $action_link, EntityInterface $entity, string $field_name, int $delta, string $view_mode): string {
    return implode(
      '-',
      [
        'action-link',
        $action_link->id(),
        $entity->id(),
        $field_name,
        $delta,
        $view_mode,
      ]
    );
  }

}
