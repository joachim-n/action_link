<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\Core\Session\AccountInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;

/**
 * Test action which is always usable.
 *
 * @StateAction(
 *   id = "test_always",
 *   label = @Translation("Test Always"),
 *   description = @Translation("Test Always"),
 *   directions = {
 *     "change",
 *   },
 * )
 */
class TestAlways extends StateActionBase {

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user): ?string {
    return 'cake';
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
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    return AccessResult::allowed();
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
