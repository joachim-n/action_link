<?php

/**
 * @file
 * Contains \Drupal\action_link\Plugin\StateCycler\EntityProperty.
 */

namespace Drupal\action_link\Plugin\StateCycler;

use Drupal\action_link\StateCyclerInterface;

/**
 * Toggles an entity property, eg published / unpublished
 */
class EntityProperty implements StateCyclerInterface {

  /**
   * Constructor.
   *
   * @param $target_entity
   *  The target entity we act on.
   * @param $parameters
   *  An array of parameters. The format is specific to this plugin:
   *  - 'property': The name of the property that is to be toggled.
   */
  function __construct($target_entity, $parameters) {
    // TODO! this should be passed in by the config entity.
    // For now, cheat!
    $parameters['property'] = 'status';

    $this->target_entity = $target_entity;
    $this->toggle_property = $parameters['property'];
  }

  function actionIsValid() {
    // Does the action actually make sense? 
    // eg. 
    return TRUE;
  }
  
  function userHasAccess() {
    return TRUE;
  }
  
  function changeState($entity_type, $entity_id, $new_state) {
    dsm('I am changing state now!');
    
    $target_entity = entity_load($entity_type, $entity_id);
    
    $toggle_property = 'status';
    
    $target_entity->{$toggle_property} = $new_state;
    $target_entity->save();
    //$toggle_property;
  }
  
}
