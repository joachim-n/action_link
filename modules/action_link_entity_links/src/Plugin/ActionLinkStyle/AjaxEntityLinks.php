<?php

namespace Drupal\action_link_entity_links\Plugin\ActionLinkStyle;

use Drupal\Core\Session\AccountInterface;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\ActionLinkStyle\Ajax;
use Drupal\Core\Render\Element;

/**
 * Replaces the ajax link style for action links in entity links.
 *
 * @ActionLinkStyle(
 *   id = "ajax_entity_links",
 *   label = @Translation("Ajax Entity Links"),
 *   no_ui = TRUE,
 * )
 */
class AjaxEntityLinks extends Ajax {

  /**
   * {@inheritdoc}
   */
  public function alterLinksBuild(array &$build, ActionLinkInterface $action_link, AccountInterface $user, array $named_parameters, array $scalar_parameters) {
    parent::alterLinksBuild(
      $build,
      $action_link,
      $user,
      $named_parameters,
      $scalar_parameters,
    );

    foreach (Element::children($build) as $direction) {
      $build[$direction]['#wrapper_tag'] = 'li';
    }
    // dsm($build);
    // Alters the render array for an action link entity's set of links.
  }

}
