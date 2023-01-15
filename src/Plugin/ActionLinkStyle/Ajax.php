<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

/**
 * TODO: class docs.
 *
 * @ActionLinkStyle(
 *   id = "ajax",
 *   label = @Translation("JavaScript"),
 *   description = @Translation("A link which makes an AJAX JavaScript request without reloading the page.")
 * )
 */
class Ajax extends ActionLinkStyleBase {

  /**
   * Undocumented function
   *
   * This is only called if $build has at least one link.
   *
   * @param [type] $build
   * @param [type] $action_link
   * @param [type] $user
   * @param [type] ...$parameters
   */
  public function alterLinksBuild($build, $action_link, $user, ...$parameters) {
    foreach ($build as $direction => $direction_link_build) {
      $build[$direction]['#attributes']['class'][] = 'use-ajax';
    }

    // TODO!
    // $build['#attached']['library'][] = 'action_link/action_link.ajax';
  }

}
