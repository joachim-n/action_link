<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\action_link\Ajax\ActionLinkMessageCommand;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Link style which uses a JavaScript link without reloading the page.
 *
 * The action link message is shown briefly alongside the clicked link.
 *
 * This gracefully degrades to the Nojs link style if JavaScript is not
 * available.
 *
 * @ActionLinkStyle(
 *   id = "ajax",
 *   label = @Translation("JavaScript"),
 *   description = @Translation("A link which makes an AJAX JavaScript request without reloading the page.")
 * )
 */
class Ajax extends ActionLinkStyleBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
    );
  }

  /**
   * Creates a Ajax instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLinksBuild(array &$build, ActionLinkInterface $action_link, AccountInterface $user, array $named_parameters, array $scalar_parameters) {
    foreach ($build as $direction => $direction_link_build) {
      // Add the 'use-ajax' class to the link. This makes core handle the link
      // using a JS request and degrades gracefully to be handled by the nojs
      // link style plugin.
      $build[$direction]['#link']['#attributes']['class'][] = 'use-ajax';

      // Add a unique class to the outer HTML for the AJAX replacement.
      $build[$direction]['#attributes']['class'][] = $this->createCssIdentifier($action_link, $direction, $user, ...$scalar_parameters);
    }

    $build['#attached']['library'][] = 'action_link/link_style.ajax';
  }

  /**
   * {@inheritdoc}
   */
  public function handleActionRequest(bool $action_completed, Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, ...$parameters): Response {
    // Create a new AJAX response.
    $response = new AjaxResponse();

    $state_action_plugin = $action_link->getStateActionPlugin();

    // Get the raw values of the dynamic parameters. The $parameters array will
    // contain the upcasted values, but we need the raw values to create CSS
    // identifiers.
    $raw_parameters = $route_match->getRawParameters();

    $dynamic_parameter_names = $state_action_plugin->getDynamicParameterNames();

    $raw_dynamic_parameters = [];
    foreach ($dynamic_parameter_names as $name) {
      $raw_dynamic_parameters[$name] = $raw_parameters->get($name);
    }

    // Key the upcasted parameters array.
    $dynamic_parameters = array_combine($dynamic_parameter_names, $parameters);

    $this->addReplacementsToResponse(
      $response,
      $action_completed,
      $request,
      $route_match,
      $action_link,
      $direction,
      $state,
      $user,
      $raw_dynamic_parameters,
      $dynamic_parameters,
    );
    $this->addMessageToResponse(
      $response,
      $action_completed,
      $request,
      $route_match,
      $action_link,
      $direction,
      $state,
      $user,
      $raw_dynamic_parameters,
      $dynamic_parameters,
    );

    return $response;
  }

  /**
   * Adds the AJAX replacements to the response.
   *
   * This replaces all links for this action link, not just the clicked one, as
   * the next state will change for all directions.
   *
   * Links are replaced even if the action was not completed, as if that is the
   * case then the links on the page are out of date and should be updated.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The AJAX response that will be returned, to which replacement commands
   *   should be added.
   * @param bool $action_completed
   *   Whether the action could be completed. If FALSE, this means that the
   *   action wasn't operable or the target state wasn't reachable.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction of the link.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param array $raw_dynamic_parameters
   *   An array of the raw values of the dynamic parameters for the state action
   *   plugin, keyed by parameter name.
   * @param array $dynamic_parameters
   *   An array of the upcasted values of the dynamic parameters for the state
   *   action plugin, keyed by parameter name.
   */
  protected function addReplacementsToResponse(
    AjaxResponse $response,
    bool $action_completed,
    Request $request,
    RouteMatchInterface $route_match,
    ActionLinkInterface $action_link,
    string $direction,
    string $state,
    UserInterface $user,
    $raw_dynamic_parameters,
    $dynamic_parameters,
  ): void {
    // Get the links from the plugin rather than the action link entity, so we
    // get the plain render array for each link, and not the lazy builder.
    // We get the plain array of links rather than the linkset as we need to
    // return each link in a separate AJAX command.
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user, $raw_dynamic_parameters, $dynamic_parameters);

    foreach (Element::children($links) as $link_direction) {
      // Generate a CSS selector to use in a JQuery Replace command.
      $selector = '.' . $this->createCssIdentifier($action_link, $link_direction, $user, ...array_values($raw_dynamic_parameters));

      // Create a new AJAX Replace command to update the link display. This
      // will update all copies of the same link if there are more than one.
      $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($links[$link_direction]));
      $response->addCommand($replace);
      // It doesn't matter that we only render the children, and not the whole
      // thing. This skips any attachments, but these are already on the page
      // from its initial load.
    }
  }

  /**
   * Adds a success or failure message to the response if applicable.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The AJAX response that will be returned, to which message commands
   *   should be added.
   * @param bool $action_completed
   *   Whether the action could be completed. If FALSE, this means that the
   *   action wasn't operable or the target state wasn't reachable.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction of the link.
   * @param string $state
   *   The target state for the action.
   * @param \Drupal\user\UserInterface $user
   *   The user to perform the action. This is not necessarily the current user.
   * @param array $raw_dynamic_parameters
   *   An array of the raw values of the dynamic parameters for the state action
   *   plugin, keyed by parameter name.
   * @param array $dynamic_parameters
   *   An array of the upcasted values of the dynamic parameters for the state
   *   action plugin, keyed by parameter name.
   */
  protected function addMessageToResponse(
    AjaxResponse $response,
    bool $action_completed,
    Request $request,
    RouteMatchInterface $route_match,
    ActionLinkInterface $action_link,
    string $direction,
    string $state,
    UserInterface $user,
    $raw_dynamic_parameters,
    $dynamic_parameters,
  ): void {
    if ($action_completed) {
      $message = $action_link->getMessage($direction, $state, ...array_values($dynamic_parameters));
    }
    else {
      $message = $action_link->getFailureMessage($direction, $state, ...array_values($dynamic_parameters));
    }

    if ($message) {
      $selector = '.' . $this->createCssIdentifier($action_link, $direction, $user, ...array_values($raw_dynamic_parameters));

      // Add a message command to the stack.
      $message_command = new ActionLinkMessageCommand($selector, $message);
      $response->addCommand($message_command);
    }
  }

  /**
   * Creates a unique HTML class for an action link.
   *
   * We don't use \Drupal\Component\Utility\Html\HTML::getUniqueId() because we
   * want the same class to be used on all instances of the same action link, so
   * that they are all replaced.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $action_link
   *   The action link entity.
   * @param string $direction
   *   The direction.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user account the action is for.
   * @param mixed ...$scalar_parameters
   *   The dynamic parameters for the action.
   *
   * @return string
   *   A CSS class.
   */
  protected function createCssIdentifier(ActionLinkInterface $action_link, string $direction, AccountInterface $user, ...$scalar_parameters): string {
    return Html::cleanCssIdentifier(implode(
      '-', [
        'action-link',
        $action_link->id(),
        $direction,
        $user->id(),
        ...array_values($scalar_parameters),
      ]
    ));
  }

}
