<?php

/**
 * @file
 * Contains \Drupal\action_link\Plugin\ActionLink\Reload.
 */

namespace Drupal\action_link\Plugin\ActionLink;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Annotation\Translation;
use Drupal\Component\Annotation\Plugin;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The reload action link style.
 *
 * This link style causes a reload of the page.
 *
 * TODO: token validation!
 *
 * @Plugin(
 *   id = "reload",
 *   label = "Normal link",
 *   description = "A normal non-JavaScript request will be made and the current page will be reloaded."
 * )
 */
class Reload  {

  /**
   * Return the output for a request on an action link.
   *
   * The reload link style causes a reload of the page the link was on.
   *
   * @param $next_state
   *  The state that the target entity can be advanced to next.
   *  (This will be needed by ajax link type.)
   */
  function getRequestOutput($next_state) {
    return new RedirectResponse(url(current_path(), array('absolute' => TRUE)));
  }

}
