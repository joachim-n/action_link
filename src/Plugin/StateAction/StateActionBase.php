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
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Base class for State Action plugins.
 *
 * Use one of the traits to provide the abstract methods.
 *
 * @todo Remove methods for ConfigurableInterface when
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
    // Validate the number of dynamic parameters. This must be done before they
    // are validated by the specific plugin class.
    $dynamic_parameter_names = $this->getDynamicParameterNames();
    if (count($parameters) != count($dynamic_parameter_names)) {
      throw new \ArgumentCountError(sprintf("State action plugin %s expects %s dynamic parameters (%s), got %s",
        $this->getPluginId(),
        count($dynamic_parameter_names),
        implode(', ', $dynamic_parameter_names),
        count($parameters),
      ));
    }

    // Get the associative indexes for the dynamic parameters.
    $named_parameters = $this->getDynamicParametersByName($parameters);

    // Validate parameters.
    $this->validateParameters($named_parameters);

    $build = [];

    if (!$this->checkOperability($action_link, ...$parameters)) {
      return $build;
    }

    // Downcast dynamic parameters.
    $scalar_parameters = $this->convertParametersForRoute($named_parameters);

    $directions = $this->getDirections();

    foreach ($directions as $direction => $direction_label) {
      if ($link = $this->getLink($action_link, $direction, $user, $named_parameters, $scalar_parameters)) {
        $build[$direction] = [
          '#theme' => 'action_link',
          '#link' => $link->toRenderable(),
          '#direction' => $direction,
          '#user' => $user,
          '#dynamic_parameters' => $parameters,
          '#attributes' => new Attribute([
            'class' => [
              'action-link',
              'action-link-id-' . $action_link->id(),
              'action-link-plugin-' . $this->getPluginId(),
            ],
          ]),
        ];

        // Set nofollow to prevent search bots from crawling anonymous flag links.
        $build[$direction]['#link']['#attributes']['rel'][] = 'nofollow';
      }
    }

    // Allow the link style plugin for this action link entity to modify the
    // render array for the links.
    if ($build) {
      $action_link->getLinkStylePlugin()->alterLinksBuild($build, $action_link, $user, $named_parameters, $scalar_parameters);
    }

    return $build;
  }

  // TODO!
  public function buildSingleLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user, ...$parameters): array {
    $build = [];

    // TODO needs $this->checkOperability($action_link, ...$named_parameters)

    // Validate the number of dynamic parameters. This must be done before they
    // are validated by the specific plugin class.
    $dynamic_parameter_names = $this->getDynamicParameterNames();
    if (count($parameters) != count($dynamic_parameter_names)) {
      throw new \ArgumentCountError(sprintf("State action plugin %s expects %s dynamic parameters (%s), got %s",
        $this->getPluginId(),
        count($dynamic_parameter_names),
        implode(', ', $dynamic_parameter_names),
        count($parameters),
      ));
    }

    // TODO: outdated call!
    if ($link = $this->getLink($action_link, $direction, $user, ...$parameters)) {
      $build = [
        '#theme' => 'action_link',
        '#link' => $link->toRenderable(),
        '#direction' => $direction,
        '#user' => $user,
        '#dynamic_parameters' => $parameters,
        '#attributes' => new Attribute(['class' => []]),
      ];

      // Set nofollow to prevent search bots from crawling anonymous flag links.
      $build['#link']['#attributes']['rel'][] = 'nofollow';
    }
    // ARGH need AJAX alteration and KEY IS WRONG!
    // TODO!

    return $build;
  }

  /**
   * Gets a link object for the action link.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction for the link.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get the link for.
   * @param array $named_parameters
   *   An array of the dynamic parameters for the link, keyed by the parameter
   *   name.
   * @param array $scalar_parameters
   *   An array of the dynamic parameters for the link, keyed by the parameter
   *   name, with any object values downcasted to the scalar values they would
   *   have in the route path.
   *
   * @return \Drupal\Core\Link
   *   A link object, or NULL if there is no valid link for the given
   *   parameters.
   */
  protected function getLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user, $named_parameters, $scalar_parameters): ?Link {
    if ($next_state = $this->getNextStateName($direction, $user, ...$named_parameters)) {
      $label = $this->getLinkLabel($direction, $next_state, ...$named_parameters);

      $route_parameters = [
        'action_link' => $action_link->id(),
        'link_style' => $action_link->getLinkStylePlugin()->getPluginId(),
        'direction' => $direction,
        'state' => $next_state,
        'user' => $user->id(),
      ];

      // Add the dynamic parameters to the route parameters.
      $route_parameters += $scalar_parameters;

      $url = Url::fromRoute('action_link.action_link.' . $action_link->id(), $route_parameters);

      // Check access for the current user.
      if ($url->access()) {
        // FUCK WHY THIS FAILING???
        return Link::fromTextAndUrl($label, $url);
      }

      // @todo Show a link to log in if the user doesn't have access but an
      // authenticated user would. Determining this appears to be rather
      // complicated.

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

  /**
   * {@inheritdoc}
   */
  abstract public function getLinkLabel(string $direction, string $state, ...$parameters): string;

  /**
   * {@inheritdoc}
   */
  public function checkOperability(ActionLinkInterface $action_link, ...$parameters): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkPermissionAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {

    // check getDirections and params.

    // // NO WAIT, direction and states!
    // if (!$account->hasPermission("use {$this->id()} action links"))
    // OR specific plugin permission(s)
    //
    //   // return AccessResult::forbidden()->addCacheableDependency($account);
    // }

    // two permissions to pass:
    //  A. 'can you use this action link?'
    //  B. 'do you have access to the thing the AL wants to change?'

    // TODO!
    return AccessResult::neutral();
  }

  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    return AccessResult::neutral();
  }

  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $plugin_form = [];

    return $plugin_form;
  }

  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  public function copyFormValuesToEntity($entity, array &$form, FormStateInterface $form_state) {
  }

  // overridden by traits.
  public function getMessage(string $direction, string $state, ...$parameters): string {
  }
    //   // try states first, then direcions.

  //


  /**
   * Gets the directions for this plugin.
   *
   * @return array
   *   An array of the directions defined in the plugin definition. Keys are
   *   direction machine names, and values are the labels.
   */
  public function getDirections(): array {
    return $this->pluginDefinition['directions'] ?? [];
  }

  /**
   * Gets the associative indexes for the dynamic parameters.
   *
   * @param array $parameters
   *   The dynamic parameters as a numeric array.
   *
   * @return
   *   The same parameters, keyed by their name as declared in the plugin's
   *   annotation.
   */
  public function getDynamicParametersByName(array $parameters) {
    $named_parameters = [];
    $dynamic_parameters_definition = $this->getDynamicParameterNames();
    foreach ($dynamic_parameters_definition as $parameter_name) {
      $named_parameters[$parameter_name] = array_shift($parameters);
    }
    return $named_parameters;
  }

  /**
   * Gets the names of the plugin's dynamic parameters.
   *
   * @return array
   *   An array of names.
   */
  public function getDynamicParameterNames(): array {
    return $this->pluginDefinition['parameters']['dynamic'] ?? [];
  }

  /**
   * Validates the dynamic parameters.
   *
   * @param array $parameters
   *   The dynamic parameters, keyed by the names defined in the plugin
   *   annotation.
   */
  public function validateParameters(array $parameters) {

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
    $dynamic_parameters_definition = $this->getDynamicParameterNames();

    $parameter_position = array_search($name, $dynamic_parameters_definition);
    return $parameters[$parameter_position];
  }



  /**
   * Downcasts object parameters for use in routes and identifiers.
   *
   * @param array $parameters
   *   The array of dynamic parameters.
   *
   * @return array
   *   The array of raw parameters.
   */
  public function convertParametersForRoute(array $parameters): array {
    // TODO: PHP 8.1 sanity check with array_is_list.
    // Do nothing by default.
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicParametersFromRouteMatch(RouteMatchInterface $route_match): array {
    $dynamic_parameters = [];
    $parameters = $route_match->getParameters()->all();

    foreach ($this->getDynamicParameterNames() as $name) {
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

    $dynamic_parameters_definition = $this->getDynamicParameterNames();
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

  /**
   * {@inheritdoc}
   */
  public function getStateActionPermissions(ActionLinkInterface $action_link): array {
    // Overridden by traits.
    return [];
  }

}
