<?php

namespace Drupal\action_link_poc\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Action link to add a product to a shopping cart.
 *
 * Proof-of-concept only, needs more work.
 *
 * The entity parameter would represent the product, but it's not used in the
 * demo logic.
 *
 * @StateAction(
 *   id = "poc_add_to_cart",
 *   label = @Translation("Add to cart (proof-of-concept)"),
 *   description = @Translation("Action link to add a product to a shopping cart."),
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
class PocAddToCart extends StateActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state'),
    );
  }

  /**
   * Creates a PocAddToCart instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    StateInterface $state
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
    $count = \Drupal::state()->get('poc_add_to_cart:count', 0);

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
  public function advanceState(AccountInterface $account, string $state, EntityInterface $entity = NULL) {
    \Drupal::state()->set('poc_add_to_cart:count', $state);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    $count = \Drupal::state()->get('poc_add_to_cart:count', 0);

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

  /**
   * {@inheritdoc}
   */
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
