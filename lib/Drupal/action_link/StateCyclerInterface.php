<?php

/**
 * @file
 * Contains \Drupal\action_link\StateCyclerInterface.
 */

namespace Drupal\action_link;

/**
 * Interface for state cyclers.
 *
 * The state cycler deals with making the change to state that is requested
 * when the user when follows an action link.
 *
 * Furthermore, the state cycler controls access to this, and thus should be
 * checked when outputting an action link.
 */
interface StateCyclerInterface {

  function actionIsValid();

  function userHasAccess();

  /**
   * Perform the state change on the target entity.
   *
   * @param $new_state
   *  The name of the new state to advance the target entity to.
   *
   * @return
   *  The name of the state that the target entity may move onto now that it has
   *  been changed. With a boolean toggle, this will be the opposite of the
   *  $new_state parameter, but there is nothing stopping an implementation of
   *  this interface from having more than two states.
   */
  function changeState($new_state);

}
