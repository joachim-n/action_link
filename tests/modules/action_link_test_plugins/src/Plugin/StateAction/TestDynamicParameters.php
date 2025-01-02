<?php

namespace Drupal\action_link_test_plugins\Plugin\StateAction;

use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Test state action for dynamic paramers.
 *
 * Defines just a single direction, so internally this is actually a 2-state
 * loop rather than a toggle.
 *
 * @StateAction(
 *   id = "test_dynamic_parameters",
 *   label = @Translation("Test dynamic parameters"),
 *   dynamic_parameters = {
 *     "entity"
 *   },
 *   directions = {
 *     "toggle" = "toggle",
 *   },
 *   states = {
 *     "true",
 *     "false",
 *   },
 * )
 */
class TestDynamicParameters extends StateActionBase {

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
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
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    return '';
  }

}
