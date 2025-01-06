<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\action_link\Attribute\ActionLinkStyle;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\Attribute;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Link style which uses a JavaScript link without reloading the page.
 *
 * The action link message is shown briefly alongside the clicked link.
 *
 * This gracefully degrades to the Nojs link style if JavaScript is not
 * available.
 *
 * @see templates/action-link-popup-message.html.twig
 */
#[ActionLinkStyle(
  id: 'ajax',
  label: new TranslatableMarkup('JavaScript'),
  description: new TranslatableMarkup('A link which makes an AJAX JavaScript request without reloading the page.'),
)]
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
   * Creates an Ajax instance.
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
      // Graceful degradation: change the style plugin in the link URL to 'nojs'
      // so that if JavaScript is not enabled, the reload page plugin is used
      // instead. Drupal's AJAX system will replace this path component with
      // 'ajax' if JavaScript is enabled.
      // @todo This won't work because the link URL being changed by JavaScript
      // means that the CSRF token is invalid. Uncomment this when
      // https://www.drupal.org/project/drupal/issues/2670798 is fixed.
      // if (isset($build[$direction]['#link']['#url'])) {
      //   $route_parameters = $build[$direction]['#link']['#url']->getRouteParameters();
      //   $route_parameters['link_style'] = 'nojs';
      //   $build[$direction]['#link']['#url']->setRouteParameters($route_parameters);
      // }

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
  public function handleActionRequest(bool $success, Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, ...$parameters): Response {
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
      $success,
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
      $success,
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
   * @param bool $success
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
    bool $success,
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
   * @param bool $success
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
    bool $success,
    Request $request,
    RouteMatchInterface $route_match,
    ActionLinkInterface $action_link,
    string $direction,
    string $state,
    UserInterface $user,
    $raw_dynamic_parameters,
    $dynamic_parameters,
  ): void {
    if ($success) {
      $message = $action_link->getMessage($direction, $state, ...array_values($dynamic_parameters));
    }
    else {
      $message = $action_link->getFailureMessage($direction, $state, ...array_values($dynamic_parameters));
    }

    if ($message) {
      $build = [
        '#theme' => 'action_link_popup_message',
        '#message' => $message,
        '#success' => $success,
        '#action_link' => $action_link,
        '#direction' => $direction,
        '#state' => $state,
        '#user' => $user,
        '#dynamic_parameters' => $dynamic_parameters,
        '#attributes' => new Attribute([
          'class' => [
            'action-link-ajax-message',
            'action-link-ajax-message-id-' . $action_link->id(),
            'action-link-ajax-message-plugin-' . $this->getPluginId(),
            'action-link-ajax-message-direction-' . $direction,
            'action-link-ajax-message-state-' . $state,
            'action-link-ajax-message-' . ($success ? 'success' : 'failure'),
          ],
        ]),
      ];

      $selector = $this->getMessageCommandSelector($action_link, $direction, $user, $raw_dynamic_parameters, $dynamic_parameters);

      // Add a message command to the stack.
      $message_command = new AppendCommand(
        selector: $selector,
        content: $build,
      );
      $response->addCommand($message_command);
    }
  }

  /**
   * Gets the CSS selector for the AJAX message command.
   *
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
   *
   * @return string
   *   The CSS selector for the element on which the message should be appended.
   */
  protected function getMessageCommandSelector(ActionLinkInterface $action_link, string $direction, UserInterface $user, $raw_dynamic_parameters, $dynamic_parameters): string {
    return '.' . $this->createCssIdentifier($action_link, $direction, $user, ...array_values($raw_dynamic_parameters));
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
