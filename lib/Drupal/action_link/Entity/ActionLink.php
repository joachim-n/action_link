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
use Drupal\action_link\ActionLinkConfigInterface;

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
class ActionLink extends ConfigEntityBase implements ActionLinkConfigInterface {
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

  public function getLinkStylePluginId() {
    // TODO! this is faked!
    $link_style_plugin_id = 'reload';
    return $link_style_plugin_id;
  }

  /**
   *
   */
  public function getLinkStylePlugin() {
    // TODO! this is faked!
    $link_style_plugin_id = $this->getLinkStylePluginId();

    $link_style_plugin_manager = \Drupal::service('plugin.manager.action_link');
    $action_link_style_plugin = $link_style_plugin_manager->createInstance($link_style_plugin_id, array());
  }

  /**
   * Returns the action link plugin for this entity.
   */
  public function getStateCyclerPlugin() {
    // @todo: plugin bag stuff.
    // @todo: plugin manager etc
    // @todo: pass on our settings to the plugin.
    //    need to figure out how to pass different settings to different
    //    state cycler plugins from the action link entity type.
    // Fake it for now!
    $parameters = array();
    return new \Drupal\action_link\Plugin\StateCycler\EntityProperty($this, $parameters);
  }


  /**
   * Generate the render array for the action link.
   *
   * @param $entity
   *  The entity to create a link for.
   *
   * @return
   *  A render array containing the link.
   */
  public function buildLink($entity) {
    // TODO moveALL TTHIS TO THE STYLE!
    $action_link_url = $this->getLinkPath($entity);

    $build = array(
      // Here we need:
      // - the action link
      '#prefix' => '<div>',
      '#markup' => l('link text!', $action_link_url, array('query' => array('destination' => current_path()))),
      '#suffix' => '</div>',
    );

    return $build;
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

    // We only need the plugin ID to form the link.
    $link_style_plugin_id = $this->getLinkStylePluginId();

    // TODO: this moves to the state cycler!
    $target_entity = entity_load($entity_type, $entity_id);
    $current_property_value = $target_entity->get($this->toggle_property)->value;
    //dsm($target_entity->get($this->toggle_property)->value);

    //dsm($current_property_value);

    // The toggling logic.

    $new_property_value = (int) !$current_property_value;

    return "action_link/$link_style_plugin_id/$config_entity_type/$config_id/$entity_type/$entity_id/$new_property_value";
  }

}
