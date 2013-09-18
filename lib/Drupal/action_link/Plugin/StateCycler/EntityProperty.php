<?php

/**
 * @file
 * Contains \Drupal\action_link\Plugin\StateCycler\EntityProperty.
 */

namespace Drupal\action_link\Plugin\StateCycler;

use Drupal\action_link\StateCyclerInterface;

/**
 * entity property toggle, eg published / unpublished
 */
class EntityProperty implements StateCyclerInterface {
  
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
