<?php

namespace Drupal\action_link\Token;

/**
 * Value object representing a state change to get tokens from.
 */
class StateChangeTokenData {

  public function __construct(
    // @todo Make these readonly for Drupal 10.
    public $actionLink,
    public string $direction,
    public string $state,
  ) {

  }

}
