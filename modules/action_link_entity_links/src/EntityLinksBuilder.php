<?php

namespace Drupal\action_link_entity_links;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\Attribute\TrustedCallback;

/**
 * TODO: class docs.
 */
class EntityLinksBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a EntityLinks instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Delegate for hook_node_links_alter() / hook_comment_links_alter()/
   *
   * @param array $links
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   * @param array $context
   */
  public function entityLinksAlter(array &$links, ContentEntityInterface $entity, array &$context) {
    // @todo Make view modes configurable in the plugin.
    $view_mode = $context['view_mode'];

    $action_link_entities = $this->entityTypeManager->getStorage('action_link')->loadByUsingOutput('entity_links');

    $action_link_links = [];

    $links['#attached'] ??= [];

    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link_entity */
    foreach ($action_link_entities as $action_link_id => $action_link_entity) {
      foreach ($action_link_entity->getStateActionPlugin()->getDirections() as $direction => $label) {
        $placeholder = Crypt::hashBase64(implode('-', [
          'action_link-entity_links',
          $entity->getEntityTypeId(),
          $entity->id(),
          $action_link_id,
          $direction,
        ]));

        $action_link_links["action_link:$action_link_id:$direction"] = [
          'title' => $placeholder,
        ];

        $links['#attached']['placeholders'][$placeholder] = [
          '#lazy_builder' => [
            // The lazy builder callback and parameters.
            'action_link_entity_links.builder:entityLinksLazyBuilder',
            [
              $entity->getEntityTypeId(),
              $entity->id(),
              $action_link_id,
              $direction,
            ],
          ],
        ];
      }
    }

    $links['action_link'] = [
      '#theme' => 'links__node__action_link',
      '#links' => $action_link_links,
    ];

    // Our links have a cache dependency on the list of action link entities, as
    // if an action link entity is added, deleted, or updated, the list of links
    // we return here must change.
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheTags($this->entityTypeManager->getDefinition('action_link')->getListCacheTags());
    \Drupal::service('renderer')->addCacheableDependency($links, $cacheable_metadata);
  }

  /**
   * Lazy builder callback for an individual action link in the entity links.
   *
   * @param string $entity_type_id
   *   The entity type ID of the entity the link is for.
   * @param int $entity_id
   *   The entity ID.
   * @param string $action_link_id
   *   The action link entity ID.
   * @param string $direction
   *   The direction to show the link for.
   */
  #[TrustedCallback]
  public function entityLinksLazyBuilder($entity_type_id, $entity_id, $action_link_id, $direction) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $action_link_entity = $this->entityTypeManager->getStorage('action_link')->load($action_link_id);
    $user = \Drupal::currentUser();

    $state_action_plugin = $action_link_entity->getStateActionPlugin();

    $action_link_build = $state_action_plugin->buildSingleLink(
      $action_link_entity,
      $direction,
      $user,
      [
        'entity' => $entity->id(),
      ],
    );

    return $action_link_build;
  }

}
