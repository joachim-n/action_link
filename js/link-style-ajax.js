/**
 * @file
 * Defines Javascript behaviors for the ajax action link style.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.actionLinkAttach = {
    attach: function (context, settings) {
      // When a message DOM element is being attached, add an event to remove it
      // after its CSS animation has completed. The behaviours are called for
      // the message element itself.
      // @see Drupal.AjaxCommands.prototype.insert()
      if (context instanceof Element) {
        if (context.classList.contains('action-link-ajax-message')) {
          context.addEventListener(
            'animationend',
            event => event.target.remove(),
            false
          );
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
