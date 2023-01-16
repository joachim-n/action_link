<?php

namespace Drupal\action_link\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Shows a message when an AJAX action link is updated.
 *
 * The client side code can be found in js/link-style-ajax.js.
 *
 * @todo Cribbed from flag module. Needs work!
 */
class ActionLinkMessageCommand implements CommandInterface {

  /**
   * Identifies the action link to be flashed.
   *
   * @var string
   */
  protected $selector;

  /**
   * The message to be flashed under the link.
   *
   * @var string
   */
  protected $message;

  /**
   * Construct a message Flasher.
   *
   * @param string $selector
   *   Identifies the action link to be flashed.
   * @param string $message
   *   The message to be displayed.
   */
  public function __construct($selector, $message) {
    $this->selector = $selector;
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'actionLinkAjaxMessage',
      'selector' => $this->selector,
      'message' => $this->message,
    ];
  }

}
