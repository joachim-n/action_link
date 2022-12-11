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

    // This action link is a toggle, so it can deduce next state automatically.
    $build['link'] = $action_link->getLink($user, $node)->toRenderable();

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
