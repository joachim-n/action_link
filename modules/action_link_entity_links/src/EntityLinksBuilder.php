<?php

namespace Drupal\action_link_entity_links;

use Drupal\Component\Utility\Crypt;
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

    $user = \Drupal::currentUser();

    $action_link_entities = \Drupal::service('entity_type.manager')->getStorage('action_link')->loadByUsingOutput('entity_links');
    foreach ($action_link_entities as $action_link_entity) {
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
      $action_links = $state_action_plugin->buildLinkArray(
        $action_link_entity,
        $user,
        [
          'entity' => $entity->id(),
        ],
      );

      $action_link_links = [];
      foreach (Element::children($action_links) as $direction) {
        $action_link_links['action_link' . ':' . $action_link_entity->id() . ':' . $direction] = [
          'title' => $action_links[$direction]['#link']['#title'],
          'url' => $action_links[$direction]['#link']['#url'],
          'attributes' => $action_links[$direction]['#link']['#attributes'],
        ];

        // We can't set attributes on the LI element at this point, so stash them in
        // a fake key in the link attributes for
        // action_link_entity_links_preprocess_links() to retrieve.
        $action_link_links['action_link' . ':' . $action_link_entity->id() . ':' . $direction]['attributes']['li_class'] = $action_links[$direction]['#attributes']['class'];
      }
    }

    $placeholder = Crypt::hashBase64('action_link-entity_links-node-' . $entity->id() . '-' . $view_mode);

    // TODO! CACHE STUFF!
    $links['action_link'] = [
      '#theme' => 'links__node__action_link',
      '#links' => $action_link_links,
      '#lazy_builder_var' => $placeholder,
    ];

    $links['#attached'] ??= [];
    $links['#attached']['placeholders'][$placeholder] = [
      '#lazy_builder' => [
        // The lazy builder callback and parameters.
        'action_link_entity_links.builder:entityLinksLazyBuilder',
        [],
      ],
    ];
  }

  #[TrustedCallback]
  public function entityLinksLazyBuilder() {
    return [];
  }


}
