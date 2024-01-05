<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Test state action for directions.
 *
 * @StateAction(
 *   id = "test_directions",
 *   label = @Translation("Test directions"),
 *   directions = {
 *     "up" = "up",
 *     "down" = "down",
 *   },
 * )
 */
class TestDirections extends StateActionBase {

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
    $state = \Drupal::state()->get('test_directions:state', 0);

    $next_state = match ($direction) {
      'up' => $state + 1,
      'down' => $state - 1,
    };

    return $next_state;
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState(AccountInterface $account, string $state) {
    \Drupal::state()->set('test_directions:state', $state);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    return "$direction to $state";
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
  public function checkPermissionAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandStateAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account): AccessResult {
    return AccessResult::allowed();
  }

}
