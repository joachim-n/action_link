<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Trait for actions which have only two states.
 *
 * A toggle action has only one direction, and that direction flips it between
 * two states, such as 'on' and 'off'.
 *
 * (An action like this could also be defined to have two directions, where each
 * direction can only advance to one state. It's mostly a matter of conceptual
 * preference. Though it would mean more complicated operability checks, and the
 * direction and state would essentially duplicate information in the action
 * link path parameters. Doing it this way means a toggle is a special case of
 * a cyclical action.)
 *
 * TODO This should be more for cyclical states rather than only 2 state toggles?
 *
 */
trait ToggleTrait {

  public function stringsDefaultConfiguration() {
    $defaults = [];
    $defaults['labels']['state']['true']['link_label'] = 'Change state';
    $defaults['labels']['state']['true']['message'] = 'Value set to TRUE';

    $defaults['labels']['state']['false']['link_label'] = 'Change state';
    $defaults['labels']['state']['false']['message'] = 'Value set to FALSE';

    return $defaults;
  }

  public function buildTextsConfigurationForm($labels_form, FormStateInterface $form_state) {
    $labels_form['state']['true'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Texts for setting the toggle'),
    ];

    $labels_form['state']['true']['link_label'] = [
      '#type' => 'textfield',
      '#title' => t('Link label for setting the toggle'),
      '#required' => TRUE,
      // todo basic defaults.
    ];

    $labels_form['state']['true']['message'] = [
      '#type' => 'textfield',
      '#title' => t('Message when setting the toggle'),
    ];

    $labels_form['state']['false'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Texts for unsetting the toggle'),
    ];

    $labels_form['state']['false']['link_label'] = [
      '#type' => 'textfield',
      '#title' => t('Link label for removing the toggle'),
      '#required' => TRUE,
    ];

    $labels_form['state']['false']['message'] = [
      '#type' => 'textfield',
      '#title' => t('Message when unsetting the toggle'),
    ];

    return $labels_form;
  }


  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    // TODO: config defaults? how in trait?
    $label = $this->configuration['labels']['state'][$state]['link_label'] ?? t("Change state");

    return $label;
  }

  public function getMessage(string $direction, string $state, ...$parameters): string {
    return $this->configuration['labels']['state'][$state]['message'] ?? '';
  }

  public function XXgetStateActionPermissions(ActionLinkInterface $action_link): array {
    // TODO: need getStates()
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
