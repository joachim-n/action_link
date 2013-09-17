<?php

/**
 * @file
 * Contains \Drupal\action_link\ActionLinkControllerInterface.
 */

namespace Drupal\action_link;

/**
 * Interface for action link controllers.
 *
 * Action link controllers handle the logic of what happens when a user clicks
 * the action links. This includes:
 *  - whether the action is valid (which is also checked to determine whether
 *    the link may be displayed)
 *  - whether the user has permissions to perform the action
 *  - what to do if the action is allowed.
 */
interface ActionLinkControllerInterface {



}
