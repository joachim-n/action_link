<?php

/**
 * @file
 * Contains token hooks for Action Link.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

/**
 * Implements hook_token_info().
 */
function action_link_token_info() {
  $type = [
    'name' => t('State action'),
    'description' => t('Tokens related to a state action change.'),
    'needs-data' => 'action_state',
  ];

  // Core tokens for nodes.
  $tokens['new_state'] = [
    'name' => t("State"),
    'description' => t("The unique ID of the TODO."),
  ];

  return [
    'types' => ['action_state' => $type],
    'tokens' => ['action_state' => $tokens],
  ];
}

/**
 * Implements hook_tokens().
 */
function action_link_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }
  $replacements = [];

  // ARGH! TWO data blobs! TODO!
  if ($type == 'action_state' && !empty($data['action_state'])) {
    /** @var \Drupal\action_link\Token\StateChangeTokenData $action_link */
    $action_state_data = $data['action_state'];
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $action_state_data->actionLink;

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'new_state':
          $replacements[$original] = $action_link->getStateActionPlugin()->getStateLabel($action_state_data->state);
          break;

        case 'new_state:raw':
          $replacements[$original] = $action_state_data->state;
          break;
      }
    }
  }

  return $replacements;
}
