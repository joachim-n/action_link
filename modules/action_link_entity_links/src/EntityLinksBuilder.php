<?php

namespace Drupal\action_link_entity_links;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\Attribute\TrustedCallback;

/**
 * Builds entity links for hook_node_links_alter() / hook_comment_links_alter().
 */
class EntityLinksBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an EntityLinks instance.
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
   * @param array &$links
   *   The array of links.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity the links are on.
   * @param array $context
   *   The context array for the hook.
   */
  public function entityLinksAlter(array &$links, ContentEntityInterface $entity, array &$context) {
    // The entity links have a cache dependency on the list of action link
    // entities, as if an action link entity is added, deleted, or updated, the
    // list of links we return here must change. We still add this cache
    // dependency even if no action links use the entity links output plugin, as
    // if an action link is edited to use it, the links we set here will
    // change.
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->setCacheTags($this->entityTypeManager->getDefinition('action_link')->getListCacheTags());
    // This cacheability will bubble to all of the entity links, but at least
    // a dependency on config entities is unlikely to change often.
    // this needs to be added in ALL cases! even no AL entities! as editing one would change this.
    \Drupal::service('renderer')->addCacheableDependency($links, $cacheable_metadata);

    $action_link_entities = $this->entityTypeManager->getStorage('action_link')->loadByUsingOutput('entity_links');

    if (empty($action_link_entities)) {
      return;
    }

    $action_link_links = [];

    $links['#attached'] ??= [];

    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link_entity */
    foreach ($action_link_entities as $action_link_id => $action_link_entity) {
      $state_action_plugin = $action_link_entity->getStateActionPlugin();

      // Check operability. For action links that target entities (which is what
      // we are dealing with here), the state action plugin's checkOperability()
      // will check the entity bundle.
      // If the operability is based on a config field being present, we don't
      // have an implicit cache dependency, but changing fields on an entity
      // type will invalidate all cache tags for that entity type anyway.
      if (!$state_action_plugin->checkOperability($action_link_entity, $entity)) {
        continue;
      }

      foreach ($state_action_plugin->getDirections() as $direction => $label) {
        // theme_links are an abomination but we can use a lazy builder so the
        // poor cacheability of our action links doesn't pollute all of the
        // links.
        // See https://www.drupal.org/project/drupal/issues/2587417.
        $placeholder = Crypt::hashBase64(implode('-', [
          'action_link-entity_links',
          $entity->getEntityTypeId(),
          $entity->id(),
          $action_link_id,
          $direction,
        ]));

        // Because individual links aren't passed through the renderer, but
        // instead have their properties picked out and put into the main links
        // array, specifying a #lazy_builder attribute here will have no effect.
        // Instead, we generate the placeholder ourselves and attach the lazy
        // builder callback. This is the same work that the renderer does for
        // a #lazy_builder attribute.
        // Create a link that is just a placeholder: this will cause theme_links
        // to output just the placeholder string inside the LI.
        $action_link_links["action_link:$action_link_id:$direction"] = [
          'title' => $placeholder,
        ];

        // Register our placeholder and its lazy builder.
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

    if (!empty($action_link_links)) {
      $links['action_link'] = [
        '#theme' => 'links__node__action_link',
        '#links' => $action_link_links,
      ];
    }
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

    // THIS IS BAD because we're building all links, using a LB for EACH link - ok so far
    // BUT THEN calling buildSingleLink() which SECRETLY BUILDS ALL LINKS.
    // So calling n^2 links for n links!
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
