<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TODO: class docs.
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

}
