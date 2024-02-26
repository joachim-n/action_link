<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Test action which has no directions and no states.
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
  public function getNextStateName(string $direction, AccountInterface $user): ?string {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState(AccountInterface $account, string $state) {
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperability(ActionLinkInterface $action_link): bool {
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
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    return 'Change';
  }

}
