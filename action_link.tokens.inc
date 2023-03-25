<?php

/**
 * @file
 * TODO: Enter file description here.
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
    'needs-data' => 'action_link',
  ];

  // Core tokens for nodes.
  $tokens['new_state'] = [
    'name' => t("State"),
    'description' => t("The unique ID of the TODO."),
  ];

  return [
    // state_action???
    'types' => ['action_link' => $type],
    'tokens' => ['action_link' => $tokens],
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

  if ($type == 'action_link' && !empty($data['action_link'])) {
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $data['action_link'];
    $action_state_data = $data['action_state_data'];

    // 'action_link' => $action_link,
    // 'action_state_data' => new StateChangeTokenData(
    //   $action_link,
    //   $direction,
    //   $next_state,
    // )


    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'new_state':
          $replacements[$original] = $action_link->getStateActionPlugin()->getStateLabel($action_state_data->state);
          break;

        case 'new_state:raw':
          $replacements[$original] = $action_state_data->state;
          break;

        case 'title':
          $replacements[$original] = $node->getTitle();
          break;

        case 'edit-url':
          $replacements[$original] = $node->toUrl('edit-form', $url_options)->toString();
          break;

        // Default values for the chained tokens handled below.
        case 'author':
          $account = $node->getOwner() ? $node->getOwner() : User::load(0);
          $replacements[$original] = $account->label();
          $bubbleable_metadata->addCacheableDependency($account);
          break;

        case 'created':
          $replacements[$original] = \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    // if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
    //   $replacements += $token_service->generate('user', $author_tokens, ['user' => $node->getOwner()], $options, $bubbleable_metadata);
    // }

    // if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
    //   $replacements += $token_service->generate('date', $created_tokens, ['date' => $node->getCreatedTime()], $options, $bubbleable_metadata);
    // }
  }

  return $replacements;
}
