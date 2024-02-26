<?php

namespace Drupal\action_link\Token;

/**
 * Value object representing a state change to get tokens from.
 */
class StateChangeTokenData {

  /**
   * Constructor.
   *
   * @param \Drupal\action_link\Entity\ActionLinkInterface $actionLink
   *   The action link entity.
   * @param string $direction
   *   The direction.
   * @param string $state
   *   The target state.
   */
  public function __construct(
    // @todo Make these readonly for Drupal 10.
    public $actionLink,
    public string $direction,
    public string $state,
  ) {

  }

}
