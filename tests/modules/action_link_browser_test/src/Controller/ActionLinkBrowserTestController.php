<?php

namespace Drupal\action_link_browser_test\Controller;

/**
 * Controller that outputs all action links for testing.
 */
class ActionLinkBrowserTestController {

  /**
   * Callback for the action_link_browser_test.action_link_browser_test route.
   */
  public function content() {
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $user = \Drupal::currentUser();
    $node = $entity_type_manager->getStorage('node')->load(1);

    $action_links = $entity_type_manager->getStorage('action_link')->loadMultiple();
    // $action_links = [$action_links['test_date']];

    /** @var \Drupal\action_link\Entity\ActionLinkInterface */
    foreach ($action_links as $action_link_id => $action_link) {
      // dsm($action_link);
      $build[$action_link_id] = [
        '#type' => 'container',
      ];

      $dynamic_parameter_names = $action_link->getStateActionPlugin()->getDynamicParameterNames();
      // dump($dynamic_parameter_names);
      $parameters = [];
      if ($dynamic_parameter_names) {
        // QUick and dirty! Assume just entity.
        $parameters[] = $node;
      }

      $build[$action_link_id]['links'] = $action_link->buildLinkSet($user, ...$parameters);

      // break;
    }

    // dsm($build);
    return $build;


    // Other node.
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


    $action_link = $entity_type_manager->getStorage('action_link')->load('test_action_link');
    // dsm($action_link);
    // $action_link->set('plugin_id', 'boolean_field');
    // $action_link->set('plugin_config', [
    //   'entity_type' => 'node',
    //   'field' => 'status',
    // ])->save();

    $user = \Drupal::currentUser();
    $node = $entity_type_manager->getStorage('node')->load(1);

    // This action link is a toggle, so it can deduce next state automatically.
    $build['link'] = $action_link->getStateActionPlugin()->getLink($user, $node)->toRenderable();

    $action_link = $entity_type_manager->getStorage('action_link')->load('test_numeric');
    // $action_link->set('plugin_config', [
    //     'entity_type' => 'node',
    //     'field' => 'field_integer',
    //     'step' => 1,
    //   ])->save();

    $user = \Drupal::currentUser();
    $node = $entity_type_manager->getStorage('node')->load(1);

    // $build['numeric'] = $action_link->getLink($user, $node, 'inc')->toRenderable();
    $build['numeric'] = $action_link->buildLinkSet($user, $node);

    ///// date
    $action_link = $entity_type_manager->getStorage('action_link')->load('test_date');
    // $action_link->set('plugin_config', [
    //     'entity_type' => 'node',
    //     'field' => 'field_date',
    //     'step' => 'P1D',
    //   ])->save();

    $user = \Drupal::currentUser();
    $node = $entity_type_manager->getStorage('node')->load(1);

    $build['date'] = $action_link->getLink($user, $node, 'inc')->toRenderable();

    // CacheableMetadata::createFromRenderArray($render)
    //   ->addCacheableDependency($access)
    //   ->applyTo($render);


    // $action_link->plugin_id =

    // $entity_type_manager->getStorage('action_link')->create([
    //   'id' => 'test_action_link',
    // ])->save();

    return $build;
  }

}
