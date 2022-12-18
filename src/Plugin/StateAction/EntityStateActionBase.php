<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Base class for State Action plugins that operate on an entity.
 */
abstract class EntityStateActionBase extends StateActionBase {



  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(AccountInterface $account, EntityInterface $entity = NULL): ?Url {
    if ($entity->hasLinkTemplate('canonical')) {
      // Redirect back to the entity. A passed in destination query parameter
      // will automatically override this.
      $url_info = $entity->toUrl();

      $options['absolute'] = TRUE;
      $url = Url::fromRoute($url_info->getRouteName(), $url_info->getRouteParameters(), $options);

      return $url;
    }
  }

}