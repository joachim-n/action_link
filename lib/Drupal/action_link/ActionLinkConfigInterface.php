<?php

/**
 * @file
 * Contains \Drupal\action_link\ActionLinkConfigInterface.
 */

namespace Drupal\action_link;

/**
 * Interface for action link config entities.
 *
 * This interface should be used by config entity types that provide
 * configuration for action links whose routes are handled by the
 * ActionLinkController.
 *
 * @see \Drupal\action_link\Controller\ActionLinkController
 */
interface ActionLinkConfigInterface {

  // Get my style plugin

  // Get my state cycler

  /**
   * Generate the render array for the action link.
   *
   * This will confer with the StateCycler plugin to figure out which state
   * the link should allow cycling to.
   *
   * @param $entity
   *  The entity to create a link for.
   *
   * @return
   *  A render array containing the link.
   */
  public function buildLink($entity);

}
