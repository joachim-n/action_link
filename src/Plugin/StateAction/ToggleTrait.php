<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Trait for actions which toggle between two states.
 */
trait ToggleTrait {

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
    $label = $this->configuration['labels']['state'][$state]['link_label'] ?? t("Change state");

    return $label;
  }

  public function getMessage(string $direction, string $state, ...$parameters): string {
    return $this->configuration['labels']['state'][$state]['message'] ?? '';
  }

}
