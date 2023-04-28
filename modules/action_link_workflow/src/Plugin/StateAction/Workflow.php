<?php

namespace Drupal\action_link_workflow\Plugin\StateAction;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\StateAction\StateActionBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * State action plugin for controlling a workflow.
 *
 * Directions are not declared in the annotation, but are derived from the
 * associated workflow entity's transitions.
 *
 * @StateAction(
 *   id = "workflow",
 *   label = @Translation("Workflow"),
 *   description = @Translation("Workflow"),
 *   directions = {},
 *   dynamic_parameters = {
 *     "entity",
 *   },
 *   deriver = "Drupal\action_link_workflow\Plugin\Derivative\WorkflowActionLinkDeriver"
 * )
 */
class Workflow extends StateActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The workflow ID.
   *
   * @var string
   */
  protected $workflowId;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Creates a Workflow instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->workflowId = $this->getDerivativeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getDirections(): array {
    // Directions are declared as the workflow's transitions.
    /** @var \Drupal\workflows\WorkflowInterface */
    $workflow = $this->entityTypeManager->getStorage('workflow')->load($this->workflowId);

    $transitions = $workflow->getTypePlugin()->getTransitions();
    return array_map(function ($transition) {
      /** @var \Drupal\workflows\TransitionInterface $transition */
      return $transition->label();
    }, $transitions);


    // dsm($workflow->getTypePlugin()->getStates());
    // dsm($workflow->getTypePlugin()->getTransitions());
    return $this->pluginDefinition['directions'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getNextStateName(string $direction, AccountInterface $user, EntityInterface $entity = NULL): ?string {
    // @todo Get current state from entity: configuration needs to tell us which
    // field.
    $current_state = $entity->moderation_state->value;

    /** @var \Drupal\workflows\WorkflowInterface */
    $workflow = $this->entityTypeManager->getStorage('workflow')->load($this->workflowId);

    $transitions = $workflow->getTypePlugin()->getTransitionsForState($current_state);
    if (!isset($transitions[$direction])) {
      return NULL;
    }

    $transition = $workflow->getTypePlugin()->getTransition($direction);

    return $transition->to()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    $workflow = $this->entityTypeManager->getStorage('workflow')->load($this->workflowId);
    $transition = $workflow->getTypePlugin()->getTransition($direction);
    return $transition->label();
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
  public function advanceState($account, $state, ...$parameters) {
    // Undocumented function.
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    // @todo Implement this properly.
    return AccessResult::allowed();
  }

  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account): AccessResult {
    // @todo Implement this properly.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(string $direction, string $state, ...$parameters): string {
    // @todo Implement this properly.
    return 'Workflow state changed.';
  }

  /**
   * {@inheritdoc}
   */
  protected function convertParametersForRoute(array $parameters): array {
    $parameters = parent::convertParametersForRoute($parameters);

    // Convert the entity parameter to an entity ID.
    if (is_object($parameters['entity'])) {
      $parameters['entity'] = $parameters['entity']->id();
    }

    return $parameters;
  }

  public function getActionRoute(ActionLinkInterface $action_link): Route {
    $route = parent::getActionRoute($action_link);

    $route->setOption('parameters', [
      'entity' => [
        // @todo Set the entity based on configuration.
        'type' => 'entity:node',
      ],
    ]);

    return $route;
  }

}
