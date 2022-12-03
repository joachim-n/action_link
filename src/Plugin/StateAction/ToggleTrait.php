<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Trait for actions which toggle between two states.
 */
trait ToggleTrait {

  public function buildLabelsConfigurationForm($labels_form, FormStateInterface $form_state) {
    $labels_form['state']['true']['link_label'] = [
      '#type' => 'textfield',
      '#title' => t('Link label for setting the toggle'),
      '#required' => TRUE,
      // todo basic defaults.
    ];

    $labels_form['state']['false']['link_label'] = [
      '#type' => 'textfield',
      '#title' => t('Link label for removing the toggle'),
      '#required' => TRUE,
    ];

    return $labels_form;
  }

  // labels for each state.

  public function getLinkLabel(string $state, ...$parameters): string {
    $label = $this->configuration['labels']['state'][$state]['link_label'] ?? t("Change state");

    return $label;
  }

}
