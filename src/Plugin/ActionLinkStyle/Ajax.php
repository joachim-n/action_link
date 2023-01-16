<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\flag\Ajax\ActionLinkFlashCommand;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TODO: class docs.
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
  public function alterLinksBuild(&$build, ActionLinkInterface $action_link, AccountInterface $user, ...$parameters) {
    foreach ($build as $direction => $direction_link_build) {
      $build[$direction]['#attributes']['class'][] = 'use-ajax';
      $build[$direction]['#attributes']['class'][] = $this->createCssIdentifier($action_link, $direction, $user, ...$parameters);
    }

    // TODO!
    // $build['#attached']['library'][] = 'action_link/action_link.ajax';
  }

  protected function createCssIdentifier(ActionLinkInterface $action_link, string $direction, AccountInterface $user, ...$parameters): string {
    return Html::cleanCssIdentifier(implode(
      '-', [
        'action-link',
        $action_link->id(),
        $direction,
        $user->id(),
        // ARGH! params!?! YES. but SCALAR!
      ]
    ));
  }


  public function handleActionRequest(bool $action_completed, Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, ...$parameters): Response {
    $state_action_plugin = $action_link->getStateActionPlugin();

    $link = $state_action_plugin->getLink($action_link, $direction, $user, ...$parameters);

    $build = $link->toRenderable();
    $build['#attributes']['class'][] = 'use-ajax';



    // TODO!
    // Generate a CSS selector to use in a JQuery Replace command.
    $selector = $this->createCssIdentifier($action_link, $direction, $user, ...$parameters);

    // Create a new AJAX response.
    $response = new AjaxResponse();

    // Create a new JQuery Replace command to update the link display.
    $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($build));
    $response->addCommand($replace);

    if ($action_completed) {
      $message = $action_link->getStateActionPlugin()->getMessage($direction, $state, ...$parameters);
      if ($message) {
        // TODO! THIS IS FROM FLAG!
        // Push a message pulsing command onto the stack.
        $pulse = new ActionLinkFlashCommand($selector, $message);
        $response->addCommand($pulse);
      }
    }



    return $response;
  }

}
