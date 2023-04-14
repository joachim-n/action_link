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
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
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
    return $this->stringsDefaultConfiguration();
  }

  /**
   * Gets default configuration for this plugin's strings.
   *
   * These are defined in a separate method so it can be overridden by geometry
   * traits.
   *
   * @return array
   *   An associative array with the default configuration.
   */
  protected function stringsDefaultConfiguration() {
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
    // Merge in default configuration.
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * Gets the plugin configuration form.
   *
   * This implements
   * \Drupal\Core\Plugin\PluginFormInterface::buildConfigurationForm() for child
   * classes which implement that interface. This in particular inserts the
   * string form elements from traits.
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    // Note that default values are filled in from the plugin configuration
    // by the 'action_plugin' form element's class which calls this.

    // If a trait supplies a method for configuring strings, add an element for
    // it.
    if (method_exists($this, 'buildTextsConfigurationForm')) {
      $element['texts'] = [
        '#tree' => TRUE,
      ];
      $element['texts'] = $this->buildTextsConfigurationForm($element['texts'], $form_state);

      // Show the token browser if there are label form elements and if token
      // module is present.
      if (\Drupal::moduleHandler()->moduleExists('token')) {
        $element['token_help'] = [
          '#theme' => 'token_tree_link',
          // We can't narrow down types as in some cases they relevant types
          // depend on the currently selected configuration.
          '#token_types' => 'all',
        ];
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLinkSet(ActionLinkInterface $action_link, AccountInterface $user, ...$parameters): array {
    $directions = $this->getDirections();

    return $this->doBuildLinkSet($action_link, $user, $directions, ...$parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function buildSingleLink(ActionLinkInterface $action_link, string $direction, AccountInterface $user, ...$parameters): array {
    $directions = $this->getDirections();
    $direction_array = [$direction => $directions[$direction]];

    return $this->doBuildLinkSet($action_link, $user, $direction_array, ...$parameters);
  }

  /**
   * Common code for static::buildLinkSet() and static::buildSingleLink().
   */
  protected function doBuildLinkSet(ActionLinkInterface $action_link, AccountInterface $user, $directions, ...$parameters): array {
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
    $named_parameters = $this->getDynamicParameterValuesByName($parameters);

    // Validate parameters.
    $this->validateParameters($named_parameters);

    $build = [];

    // If the action link isn't operable, show nothing.
    if (!$this->checkOperability($action_link, ...$parameters)) {
      return $build;
    }

    // Downcast dynamic parameters.
    $scalar_parameters = $this->convertParametersForRoute($named_parameters);
    assert(empty(array_filter($scalar_parameters, 'is_object')), 'Call to convertParametersForRoute() should downcast all objects');

    foreach ($directions as $direction => $direction_label) {
      $link = $this->buildLink($action_link, $direction, $user, $scalar_parameters, ...$parameters);
      if ($link) {
        $build[$direction] = $link;
      }
    }

    // Allow the link style plugin for this action link entity to modify the
    // render array for the links.
    if ($build) {
      $action_link->getLinkStylePlugin()->alterLinksBuild($build, $action_link, $user, $named_parameters, $scalar_parameters);
    }

    return $build;
  }

  /**
   * Gets the build array for a single link.
   *
   * This creates a build array rather than return a Link object, because in
   * some cases we want an empty build array with no link, and in some we want
   * nothing at all, and returning a Link object wouldn't capture that
   * distinction.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param array $scalar_parameters
   *   The dynamic parameters, downcasted to scalar values, keyed by parameter
   *   name.
   * @param ...$parameters
   *   The dynamic parameters.
   *
   * @return array|null
   *   A build array for the link, or NULL if nothing should be output. The
   *   build array may itself not contain a link and show nothing. The logic is:
   *    - No access: NULL.
   *    - No operability: NULL.
   *    - No reachable state: build array with no link.
   *    - Everything ok: build array with a link.
   */
  protected function buildLink($action_link, $direction, $user, $scalar_parameters, ...$parameters): ?array {
    // Only NULL means there is no valid next state; a string such as '0' is
    // a valid state.
    $next_state = $this->getNextStateName($direction, $user, ...$parameters);
    $reachable = !is_null($next_state);

    if ($reachable) {
      // Check access if the state is reachable. If the state is not reachable,
      // we can't check access but output an empty link anyway.
      $access = $action_link->checkAccess($direction, $next_state, $user, ...$parameters);
      if (!$access->isAllowed()) {
        // @todo Show a link to log in if the user doesn't have access but an
        // authenticated user would. Determining this appears to be rather
        // complicated, as we'd need to mock a user object to pass to access
        // checks, but isAuthenticated() works by checking for the uid.
        return NULL;
      }
    }

    $route_parameters = [
      'action_link' => $action_link->id(),
      'link_style' => $action_link->getLinkStylePlugin()->getPluginId(),
      'direction' => $direction,
      'state' => $next_state,
      'user' => $user->id(),
    ];

    // Add the dynamic parameters to the route parameters.
    $route_parameters += $scalar_parameters;

    if ($reachable) {
      $label = $action_link->getLinkLabel($direction, $next_state, ...$parameters);

      $url = Url::fromRoute('action_link.action_link.' . $action_link->id(), $route_parameters);
      $link = Link::fromTextAndUrl($label, $url);
    }

    // We output an action link even if there is no actual link to show because
    // the state is not reachable in the given direction. This is so that if a
    // link in another direction is used over AJAX, and causes the inactive
    // direction to become available, then the empty SPAN is replaced by the
    // AJAX with an active link. For example, suppose an action link adds a
    // product to a shopping cart, with 'add' and 'remove' directions. When
    // the cart is empty, only the 'add' direction link shows. Clicking this
    // link takes the site to a state where the 'remove' direction is now
    // valid and the link for that should show. Therefore, the AJAX
    // replacement that occurs when the user clicks the 'add' link must
    // replace both directions. Having an empty SPAN for the 'remove'
    // direction means there is somewhere for the updated 'remove' link to go.
    $build = [
      '#theme' => 'action_link',
      '#link' => $reachable ? $link->toRenderable() : [],
      '#direction' => $direction,
      '#user' => $user,
      '#dynamic_parameters' => $parameters,
      '#attributes' => new Attribute([
        'class' => [
          'action-link',
          'action-link-id-' . $action_link->id(),
          'action-link-plugin-' . $this->getPluginId(),
          'action-link-' . ($reachable ? 'present' : 'empty'),
        ],
      ]),
    ];

    // Set nofollow to prevent search bots from crawling anonymous links.
    if ($build['#link']) {
      $build['#link']['#attributes']['rel'][] = 'nofollow';
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getLinkLabel(string $direction, string $state, ...$parameters): string;

  /**
   * {@inheritdoc}
   */
  public function getStateActionPermissions(ActionLinkInterface $action_link): array {
    // Overridden by traits.
    return [];
  }

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
    // The base plugin class doesn't define any permissions in
    // self::getStateActionPermissions(), therefore return neutral access.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(string $direction, string $state, ...$parameters): string {
    // Overridden by traits.
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getFailureMessage(string $direction, string $state, ...$parameters): string {
    return 'Unable to perform the action. The link may be outdated.';
  }

  /**
   * {@inheritdoc}
   */
  public function getDirections(): array {
    return $this->pluginDefinition['directions'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getStates(): array {
    return $this->pluginDefinition['states'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getStateLabel(string $state): string {
    return $state;
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
  protected function getDynamicParameterValuesByName(array $parameters) {
    $named_parameters = [];
    $dynamic_parameters_definition = $this->getDynamicParameterNames();
    foreach ($dynamic_parameters_definition as $parameter_name) {
      $named_parameters[$parameter_name] = array_shift($parameters);
    }
    return $named_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getDynamicParameterNames(): array {
    return $this->pluginDefinition['dynamic_parameters'];
  }

  /**
   * Validates the dynamic parameters.
   *
   * This is called by buildLinkSet().
   *
   * @param array $parameters
   *   The dynamic parameters, keyed by the names defined in the plugin
   *   annotation.
   *
   * @throws \Throwable
   *   Throws an error or exception if the parameters are invalid.
   */
  protected function validateParameters(array $parameters) {
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
  protected function convertParametersForRoute(array $parameters): array {
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
  public function getTokenData(...$parameters) {
    return [];
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
        // Pass the action link as a parameter to the controller and access
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
