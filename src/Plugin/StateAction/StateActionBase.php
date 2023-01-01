<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Base class for State Action plugins.
 *
 * Remove methods for ConfigurableInterface when
 * https://www.drupal.org/project/drupal/issues/2852463 gets in.
 */
abstract class StateActionBase extends PluginBase implements StateActionInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $this_id, $this_definition) {
    parent::__construct($configuration, $this_id, $this_definition);

    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * Gets a render array of all the operable links for the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get links for. TODO ARGH WANT TO ALLOW EASY DEFAULT TO MEAN CURRENT USER!
   * @param [type] ...$parameters
   */
  public function buildLinkSet(ActionLinkInterface $action_link, AccountInterface $user, ...$parameters) {
    // can't do this yet as it's skipping the $direction param, need to pass
    // $parameters to the plugin as unpacking named arguments -- need PHP 8.1

    $directions = $this->getDirections();

    $build = [];

  // else, NEED TO KNOW how to add $direction to $parameters!
    // $definition = $this->getPluginDefinition();
    // $dynamic_parameters = $definition['parameters']['dynamic'];
    // The plugin manager has checked that the 'direction' parameter exists
    // at discovery time.
    // $direction_parameter_position = array_search('direction', $dynamic_parameters);

    foreach ($directions as $direction) {
      // $link_parameters = $parameters;
      // array_splice($link_parameters, $direction_parameter_position, 0, $direction);

      $build[$direction] = $this->getLink($action_link, $direction, $user, ...$parameters)?->toRenderable();
    }

    return array_filter($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user, ...$parameters): ?Link {
    // validate param count!
    $this->validateParameters($parameters);

    $route_parameters = $this->convertParametersForRoute($parameters);
    // ARGH convert a node entity to an ID??

    // TODO - get labels!

    if ($next_state = $this->getNextStateName($direction, $user, ...$parameters)) {
      $label = $this->getLinkLabel($direction, $next_state, ...$parameters);

      $url = Url::fromRoute('action_link.action_link', [
        'action_link' => $action_link->id(),
        'direction' => $direction,
        'state' => $next_state,
        'user' => $user->id(),
        'parameters' => implode('/', $route_parameters),
      ]);
      return Link::fromTextAndUrl($label, $url);
    }
  }

  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    // $permission = ARGH we need the action link!
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperability(string $direction, string $state, AccountInterface $account, ...$parameters): bool {
    // Check the desired state is the next state.

    // ARGH this won't work for generating links, it's just tautology!!!


    $next_state = $this->getNextStateName($direction, $account, ...$parameters);

    return ($next_state == $state);
  }

  // implemented in traits.
  // public function getLinkLabel(string $state, ...$parameters): string {
  //   // try states first, then direcions.

  // }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  public function copyFormValuesToEntity($entity, array &$form, FormStateInterface $form_state) {
  }

  // overridden by traits.
  public function getMessage(string $direction, string $state, ...$parameters): string {
  }
    //   // try states first, then direcions.

  //


  public function getDirections() {
    return $this->pluginDefinition['directions'] ?? NULL;
  }

  public function validateParameters(array $parameters) {
    if (count($parameters) != count($this->pluginDefinition['parameters']['dynamic'])) {
      throw new \LogicException(sprintf("State action plugin %s expects %s parameters, got %s",
        $this->getPluginId(),
        count($this->pluginDefinition['parameters']['dynamic']),
        count($parameters),
      ));
    }
  }

  /**
   * Gets a specific parameter that was passed to the action link.
   *
   * @param array $parameters
   *   The original array of parameters.
   * @param string $name
   *   The name of the parameter to get.
   *
   * @return mixed
   *   The parameter value from the array.
   */
  protected function getDynamicParameter(array $parameters, string $name) {
    $dynamic_parameters_definition = $this->pluginDefinition['parameters']['dynamic'];

    $parameter_position = array_search($name, $dynamic_parameters_definition);
    return $parameters[$parameter_position];
  }


  // todo interface.
  public function convertParametersForRoute(array $parameters): array {
    $dynamic_parameter_indexes = array_flip($this->pluginDefinition['parameters']['dynamic']);

    if (isset($dynamic_parameter_indexes['entity'])) {
      $index = $dynamic_parameter_indexes['entity'];
      $parameters[$index] = $parameters[$index]->id();
    }

    return $parameters;
  }


  /**
   * Upcasts route parameters.
   *
   * This is necessary because dynamic parameters which are specific to a plugin
   * can't be declared on the route.
   *
   * (This can't be done with dynamic routes, declaring one for each state
   * action plugin, because they would need to share a controller with a
   * variadic parameter which isn't possible: see
   * https://www.drupal.org/project/drupal/issues/3329122.)
   *
   * @param array $parameters
   *   The dynamic parameters.
   *
   * @return array
   *   The parameters with objects upcased where necessary.
   */
  public function upcastRouteParameters(array $parameters): array {
    $this->validateParameters($parameters);

    $entity_type_manager = \Drupal::service('entity_type.manager');

    $dynamic_parameter_indexes = array_flip($this->pluginDefinition['parameters']['dynamic']);

    if (isset($dynamic_parameter_indexes['entity'])) {
      $index = $dynamic_parameter_indexes['entity'];
      // TODO: validate and throw?
      $parameters[$index] = $entity_type_manager->getStorage($this->configuration['entity_type_id'])->load($parameters[$index]);
    }

    return $parameters;
  }

}
