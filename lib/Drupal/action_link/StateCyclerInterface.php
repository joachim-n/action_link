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

  function changeState($new_state);

}
