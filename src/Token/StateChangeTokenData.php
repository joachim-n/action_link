<?php

namespace Drupal\action_link\Token;

class StateChangeTokenData {

  public function __construct(
    // @todo Make these readonly for Drupal 10.
    public $actionLink,
    public string $direction,
    public string $state,
  )
  {

  }

}