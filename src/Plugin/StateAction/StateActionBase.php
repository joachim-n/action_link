<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\DynamicParameterUpcaster;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Base class for State Action plugins.
 *
 * Use one of the traits to provide the abstract methods.
 *
 * @todo Remove methods for ConfigurableInterface when
 * https://www.drupal.org/project/drupal/issues/2852463 gets in.
 */
abstract class StateActionBase extends PluginBase implements StateActionInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The dynamic parameter upcaster.
   *
   * @var \Drupal\action_link\DynamicParameterUpcaster
   */
  protected $dynamicParameterUpcaster;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('action_link.dynamic_parameter_upcaster'),
    );
  }

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\action_link\DynamicParameterUpcaster $dynamic_parameter_upcaster
   *   The dynamic parameter upcaster.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    DynamicParameterUpcaster $dynamic_parameter_upcaster,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dynamicParameterUpcaster = $dynamic_parameter_upcaster;

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
  public function buildLinkSet(
    ActionLinkInterface $action_link,
    AccountInterface $user,
    array $scalar_parameters = [],
  ): array {
    $directions = $this->getDirections();

    $build = [
      '#theme' => 'action_linkset',
      '#links' => $this->doBuildLinkArray($action_link, $user, $directions, $scalar_parameters),
      '#action_link' => $action_link,
      '#user' => $user,
      '#dynamic_parameters' => $this->dynamicParameterUpcaster->upcastDynamicParameters($action_link, $scalar_parameters),
      '#attributes' => new Attribute([
        'class' => [
          'action-linkset',
          'action-linkset-id-' . $action_link->id(),
          'action-linkset-plugin-' . $this->getPluginId(),
        ],
      ]),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSingleLink(
    ActionLinkInterface $action_link,
    string $direction,
    AccountInterface $user,
    array $scalar_parameters = [],
  ): array {
    $directions = $this->getDirections();
    $direction_array = [$direction => $directions[$direction]];

    // Yeah but we get it wrapped, is that even desirable??
    return $this->doBuildLinkArray($action_link, $user, $direction_array, $scalar_parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function buildLinkArray(
    ActionLinkInterface $action_link,
    AccountInterface $user,
    array $scalar_parameters = [],
  ): array {
    $directions = $this->getDirections();

    return $this->doBuildLinkArray($action_link, $user, $directions, $scalar_parameters);
  }

  /**
   * Builds an array of action links.
   *
   * Common code for static::buildLinkSet(), static::buildSingleLink(), and
   * static::buildLinkArray().
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get links for.
   * @param array $directions
   *   An array of the directions to return links for, in the same format as
   *   static::getDirections().
   * @param array $scalar_parameters
   *   (optional) The scalar values of the dynamic parameters for the state
   *   action plugin, keyed by the parameter names.
   */
  protected function doBuildLinkArray(
    ActionLinkInterface $action_link,
    AccountInterface $user,
    $directions,
    array $scalar_parameters,
  ): array {
    // Validate the number of dynamic parameters. This must be done before they
    // are validated by the specific plugin class.
    $dynamic_parameter_names = $this->getDynamicParameterNames();
    if (count($scalar_parameters) != count($dynamic_parameter_names)) {
      throw new \ArgumentCountError(sprintf("State action plugin %s expects %s dynamic parameters (%s), got %s",
        $this->getPluginId(),
        count($dynamic_parameter_names),
        implode(', ', $dynamic_parameter_names),
        count($scalar_parameters),
      ));
    }

    if (empty($scalar_parameters)) {
      $parameters = [];
    }
    else {
      // Ensure the parameters are keyed with their parameter names.
      if (\array_is_list($scalar_parameters)) {
        $scalar_parameters = array_combine($dynamic_parameter_names, $scalar_parameters);
      }

      // Get the upcasted the dynamic parameters.
      $parameters = $this->dynamicParameterUpcaster->upcastDynamicParameters($action_link, $scalar_parameters);

      // Validate parameters.
      $this->validateParameters($parameters);
    }

    // Strip the keys from the parameters array. This is so that plugin classes
    // can omit implementing methods in this base class that they don't care
    // about overriding. With array keys, the call to a method in this class
    // would cause a PHP error because the splat operator treats an array key as
    // a method parameter name.
    $indexed_parameters = array_values($parameters);

    $build = [];

    // If the action link isn't operable, show nothing.
    if (!$this->checkOperability($action_link, ...$indexed_parameters)) {
      return $build;
    }

    // If general access is denied, show nothing.
    if ($action_link->checkGeneralAccess($user, ...$indexed_parameters)->isForbidden()) {
      return $build;
    }

    foreach ($directions as $direction => $direction_label) {
      $link = $this->buildLink($action_link, $direction, $user, $scalar_parameters, ...$indexed_parameters);
      if ($link) {
        $build[$direction] = $link;
      }
    }

    // Allow the link style plugin for this action link entity to modify the
    // render array for the links.
    if ($build) {
      $action_link->getLinkStylePlugin()->alterLinksBuild($build, $action_link, $user, $parameters, $scalar_parameters);
    }

    return $build;
  }

  /**
   * Gets the build array for a single link.
   *
   * @todo Change this to return the Link object and build the render array in
   * the caller.
   *
   * Helper for doBuildLinkArray().
   *
   * This creates a build array rather than return a Link object, because in
   * some cases we want an empty build array with no link, and in some we want
   * nothing at all, and returning a Link object wouldn't capture that
   * distinction.
   *
   * This does not check operability: that has already been checked by
   * doBuildLinkArray().
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction for the action.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param array $scalar_parameters
   *   The dynamic parameters, downcasted to scalar values, keyed by parameter
   *   name.
   * @param mixed ...$parameters
   *   The dynamic parameters.
   *
   * @return array|null
   *   A build array for the link, or NULL if nothing should be output. The
   *   build array may itself not contain a link and show nothing. The principle
   *   for determining whether to return NULL or an empty link is whether a
   *   different state could result in the link for the direction in question
   *   becoming available. See the ActionLinkset class for more details.
   */
  protected function buildLink(ActionLinkInterface $action_link, $direction, AccountInterface $user, array $scalar_parameters, ...$parameters): ?array {
    // Only NULL means there is no valid next state; a string such as '0' is
    // a valid state.
    $next_state = $this->getNextStateName($direction, $user, ...$parameters);
    $reachable = !is_null($next_state);

    if ($reachable) {
      // Check access if the state is reachable. If the state is not reachable,
      // we can't check access because that requires a state parameter, so we
      // output an empty link. This is because access is state-specific, and a
      // user could use a different direction which then causes this direction
      // to become accessible: for this to work correctly with AJAX, an empty
      // link must be present to be replaced.
      $access = $action_link->checkStateAccess($direction, $next_state, $user, ...$parameters);
    }

    if ($reachable && $access->isAllowed()) {
      $route_parameters = [
        'action_link' => $action_link->id(),
        'link_style' => $action_link->getLinkStylePlugin()->getPluginId(),
        'direction' => $direction,
        'state' => $next_state,
        'user' => $user->id(),
      ];

      // Add the dynamic parameters to the route parameters.
      $route_parameters += $scalar_parameters;

      $label = $action_link->getLinkLabel($direction, $next_state, ...$parameters);

      $url = Url::fromRoute($action_link->getRouteName(), $route_parameters);
      $link = Link::fromTextAndUrl($label, $url);
    }
    else {
      // @todo Show a link to log in if the user doesn't have access but an
      // authenticated user would. Determining this appears to be rather
      // complicated, as we'd need to mock a user object to pass to access
      // checks, but isAuthenticated() works by checking for the uid.
      $link = NULL;
    }

    // We output an action link even if there is no actual link to show because
    // the state is not reachable in the given direction or there is no access
    // to the state. This is so that if a link in another direction is used over
    // AJAX, and causes the inactive direction to become available, then the
    // empty SPAN is replaced by the AJAX with an active link. For example,
    // suppose an action link adds a product to a shopping cart, with 'add' and
    // 'remove' directions. When the cart is empty, only the 'add' direction
    // link shows. Clicking this link takes the site to a state where the
    // 'remove' direction is now valid and the link for that should show.
    // Therefore, the AJAX replacement that occurs when the user clicks the
    // 'add' link must replace both directions. Having an empty SPAN for the
    // 'remove' direction means there is somewhere for the updated 'remove' link
    // to go.
    $build = [
      '#theme' => 'action_link',
      '#link' => $link ? $link->toRenderable() : [],
      '#action_link' => $action_link,
      '#direction' => $direction,
      '#state' => $next_state,
      '#user' => $user,
      '#dynamic_parameters' => $parameters,
      '#attributes' => new Attribute([
        'class' => [
          'action-link',
          'action-link-id-' . $action_link->id(),
          'action-link-plugin-' . $this->getPluginId(),
          'action-link-direction-' . $direction,
          'action-link-state-' . $next_state,
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
  public function checkOperability(ActionLinkInterface $action_link): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandGeneralAccess(ActionLinkInterface $action_link, AccountInterface $account): AccessResult {
    // Allow at the operand by default.
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function checkPermissionStateAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    // The base plugin class doesn't define any permissions in
    // self::getStateActionPermissions(), therefore return neutral access.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  public function checkOperandStateAccess(ActionLinkInterface $action_link, string $direction, string $state, AccountInterface $account): AccessResult {
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
   * @return array
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
  public function getTokenData() {
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
