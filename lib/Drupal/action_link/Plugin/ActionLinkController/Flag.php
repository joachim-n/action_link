<?php

/**
 * @file
 * Contains \Drupal\action_link\Plugin\ActionLinkController\Flag.
 */

namespace Drupal\action_link\Plugin\ActionLinkController;

use Drupal\action_link\ActionLinkControllerInterface;

/**
 *
 */
class Flag implements ActionLinkControllerInterface {
  
  // WORP WOPR WOPR
  // We need the flag name!!!!!
  // where is that!?!?!
  
  
  function __construct($config_entity = NULL) {
    // Get a ref to the config entity so we know things like:
    // - what is our flag name? etc
  }
  
  function actionIsValid() {
    // Does the action actually make sense? 
    // eg. 
    return TRUE;
  }
  
  function userHasAccess() {
    return TRUE;
  }
  
  function changeState($entity_id, $entity, $parameters) {
    // $parameters is set in the config and includes stuff like the flag name???
    
    //$flag_id = $parameters['flag'];
    
    dsm('I am flagging now!');
    
    // Flags are config entities too!
    //$flag = entity_load('flag', $flag_id);
    
    // ARgh. need to write this crap ourselves :(
    //$flag_plugin = $flag->getPlugin->();
    
    //$flag_plugin->flag(......)
    
    //Done!
    
  }
  
}