<?php

namespace Drupal\action_link\Utility;

/**
 * Provides helpers for working recursively with nested arrays.
 *
 * @todo Remove this when https://www.drupal.org/project/drupal/issues/3324952
 * is fixed.
 */
class NestedArrayRecursive {

  /**
   * Applies a callback to all elements of a nested array.
   *
   * @param array &$array
   *   A reference to the array to operate on.
   * @param callable $callback
   *   The callable to apply to each scalar element. This should have a
   *   signature of:
   *   @code
   *   callable(&$value, $parents)
   *   @endcode
   *   where $value is the current array value, and $parents is an array of
   *   parent keys of the value, starting with the outermost key.
   */
  public static function arrayWalkNested(&$array, callable $callback) {
    $parents = [];
    self::arrayWalkNestedRecursive($array, $callback, $parents);
  }

  /**
   * Recursive helper for arrayWalkNested().
   *
   * @param array &$array
   *   A reference to the current subarray.
   * @param callable $callback
   *   The callback which operates on the array.
   * @param array $parents
   *   The current parent keys.
   */
  protected static function arrayWalkNestedRecursive(&$array, callable $callback, array $parents) {
    foreach ($array as $key => &$value) {
      $current_parents = $parents;
      $current_parents[] = $key;
      if (is_array($value)) {
        self::arrayWalkNestedRecursive($value, $callback, $current_parents);
      }
      else {
        $callback($value, $current_parents);
      }
    }
  }

}
