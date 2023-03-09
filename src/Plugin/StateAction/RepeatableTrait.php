<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Geometry trait for actions which are repeatable in one or more directions.
 *
 * For example, an action which increases or decreases the value of a numeric
 * field on an entity is repeatable, because the value can be increased over and
 * over again, and has two directions: increase and decrease. A similar action
 * on a colour value field would have six directions: increase and decrease each
 * of the red, green, blue components.
 */
// TODO rename RepeatableGeometryTrait ?
trait RepeatableTrait {

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
      ];
    }

    return $labels_form;
  }

  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    $label = $this->configuration['labels']['direction'][$direction]['link_label'] ?? t("Change value");

    return $label;
  }

  public function getMessage(string $direction, string $state, ...$parameters): string {
    return $this->configuration['labels']['direction'][$direction]['message'] ?? '';
  }

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
