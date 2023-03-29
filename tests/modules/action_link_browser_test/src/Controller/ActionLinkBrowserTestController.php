<?php

namespace Drupal\action_link_browser_test\Controller;

use Drupal\action_link\Entity\ActionLinkInterface;

/**
 * Controller that outputs all action links for testing.
 */
class ActionLinkBrowserTestController {

  /**
   * Callback for the action_link_browser_test.action_link_browser_test route.
   */
  public function content(ActionLinkInterface $action_link = NULL) {
    $build = [];

    $entity_type_manager = \Drupal::service('entity_type.manager');
    $user = \Drupal::currentUser();

    $dynamic_parameter_names = $action_link->getStateActionPlugin()->getDynamicParameterNames();
    $parameters = [];
    if ($dynamic_parameter_names) {
      // Quick and dirty! Assume parameter is an entity.
      $node = $entity_type_manager->getStorage('node')->load(1);
      $parameters[] = $node;
    }

    // Whole linkset.
    $build['linkset'] = [
      '#type' => 'container',
    ];
    $build['linkset']['links'] = $action_link->buildLinkSet($user, ...$parameters);

    // Individual links.
    foreach ($action_link->getStateActionPlugin()->getDirections() as $direction => $label) {
      $build['singles'][$direction]  = [
        '#type' => 'container',
      ];
      $build['singles'][$direction]['link'] = $action_link->buildSingleLink($direction, $user, ...$parameters);
    }

    return $build;

    // TODO: Links on other node.
    $node = $entity_type_manager->getStorage('node')->load(4);
    $build['other'] = $action_link->buildLinkSet($user, $node);

    return $build;

    // $action_links = $entity_type_manager->getStorage('action_link')->loadMultiple();
    // $action_links = [$action_links['test_date']];

    foreach ($action_links as $action_link_id => $action_link) {
      // dsm($action_link);
      $build[$action_link_id . '4'] = [
        '#type' => 'container',
      ];

      $build[$action_link_id . '4']['links'] = $action_link->buildLinkSet($user, $node);

      // break;
    }


    // Test repeat links!
    // $build['repeat'] = [
    //   '#type' => 'container',
    // ];

    // $build['repeat']['links'] = $action_link->buildLinkSet($user, $node);


    return $build;
  }

}
