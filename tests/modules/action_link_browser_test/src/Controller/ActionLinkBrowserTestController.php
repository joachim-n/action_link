<?php

namespace Drupal\action_link_browser_test\Controller;

/**
 * TODO: class docs.
 */
class ActionLinkBrowserTestController {

  /**
   * Callback for the action_link_browser_test.action_link_browser_test route.
   */
  public function content() {
    $entity_type_manager = \Drupal::service('entity_type.manager');

    $action_link = $entity_type_manager->getStorage('action_link')->load('test_action_link');
    // dsm($action_link);
    // $action_link->set('plugin_id', 'boolean_field');
    // $action_link->set('plugin_config', [
    //   'entity_type' => 'node',
    //   'field' => 'status',
    // ])->save();

    $user = \Drupal::currentUser();
    $node = $entity_type_manager->getStorage('node')->load(1);

    $build['link'] = $action_link->getLink($user, $node)->toRenderable();


    // $action_link->plugin_id =

    // $entity_type_manager->getStorage('action_link')->create([
    //   'id' => 'test_action_link',
    // ])->save();

    return $build;
  }

}
