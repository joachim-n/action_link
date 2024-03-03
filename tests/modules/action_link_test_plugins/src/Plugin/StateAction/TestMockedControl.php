<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Test plugin which returns access and operability based on state value.
 *
 * Uses the following state values as return values from methods:
 *  - test_mocked_control:getNextStateName
 *  - test_mocked_control:checkOperability
 *  - test_mocked_control:checkPermissionStateAccess
 *  - test_mocked_control:checkOperandGeneralAccess
 *  - test_mocked_control:checkOperandStateAccess
 *
 * When advanceState() is called, the test_mocked_control:set_state state key
 * is set with the new state.
 *
 * @StateAction(
 *   id = "test_mocked_control",
 *   label = @Translation("Test mocked control"),
 *   description = @Translation("Mocked control"),
 *   directions = {
 *     "change" = "change",
 *   },
 * )
 */
class TestMockedControl extends StateActionBase {

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user): ?string {
    $state = \Drupal::state()->get('test_mocked_control:getNextStateName', NULL);
    return $state;
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState(AccountInterface $account, string $state) {
    \Drupal::state()->set('test_mocked_control:set_state', $state);
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperability(ActionLinkInterface $action_link): bool {
    $operability = \Drupal::state()->get('test_mocked_control:checkOperability', FALSE);
    return $operability;
  }

  /**
   * {@inheritdoc}
   */
  public function checkPermissionStateAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    $access = \Drupal::state()->get('test_mocked_control:checkPermissionStateAccess', AccessResult::neutral());
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandGeneralAccess(ActionLinkInterface $action_link, AccountInterface $account): AccessResult {
    $access = \Drupal::state()->get('test_mocked_control:checkOperandGeneralAccess', AccessResult::neutral());
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandStateAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account): AccessResult {
    $access = \Drupal::state()->get('test_mocked_control:checkOperandStateAccess', AccessResult::neutral());
    return $access;
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
