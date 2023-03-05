<?php

namespace Drupal\action_link_workflow\Plugin\StateAction;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;

/**
 * TODO: class docs.
 *
 * @StateAction(
 *   id = "workflow",
 *   label = @Translation("Workflow"),
 *   description = @Translation("Workflow"),
 *   directions = {},
 *   parameters = {},
 *   deriver = "Drupal\action_link_workflow\Plugin\Derivative\WorkflowActionLinkDeriver"
 * )
 */
class Workflow extends StateActionBase {

  // /**
  //  * {@inheritdoc}
  //  */
  // public function getLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user): ?Link {
  //   // Gets the action link for a specific direction.
  // }

  public function getLinkLabel(string $direction, string $state, ...$parameters): string {

    return 'label!';
  }

  /**
   * {@inheritdoc}
   */
  public function XbuildConfigurationForm(array $element, FormStateInterface $form_state) {
    // Method has no documentation!.
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user): ?string {
    // Gets the next state for the given parameters, or NULL if there is none.
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState($account, $state, $parameters) {
    // Undocumented function.
  }

  /**
   * {@inheritdoc}
   */
  public function XgetDynamicParametersFromRouteMatch(RouteMatchInterface $route_match): array {
    // Gets the dynamic parameters from the route match.
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
  public function getMessage(string $direction, string $state, ...$parameters): string {
    return 'boop';
  }


  // $this->getTypePlugin()->getStates

}
