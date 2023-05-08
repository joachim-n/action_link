<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Link style which reloads the page.
 *
 * The action link message is shown as a standard Drupal status message.
 *
 * @ActionLinkStyle(
 *   id = "nojs",
 *   label = @Translation("Reload"),
 *   description = @Translation("A link which makes normal non-JavaScript request which reloads the current page.")
 * )
 */
class Nojs extends ActionLinkStyleBase implements ContainerFactoryPluginInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
    );
  }

  /**
   * Creates a Nojs instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MessengerInterface $messenger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function handleActionRequest(bool $action_completed, Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, ...$parameters): Response {
    if ($action_completed) {
      $message = $action_link->getMessage($direction, $state, ...$parameters);
    }
    else {
      $message = $action_link->getFailureMessage($direction, $state, ...$parameters);
    }

    if ($message) {
      $this->messenger->addMessage($message);
    }

    // Redirect to the referrer.
    if ($request->headers->get('referer')) {
      $response = new RedirectResponse($request->headers->get('referer'));
    }
    else {
      // This shouldn't happen outside of tests.
      $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
    }

    return $response;
  }

}
