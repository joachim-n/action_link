<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;

/**
 * Test plugin which returns access and operability based on state value.
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
    $state = \Drupal::state()->get('test_mocked_control:next_state', NULL);
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
  public function checkOperability(ActionLinkInterface $action_link, ...$parameters): bool {
    $operability = \Drupal::state()->get('test_mocked_control:operability', FALSE);
    return $operability;
  }

  /**
   * {@inheritdoc}
   */
  public function checkPermissionAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    $access = \Drupal::state()->get('test_mocked_control:permission_access', AccessResult::neutral());
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    $access = \Drupal::state()->get('test_mocked_control:operand_access', AccessResult::neutral());
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
