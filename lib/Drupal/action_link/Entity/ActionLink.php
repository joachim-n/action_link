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
 * Defines the ActionLink config entity type.
 *
 * An action link config entity is a generalized user of action links: it may
 * work with the EntityProperty toggler, or other state cyclers, including
 * presumably one that does nothing except fire rules.
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
    //$link_style_plugin_id = 'confirm';
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
   * Returns the state cycler plugin for this entity.
   *
   * @param $target_entity
   *  The target entity to act on.
   */
  public function getStateCyclerPlugin($target_entity) {
    // @todo: plugin bag stuff.
    // @todo: plugin manager etc
    // @todo: pass on our settings to the plugin.
    //    need to figure out how to pass different settings to different
    //    state cycler plugins from the action link entity type.
    // Fake it for now!
    $parameters = array();
    return new \Drupal\action_link\Plugin\StateCycler\EntityProperty($target_entity, $parameters);
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
    // TODO: move some or all of this to the style plugin.
    $action_link_url = $this->getLinkPath($entity);

    $build = array(
      // Here we need:
      // - the action link
      '#prefix' => '<div>',
      '#markup' => l('action link!', $action_link_url, array('query' => array('destination' => current_path()))),
      '#suffix' => '</div>',
    );

    return $build;
  }

  /**
   * Returns the path for a link.
   */
  public function getLinkPath($target_entity) {
    // TODO: go through to the plugin!!!!
    $config_entity_type = $this->entityType();
    // TODO!
    $config_id = 'cake';

    $entity_type = $target_entity->entityType();
    $entity_id = $target_entity->id();

    // We only need the plugin ID to form the link.
    $link_style_plugin_id = $this->getLinkStylePluginId();

    // Get the next state the target entity can be advanced to.
    $state_cycler_plugin = $this->getStateCyclerPlugin($target_entity);
    $next_state = $state_cycler_plugin->getNextState();

    return "action_link/$link_style_plugin_id/$config_entity_type/$config_id/$entity_type/$entity_id/$next_state";
  }

}
