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
    // TODO
    // Does the action actually make sense?
    return TRUE;
  }

  function userHasAccess() {
    // TODO
    return TRUE;
  }

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
  function changeState($new_state) {
    dsm('I am changing state now!');

    $this->target_entity->{$this->toggle_property} = $new_state;
    $this->target_entity->save();

    // For boolean entity properties, return an integer value.
    $next_state = (int) !$new_state;
    return $next_state;
  }

}
