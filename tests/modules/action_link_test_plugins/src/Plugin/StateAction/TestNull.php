<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;

/**
 * TODO: class docs.
 *
 * @StateAction(
 *   id = "test_null",
 *   label = @Translation("Test Null"),
 *   description = @Translation("Does nothing"),
 *   directions = {},
 * )
 */
class TestNull extends StateActionBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $plugin_form, FormStateInterface $form_state) {
    // Method has no documentation!.
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState(AccountInterface $account, string $state, array $parameters) {
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperability(string $direction, string $state, AccountInterface $account,  ...$parameters): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(AccountInterface $account): ?Url {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    return 'Change';
  }

}
