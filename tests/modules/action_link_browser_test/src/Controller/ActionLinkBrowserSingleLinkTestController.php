<?php

namespace Drupal\action_link_browser_test\Controller;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Component\Utility\Random;

/**
 * Controller that outputs action links as singles for testing.
 *
 * Use at the path '/action_link_browser_test_single/{action_link}'.
 */
class ActionLinkBrowserSingleLinkTestController {

  /**
   * Callback for the action_link_browser_test.action_link_browser_test_single route.
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

    $random = new Random();

    // Individual links.
    foreach ($action_link->getStateActionPlugin()->getDirections() as $direction => $label) {
      $build['singles'][$direction] = [
        '#type' => 'container',
      ];
      $build['singles'][$direction]['link'] = $action_link->buildSingleLink($direction, NULL, ...$parameters);

      $build['singles'][$direction]['filler'] = [
        '#markup' => $random->sentences(10),
      ];
    }

    return $build;
  }

}
