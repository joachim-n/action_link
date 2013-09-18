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
 *
 * Modules whose use of action links is different should implement their own
 * routes and controller.
 *
 * @see \Drupal\action_link\ActionLinkConfigInterface
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
  /**
   * Return output for an action link page.
   *
   * @param $action_link_plugin_style_id
   *  The style plugin ID. This determines the format of the output. For
   *  example, the reload plugin will reload the page that the link was one; the
   *  AJAX plugin will return AJAX, and so on. Note that we react to the path
   *  that we're on rather than the action link's configuration.
   * @param $config_entity_type
   *  The type of the entity that holds configuration for this link.
   * @param $config_id
   *  The id of the entity that holds configuration for this link.
   * @param $entity_type
   *  The type of the entity that this link acts on.
   * @param $entity_id
   *  The id of the entity that this link acts on. This is known as the target
   *  entity.
   * @param $new_state
   *  A string that describes the state on the target entity that this link
   *  cycles to.
   *
   * @return
   *  Output that is suitable for the link style requested by the
   *  $action_link_plugin_style_id parameter.
   */
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
    //$action_link_plugin = new \Drupal\action_link\Plugin\StateCycler\Flag();
    $action_link_plugin = $config_entity->getStateCyclerPlugin();

    // Check validity: find out if this request is valid for the combination
    // of configuration, target entity, and destination state.
    $action_link_plugin->actionIsValid();
    // TODO return 404 if not valid

    // Check access: find out if user is allowed to perform this change.
    $action_link_plugin->userHasAccess();
    // TODO return AccessDenied if no access

    // If either of those fail, bail with an error
    // we need to ask the link style plugin what to do about that.

    // Get the link style plugin for the style ID that we've come in on, rather
    // than the one set in the config entity.
    // (This is the same behaviour as Flag on D7.)
    $link_style_plugin_manager = \Drupal::service('plugin.manager.action_link');
    $action_link_style_plugin = $link_style_plugin_manager->createInstance($action_link_plugin_style_id, array(
      // No -- that expects too much of the target entity!
      //'config_entity' => $config_entity,
      //'target_entity' => $target_entity,
    ));

    // Change the state of the target entity.
    $action_link_plugin->changeState($entity_type, $entity_id, $new_state);

    // Argh, confirm form will def want something else, won't it?
    // we output a whole damn form!!!

    // Get the output from the link style plugin.
    $action_link_style_plugin->getRequestOutput();

    return $action_link_style_plugin->getRequestOutput();
  }

}
