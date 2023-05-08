<?php

namespace Drupal\action_link_poc\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Shows all action links that use the proof-of-concept plugins.
 */
class ActionLinkPocController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Creates a ActionLinkPocController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Callback for the action_link_poc.action_link_poc route.
   */
  public function content() {
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $user = \Drupal::currentUser();

    $action_links = $entity_type_manager->getStorage('action_link')->loadMultiple();

    // Assume node 1 exists!
    $node = $entity_type_manager->getStorage('node')->load(1);

    /** @var \Drupal\action_link\Entity\ActionLinkInterface */
    foreach ($action_links as $action_link_id => $action_link) {
      // Add to cart action links.
      if ($action_link->getStateActionPlugin()->getPluginId() == 'poc_add_to_cart') {
        $build[$action_link_id] = [
          '#type' => 'container',
        ];

        $build[$action_link_id]['links'] = $action_link->buildLinkSet($user, $node);
      }

      // Subscribe action links.
      if ($action_link->getStateActionPlugin()->getPluginId() == 'poc_subscribe') {
        // @todo It's not very elegant having to pass both the entity type ID
        // and the entity ID. See the @todo note in the plugin class.
        $build[$action_link_id] = [
          '#type' => 'container',
        ];
        $build[$action_link_id]['links'] = $action_link->buildLinkSet($user, 'node', $node->id());
      }
    }

    return $build;
  }

}
