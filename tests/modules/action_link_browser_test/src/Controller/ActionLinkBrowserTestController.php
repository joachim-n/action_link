<?php

namespace Drupal\action_link_browser_test\Controller;

use Drupal\action_link\Entity\ActionLinkInterface;

/**
 * Controller that outputs an action link for testing.
 *
 * Use at the path '/action_link_browser_test/{action_link}'.
 */
class ActionLinkBrowserTestController {

  /**
   * Callback for the action_link_browser_test.action_link_browser_test route.
   */
  public function content(ActionLinkInterface $action_link) {
    // Track the number of times this is called in total.
    $call_count = \Drupal::state()->get('ActionLinkBrowserTestController:call_count', 0);
    $call_count++;
    \Drupal::state()->set('ActionLinkBrowserTestController:call_count', $call_count);

    $build = [];

    $dynamic_parameter_names = $action_link->getStateActionPlugin()->getDynamicParameterNames();
    $parameters = [];
    if ($dynamic_parameter_names) {
      // Quick and dirty! Assume that if there is a parameter, it is an entity
      // AND assume that in that case, the test will have created it.
      $parameters[] = 1;
    }

    // Whole linkset.
    $build['linkset'] = [
      '#type' => 'container',
    ];
    $build['linkset']['links'] = $action_link->buildLinkSet(NULL, ...$parameters);

    return $build;
  }

}
