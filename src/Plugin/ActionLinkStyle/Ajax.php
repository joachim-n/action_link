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
use Drupal\Core\Template\Attribute;
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
  public function alterLinksBuild(&$build, ActionLinkInterface $action_link, AccountInterface $user, $named_parameters, $scalar_parameters) {
    foreach ($build as $direction => $direction_link_build) {
      // Add the 'use-ajax' class to the link. This makes core handle the link using a JS
      // request and degrades gracefully to be handled by the nojs link style
      // plugin.
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
      $raw_dynamic_parameters[] = $raw_parameters->get($name);
    }

    // We have to replace all links for this action link, not just the clicked
    // one, as the next state will change for all directions.
    // Replace links even if the action was not completed, as if that is the
    // case then links on the page are out of date.
    $links = $action_link->buildLinkSet($user, ...$parameters);
    foreach (Element::children($links) as $link_direction) {
      // Generate a CSS selector to use in a JQuery Replace command.
      $selector = '.' . $this->createCssIdentifier($action_link, $link_direction, $user, ...$raw_dynamic_parameters);

      // Create a new AJAX Replace command to update the link display. This
      // will update all copies of the same link if there are more than one.
      $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($links[$link_direction]));
      $response->addCommand($replace);
      // It doesn't matter that we only render the children, and not the whole
      // thing. This skips any attachments, but these are already on the page
      // from its initial load.
    }

    if ($action_completed) {
      $message = $action_link->getStateActionPlugin()->getMessage($direction, $state, ...$parameters);
    }
    else {
      $message = $action_link->getStateActionPlugin()->getFailureMessage($direction, $state, ...$parameters);
    }

    if ($message) {
      $selector = '.' . $this->createCssIdentifier($action_link, $direction, $user, ...$raw_dynamic_parameters);

      // Add a message command to the stack.
      $message_command = new ActionLinkMessageCommand($selector, $message);
      $response->addCommand($message_command);
    }

    return $response;
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
