<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Geometry trait for actions which are repeatable in one or more directions.
 *
 * For example, an action which increases or decreases the value of a numeric
 * field on an entity is repeatable, because the value can be increased over and
 * over again, and has two directions: increase and decrease. A similar action
 * on a colour value field would have six directions: increase and decrease each
 * of the red, green, blue components.
 */
trait RepeatableGeometryTrait {

  /**
   * Overrides stringsDefaultConfiguration()
   *
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::stringsDefaultConfiguration()
   */
  public function stringsDefaultConfiguration() {
    $defaults = [];

    foreach ($this->getDirections() as $direction => $direction_label) {
      $defaults['texts']['direction'][$direction]['link_label'] = t('@direction the value', [
        '@direction' => ucfirst($direction_label),
      ]);
      $defaults['texts']['direction'][$direction]['message'] = t('Value changed');
    }

    return $defaults;
  }

  /**
   * Overrides buildTextsConfigurationForm()
   *
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::buildTextsConfigurationForm()
   */
  public function buildTextsConfigurationForm($labels_form, FormStateInterface $form_state) {
    foreach ($this->getDirections() as $direction => $direction_label) {
      $labels_form['direction'][$direction] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('Texts for @direction', [
          '@direction' => $direction_label,
        ]),
      ];

      $labels_form['direction'][$direction]['link_label'] = [
        '#type' => 'textfield',
        '#title' => t('Link label for @direction', [
          '@direction' => $direction_label,
        ]),
        '#required' => TRUE,
      ];

      $labels_form['direction'][$direction]['message'] = [
        '#type' => 'textfield',
        '#title' => t('Message for @direction', [
          '@direction' => $direction_label,
        ]),
        '#description' => t('Leave empty to show no message.'),
      ];
    }

    return $labels_form;
  }

  /**
   * Overrides getLinkLabel()
   *
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::getLinkLabel()
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    $label = $this->configuration['texts']['direction'][$direction]['link_label'] ?? t("Change value");

    return $label;
  }

  /**
   * Overrides getMessage()
   *
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::getMessage()
   */
  public function getMessage(string $direction, string $state, ...$parameters): string {
    return $this->configuration['texts']['direction'][$direction]['message'] ?? '';
  }

  /**
   * Overrides getStateActionPermissions()
   *
   * @see \Drupal\action_link\Plugin\StateAction\StateActionBase::getStateActionPermissions()
   */
  public function getStateActionPermissions(ActionLinkInterface $action_link): array {
    $permissions = [];
    foreach ($this->getDirections() as $direction => $direction_label) {
      $permissions["use {$action_link->id()} action links in {$direction} direction"] = [
        'title' => t('Use %label action links to @direction', [
          '%label' => $action_link->label(),
          '@direction' => $direction_label,
        ]),
      ];
    }
    return $permissions;
  }

}
