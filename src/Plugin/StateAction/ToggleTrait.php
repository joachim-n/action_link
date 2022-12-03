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
    $labels_form['link_label_set'] = [
      '#type' => 'textfield',
      '#title' => t('Link label for setting the toggle'),
      '#required' => TRUE,
      // todo basic defaults.
    ];

    $labels_form['link_label_unset'] = [
      '#type' => 'textfield',
      '#title' => t('Link label for removing the toggle'),
      '#required' => TRUE,
    ];

    return $labels_form;
  }

  // labels for each state.

  public function getLinkLabel(string $state, ...$parameters): TranslatableMarkup {
    $label = $this->configuration['labels']['link'][$state] ?? t("Change state");

    return $label;
  }

}
