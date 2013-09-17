<?php

/**
 * @file
 * Contains Drupal\action_link\Entity\ActionLink.
 */

namespace Drupal\action_link\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the ActionLink entity.
 *
 * The lines below are a plugin annotation. These define the entity type to the
 * entity type manager.
 *
 * The properties in the annotation are as follows:
 * TODO: add a @see to the annotation docs, assuming these exist!!!
 *  - id: The machine name of the entity type.
 *  - label: The human-readable label of the entity type.
 *    TODO: explain @Translation.
 *  - module: The name of the module that provides this.
 *    TODO: is this necessary?
 *  - controllers: An array specifying controller classes that handle various
 *    aspects of the entity type's functionality.
 *  - config_prefix: This tells the config system the prefix to use for
 *    filenames when storing entities. This means that the default entity we
 *    include in our module has the filename
 *    'action_link.action_link.marvin.yml'.
 *  - entity_keys: TODO.
 *
 * @EntityType(
 *   id = "action_link",
 *   label = @Translation("ActionLink"),
 *   module = "action_link",
 *   controllers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigStorageController",
 *     "access"  = "Drupal\action_link\ActionLinkAccessController",
 *     "list"    = "Drupal\Core\Config\Entity\ConfigEntityListController",
 *     "form" = {
 *       "add" = "Drupal\action_link\ActionLinkFormController",
 *       "edit" = "Drupal\action_link\ActionLinkFormController"
 *     }
 *   },
 *   access_controller_permission = "administer action links",
 *   config_prefix = "action_link.action_link",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class ActionLink extends ConfigEntityBase {
  // @todo: interface!

  /**
   * The action link ID.
   *
   * @var string
   */
  public $id;

  /**
   * The action link label.
   *
   * @var string
   */
  public $label;
  
  // CHEAT FOR NOW!
  public $toggle_property = 'status';
  
  /**
   * {@inheritdoc}
   *
   * This defines the URI for an action link entity.
   */
  public function uri() {
    return array(
      // TODO docs.
      'path' => 'admin/structure/action_link/manage/' . $this->id(),
      'options' => array(),
    );
  }

  /**
   * Returns the action link plugin for this entity.
   */
  public function getLinkControllerPlugin() {
    // @todo: plugin bag stuff.
    // @todo: plugin manager etc
    // @todo: pass on our settings to the plugin
    // Fake it for now!
    return new \Drupal\action_link\Plugin\ActionLinkController\EntityProperty($this);
  }

  /**
   * Returns the path for a link.
   */
  public function getLinkPath($entity) {
    // TODO: go through to the plugin!!!!
    $config_entity_type = $this->entityType();
    // TODO!
    $config_id = 'cake';
    
    $entity_type = $entity->entityType();
    $entity_id = $entity->id();
    
    // TODO!
    $link_style = 'reload';
    
    $target_entity = entity_load($entity_type, $entity_id);
    $current_property_value = $target_entity->get($this->toggle_property)->value;
    dsm($target_entity->get($this->toggle_property)->value);
    
    dsm($current_property_value);
    $new_property_value = (int) !$current_property_value;
    
    return "action_link/$link_style/$config_entity_type/$config_id/$entity_type/$entity_id/$new_property_value";
  }

}
