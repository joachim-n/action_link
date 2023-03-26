<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Trait for actions with only two states and a single direction to toggle.
 *
 * A toggle action has only one direction, and that direction flips it between
 * two states, such as 'on' and 'off'.
 *
 * Plugin classes using this trait must:
 *  - implement \Drupal\Component\Plugin\ConfigurableInterface
 *  - implement \Drupal\Core\Plugin\PluginFormInterface
 *  - in their buildConfigurationForm(), set the form elements from
 *    this trait's buildTextsConfigurationForm() in to $element['labels'].
 *  - define two states, in the order 'set, unset'.
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

    [$set_state, $unset_state] = $this->getStates();

    $defaults['labels']['state'][$set_state]['link_label'] = 'Change state';
    $defaults['labels']['state'][$set_state]['message'] = 'Value set to TRUE';

    $defaults['labels']['state'][$unset_state]['link_label'] = 'Change state';
    $defaults['labels']['state'][$unset_state]['message'] = 'Value set to FALSE';

    return $defaults;
  }

  public function buildTextsConfigurationForm($labels_form, FormStateInterface $form_state) {
    [$set_state, $unset_state] = $this->getStates();

    $labels_form['state'][$set_state] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Texts for setting the toggle'),
    ];

    $labels_form['state'][$set_state]['link_label'] = [
      '#type' => 'textfield',
      '#title' => t('Link label for setting the toggle'),
      '#required' => TRUE,
      // todo basic defaults.
    ];

    $labels_form['state'][$set_state]['message'] = [
      '#type' => 'textfield',
      '#title' => t('Message when setting the toggle'),
      '#description' => t('Leave empty to show no message.'),
    ];

    $labels_form['state'][$unset_state] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Texts for unsetting the toggle'),
    ];

    $labels_form['state'][$unset_state]['link_label'] = [
      '#type' => 'textfield',
      '#title' => t('Link label for removing the toggle'),
      '#required' => TRUE,
    ];

    $labels_form['state'][$unset_state]['message'] = [
      '#type' => 'textfield',
      '#title' => t('Message when unsetting the toggle'),
      '#description' => t('Leave empty to show no message.'),
    ];

    return $labels_form;
  }


  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    $label = $this->configuration['labels']['state'][$state]['link_label'];

    return $label;
  }

  public function getMessage(string $direction, string $state, ...$parameters): string {
    return $this->configuration['labels']['state'][$state]['message'];
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
