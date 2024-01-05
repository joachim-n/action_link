<?php

namespace Drupal\action_link_poc\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\action_link\Plugin\StateAction\ToggleGeometryTrait;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Action link to subscribe to an entity.
 *
 * Proof-of-concept only, needs more work.
 *
 * This takes a generic entity, and so needs two dynamic parameters, because in
 * the path parameters we need the entity type ID as well as the entity ID. This
 * could be made more elegant with a route enhancer which takes both values in
 * a single path parameter.
 *
 * @StateAction(
 *   id = "poc_subscribe",
 *   label = @Translation("Subscribe (proof-of-concept)"),
 *   description = @Translation("Action link to subscribe to an entity."),
 *   dynamic_parameters = {
 *     "entity_type",
 *     "entity_id",
 *   },
 *   directions = {
 *     "toggle" = "toggle",
 *   },
 *   states = {
 *     "sub",
 *     "unsub",
 *   },
 *  )
 */
class PocSubscribe extends StateActionBase implements ContainerFactoryPluginInterface, ConfigurableInterface, PluginFormInterface {

  use StringTranslationTrait;
  use ToggleGeometryTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
      $container->get('entity_type.manager'),
      $container->get('state'),
    );
  }

  /**
   * Creates a PocSubscribe instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    StateInterface $state
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $element['texts'] = [
      '#tree' => TRUE,
    ];
    $element['texts'] = $this->buildTextsConfigurationForm($element['texts'], $form_state);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, string $entity_type = NULL, string $entity_id = NULL): ?string {
    $value = \Drupal::state()->get(implode(':', ['poc_subscribe', $entity_type, $entity_id]), 'unsub');

    return match ($value) {
      'sub' => 'unsub',
      'unsub'  => 'sub',
    };
  }

  /**
   * {@inheritdoc}
   */
  public function advanceState($account, $state, string $entity_type = NULL, string $entity_id = NULL) {
    \Drupal::state()->set(implode(':', ['poc_subscribe', $entity_type, $entity_id]), $state);
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
  public function checkOperandStateAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account): AccessResult {
    // @todo Implement properly!
    return AccessResult::allowed();
  }

}
