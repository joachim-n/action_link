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
 *
 * @Plugin(
 *   id = "reload",
 *   description = "WRITE ME TODO."
 * )
 */
class Reload  {

  /**
   * Return the output for a request on an action link.
   */
  function getRequestOutput() {
    return new RedirectResponse(url(current_path(), array('absolute' => TRUE)));
    return 'hellO!';
  }
  
}