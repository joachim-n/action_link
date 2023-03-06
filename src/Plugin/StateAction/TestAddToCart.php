<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\Routing\Route;

/**
 * Proof of concept of a state action for adding a product to a cart.
 *
 * @StateAction(
 *   id = "test_add_to_cart",
 *   label = @Translation("Add to cart"),
 *   description = @Translation("TODO"),
 *   parameters = {
 *     "dynamic" = {
 *       "entity"
 *     },
 *     "configuration" = {
 *     },
 *   },
 *   directions = {
 *     "add" = "add",
 *     "remove" = "remove",
 *   },
 *   states = {},
 * )
 */
class TestAddToCart extends StateActionBase {

  use StringTranslationTrait;

  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    // $plugin_form = parent::buildConfigurationForm($element, $form_state);

    $plugin_form['markup'] = [
      '#markup' => 'one',
    ];

    return $plugin_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
    $count = \Drupal::state()->get('test_add_to_cart:count', 0);

    if ($direction == 'add') {
      return $count + 1;
    }
    else {
      return $count ?
        $count - 1 :
        NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState(AccountInterface $account, string $state, array $parameters) {
    \Drupal::state()->set('test_add_to_cart:count', $state);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    $count = \Drupal::state()->get('test_add_to_cart:count', 0);

    if ($direction == 'add') {
      return $count ?
        $this->t('Add to cart (@count in cart)', [
          '@count' => $count,
        ]) :
        $this->t('Add to cart');
    }
    else {
      return $count > 1 ?
        $this->t('Remove 1 from cart (@count in cart)', [
          '@count' => $count,
        ]) :
        $this->t('Remove from cart');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    // @todo Implement properly!
    return AccessResult::allowed();
  }

  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    // @todo Implement properly!
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function convertParametersForRoute(array $parameters): array {
    $parameters = parent::convertParametersForRoute($parameters);

    // Convert the entity parameter to an entity ID.
    // TODO: this needs to be able to complain if a param is bad.
    // e.g. no node exists.
    $parameters['entity'] = $parameters['entity']->id();

    return $parameters;
  }

  public function getActionRoute(ActionLinkInterface $action_link): Route {
    $route = parent::getActionRoute($action_link);

    $route->setOption('parameters', [
      'entity' => [
        // @todo Get correct entity type from configuration.
        'type' => 'entity:node',
      ],
    ]);

    return $route;
  }

}
