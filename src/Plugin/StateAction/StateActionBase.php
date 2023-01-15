<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

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
   * {@inheritdoc}
   */
  public function buildLinkSet(ActionLinkInterface $action_link, AccountInterface $user, ...$parameters): array {
    $directions = $this->getDirections();

    $build = [];

    foreach ($directions as $direction) {
      if ($link = $this->getLink($action_link, $direction, $user, ...$parameters)) {
        $build[$direction] = $link->toRenderable();
      }
    }

    // Allow the link style plugin for this action link entity to modify the
    // render array for the links.
    if ($build) {
      $action_link->getLinkStylePlugin()->alterLinksBuild($build, $action_link, $user, ...$parameters);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user, ...$parameters): ?Link {
    // validate param count!
    $this->validateParameters($parameters);

    // todo access!

    // TODO - get labels!

    if ($next_state = $this->getNextStateName($direction, $user, ...$parameters)) {
      $label = $this->getLinkLabel($direction, $next_state, ...$parameters);

      $route_parameters = [
        'action_link' => $action_link->id(),
        'link_style' => $action_link->getLinkStylePlugin()->getPluginId(),
        'direction' => $direction,
        'state' => $next_state,
        'user' => $user->id(),
      ];

      $parameters = $this->convertParametersForRoute($parameters);
      $dynamic_parameters_definition = $this->pluginDefinition['parameters']['dynamic'];
      foreach ($dynamic_parameters_definition as $parameter_name) {
        $route_parameters[$parameter_name] = array_shift($parameters);
      }

      $url = Url::fromRoute('action_link.action_link.' . $action_link->id(), $route_parameters);

      // Check access for the current user.
      if ($url->access()) {
        // FUCK WHY THIS FAILING???
        return Link::fromTextAndUrl($label, $url);
      }

      // // TODO: If logged out, and an authenticated user would have access, show a log
      // // in CTA? HOW?
      // if (\Drupal::currentUser()->isAnonymous()) {
      //   $dummy_authenticated_user = User::create([
      //     'roles' => [
      //       AccountInterface::AUTHENTICATED_ROLE,
      //     ],
      //   ]);
      //  DOESN"T WORK -- isAuthenticated checks the uid!
      //   dump($dummy_authenticated_user->isAuthenticated());


      //   // doesn't work - wrong user gets to the controller access. HOW?
      //   if ($url->access($dummy_authenticated_user)) {
      //     return Link::fromTextAndUrl("Log in!", $url);
      //   }
      // }

    }

    return NULL;
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
   * {@inheritdoc}
   */
  public function getDynamicParametersFromRouteMatch(RouteMatchInterface $route_match): array {
    $dynamic_parameters = [];
    $parameters = $route_match->getParameters()->all();
    foreach ($this->pluginDefinition['parameters']['dynamic'] as $name) {
      $dynamic_parameters[] = $parameters[$name];
    }

    return $dynamic_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getActionRoute(ActionLinkInterface $action_link): Route {
    $action_link_id = $action_link->id();

    // Hardcode the action link ID in the path, so each route has a distinct
    // path in the routing table.
    $path = "/action-link/$action_link_id/{link_style}/{direction}/{state}/{user}";

    $dynamic_parameters_definition = $this->pluginDefinition['parameters']['dynamic'];
    foreach ($dynamic_parameters_definition as $parameter_name) {
      $path .= '/{' . $parameter_name . '}';
    }

    $route = new Route(
      $path,
      [
        '_controller' => '\Drupal\action_link\Controller\ActionLinkController::action',
        // Pass the action link ID as a parameter to the controller and access
        // callbacks.
        'action_link' => $action_link_id,
      ],
      [
        '_custom_access'  => '\Drupal\action_link\Controller\ActionLinkController::access',
        '_csrf_token' => 'TRUE',
      ],
    );

    return $route;
  }

}
