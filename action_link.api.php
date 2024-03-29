<?php

/**
 * @file
 * Hooks provided by the Action links module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Perform alterations on State Action definitions.
 *
 * @param array $info
 *   Array of information on State Action plugins.
 */
function hook_state_action_info_alter(array &$info) {
  // Change the class of the 'foo' plugin.
  $info['foo']['class'] = SomeOtherClass::class;
}

/**
 * Perform alterations on Action Link Style definitions.
 *
 * @param array $info
 *   Array of information on Action Link Style plugins.
 */
function hook_action_link_style_info_alter(array &$info) {
  // Change the class of the 'foo' plugin.
  $info['foo']['class'] = SomeOtherClass::class;
}

/**
 * @} End of "addtogroup hooks".
 */
