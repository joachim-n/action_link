<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\Core\Session\AccountInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Url;

/**
 * Test plugin which returns access based on state value.
 *
 * @StateAction(
 *   id = "test_mocked_access",
 *   label = @Translation("Test mocked access"),
 *   description = @Translation("Mocked access"),
 *   directions = {
 *     "change",
 *   },
 * )
 */
class TestMockedAccess extends StateActionBase {

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
    $access = \Drupal::state()->get('test_mocked_access:access');
    return match ($access) {
      NULL => AccessResult::neutral(),
      FALSE => AccessResult::forbidden(),
      TRUE => AccessResult::allowed(),
    };
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

  /**
   * {@inheritdoc}
   */
  public function getMessage(string $direction, string $state, ...$parameters): string {
    return 'Changed';
  }

}
