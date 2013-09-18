<?php

/**
 * @file
 * Contains \Drupal\action_link\Controller\ActionLinkController.
 */

namespace Drupal\action_link\Controller;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Default Response Controller for action links.
 *
 * This works with config entities to perform the action, which must implement
 * the ActionLinkConfigInterface.
 */
class ActionLinkController {

  /**
   * Page controller for normal action links.
   *
   * @param $action_link
   *  DESC
   * @param $new_state
   *  DESC
   * @param $entity_type
   *  DESC
   * @param $entity_id
   *  DESC
   @param
    return link type: LINKPLUGINID
   *
   * @return
   *  DESC
   */
  // action_link/reload/flag/bookmarks/node/1/flag
  // action_link/reload/action_link/cake/node/1/flag
  function normal($action_link_plugin_style_id, $config_entity_type, $config_id, $entity_type, $entity_id, $new_state) {
    // WTF, $action_link doesn't autoload, when the name of another entity type
    //// did and caused me hours of headdesking??? WTF, D8?
    //$action_link = entity_load('action_link', $action_link);

    // 1. Load the config entity involved.
    // Any config entity can be used here, provided it implements our interface.
    // The config entity is responsible for telling us about:
    //  - the action link controller plugin (see below)
    //  - any strings we output, such as messages and link text
    //  - what style of link to return (?? or should we just return the same
    //    style we came in on?)
    $config_entity = entity_load($config_entity_type, $config_id);
    //dsm($config_entity);
    $target_entity = entity_load($entity_type, $entity_id);
    dsm($target_entity);

    // 2. Get the action link controller plugin from the config entity.
    // In D7 land, this would be the actual Flag handler object.
    // In D8, this is separate from the config entity.
    // For POC, we have two: Flag, EntityProperty.
    // Other response controllers might do something different here, eg
    // if this were for a field widget, there'd be no config entity, and the
    // information on what to do would be in the field settings.
    //$action_link_plugin = new \Drupal\action_link\Plugin\ActionLinkController\Flag();
    $action_link_plugin = $config_entity->getStateCyclerPlugin();

    // Find out if this route is valid. TODO
    $action_link_plugin->actionIsValid();

    // Find out if use has access. TODO
    $action_link_plugin->userHasAccess();

    // If either of those fail, bail with an error
    // we need to ask the link style plugin what to do about that.
    
    $link_style_plugin_manager = \Drupal::service('plugin.manager.action_link');
    $action_link_style_plugin = $link_style_plugin_manager->createInstance($action_link_plugin_style_id, array(
      // No -- that expects too much of the target entity!
      //'config_entity' => $config_entity,
      //'target_entity' => $target_entity,
    ));

    // Change state.
    $action_link_plugin->changeState($entity_type, $entity_id, $new_state);

    // Argh, confirm form will def want something else, won't it?
    // we output a whole damn form!!!

    // Get the output from the AL link plugin.
    // BUT!!! if the ALLP id is in the path, then youcould transform the
    // link style just by hacking the path!
    // (You can do that on flag already!!!)


    $action_link_style_plugin->getRequestOutput();

    // DONE!!


    // So what do we need to know at this point?

    // 1. check validity

    // $action_link->getPlugin->checkValidity()
    // return 404 if not valid


    // 2. check access

    // $action_link->getPlugin->checkAccess()
    // return AccessDenied if no access

    // 3. make state transition.
    //$action_link->getPlugin->changeState($new_state);


    return $action_link_style_plugin->getRequestOutput();

    // TODO! redirect to the entity just acted upon.
    //return new RedirectResponse(url('user'));
  }

}
