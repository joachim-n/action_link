<?php

/**
 * @file
 * Contains \YADAYADA.
 */

namespace Drupal\action_link;

use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityAccessControllerInterface;

/**
 * Defines a default implementation for config entity access controllers.
 *
 * This may be used by config entity types for their access controller if all
 * they require to control access is a single user permission. For example, all
 * the config entities are managed under a single admin path, accessible with
 * the permission 'administer foobar'.
 *
 * @todo: document the 'access_controller_permission' property this requires in
 * the entity type annotation.
 */
class ActionLinkAccessController extends EntityAccessController implements EntityAccessControllerInterface {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    //dsm($operation);
    
    //$entity_info = $entity->entityInfo();

    /*
    // Throw an exception if we don't have what we need on the entity type info.
    if (!isset($entity_info['access_controller_permission'])) {
      throw new \Exception("Entity types using ConfigEntityAccessControllerBase as their access controller must define the 'access_controller_permission' property.");
    }
    */
    return TRUE;

    $permission = $entity_info['access_controller_permission'];

    return $account->hasPermission($permission);
  }

}
