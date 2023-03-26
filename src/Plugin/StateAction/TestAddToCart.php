<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
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
    // $element = parent::buildConfigurationForm($element, $form_state);

    $element['markup'] = [
      '#markup' => 'one',
    ];

    return $element;
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
  public function advanceState(AccountInterface $account, string $state) {
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
  public function getMessage(string $direction, string $state, ...$parameters): string {
    return match ($direction) {
      'add' => $this->t('One item added to the cart'),
      'remove' => $this->t('One item removed from the cart'),
    };
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
    $parameters['entity'] = $parameters['entity']->id();

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
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
