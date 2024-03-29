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

    // $placeholder = Crypt::hashBase64('action_link-entity_links-' . $entity->getEntityTypeId() . '-' . $entity->id() . '-' . $view_mode);

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
   * Undocumented function
   */
  #[TrustedCallback]
  public function entityLinksLazyBuilder($entity_type_id, $entity_id, $action_link_id, $direction) {
    $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    $action_link_entity = $this->entityTypeManager->getStorage('action_link')->load($action_link_id);
    $user = \Drupal::currentUser();

    // Replace the ajax plugin with our altered version.
    // @todo Add a getter for this?
    if ($action_link_entity->get('link_style') == 'ajax') {
      $action_link_entity->set('link_style',  'ajax_entity_links');
    }

    $state_action_plugin = $action_link_entity->getStateActionPlugin();

    // This is a total hack: we get the render array for the action link, then
    // cannibalise it for bits to put in the render array for the entity links.
    // @todo Refactor the action link rendering methods on StateActionBase to
    // give us things that are more suitable here.
    $action_link_build = $state_action_plugin->buildSingleLink(
      $action_link_entity,
      $direction,
      $user,
      [
        'entity' => $entity->id(),
      ],
    );

    // dsm($action_link_build);
    return $action_link_build;

    //   $action_link_links = [];
    //   foreach (Element::children($action_links) as $direction) {
    //     $action_link_links['action_link' . ':' . $action_link_entity->id() . ':' . $direction] = [
    //       'title' => $action_links[$direction]['#link']['#title'],
    //       'url' => $action_links[$direction]['#link']['#url'],
    //       'attributes' => $action_links[$direction]['#link']['#attributes'],
    //     ];

    //     // We can't set attributes on the LI element at this point, so stash them in
    //     // a fake key in the link attributes for
    //     // action_link_entity_links_preprocess_links() to retrieve.
    //     $action_link_links['action_link' . ':' . $action_link_entity->id() . ':' . $direction]['attributes']['li_class'] = $action_links[$direction]['#attributes']['class'];
    //   }
    // }

    // dsm(func_get_args());
    return [
      '#theme' => 'links__node__action_link',
      '#links' => $action_link_links,
    ];

    // $links['dummy'] = [
    //   '#theme' => 'links__node__statistics',
    //   '#links' => [
    //     'foo' => [
    //       'title' => 'OH YEAH THIS IS A LINK',
    //     ],
    //   ],
    //   '#attributes' => ['class' => ['links', 'inline']],
    // ];
    }


}
