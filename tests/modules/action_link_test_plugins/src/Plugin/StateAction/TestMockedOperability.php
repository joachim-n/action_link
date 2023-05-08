<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;

/**
 * Test plugin which returns operability based on state value.
 *
 * @StateAction(
 *   id = "test_mocked_operability",
 *   label = @Translation("Test mocked operability"),
 *   description = @Translation("Mocked operability"),
 *   directions = {
 *     "change" = "change",
 *   },
 * )
 */
class TestMockedOperability extends StateActionBase {

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user): ?string {
    return 'cake';
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
    // @todo Need to mock getNextStateName() instead.
    $operability = \Drupal::state()->get('test_mocked_operability:operability');
    return $operability;
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account): AccessResult {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    return 'Change';
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(string $direction, string $state, ...$parameters): string {
    return 'Changed';
  }

}
