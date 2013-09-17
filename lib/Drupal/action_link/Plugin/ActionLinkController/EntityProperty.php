<?php

/**
 * @file
 * Contains \Drupal\action_link\Plugin\ActionLinkController\Flag.
 */

namespace Drupal\action_link\Plugin\ActionLinkController;

use Drupal\action_link\ActionLinkControllerInterface;

/**
 * entity property toggle, eg published / unpublished
 */
class Flag implements ActionLinkControllerInterface {
  
  // WORP WOPR WOPR
  // We need the flag name!!!!!
  // where is that!?!?!
  
  function changeState($entity_id, $entity, $parameters) {
    // $parameters is set in the config and includes stuff like the flag name???
    
    $flag_id = $parameters['flag'];
    
    dsm('I am flagging now!');
    
    // Flags are config entities too!
    //$flag = entity_load('flag', $flag_id);
    
    // ARgh. need to write this crap ourselves :(
    //$flag_plugin = $flag->getPlugin->();
    
    //$flag_plugin->flag(......)
    
    //Done!
    
  }
  
}
