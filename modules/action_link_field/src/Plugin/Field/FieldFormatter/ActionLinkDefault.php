<?php

namespace Drupal\action_link_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Action linkset formatter that just shows the option from the action link.
 *
 * @FieldFormatter(
 *   id = "action_linkset_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "action_linkset",
 *   },
 *   weight = -10,
 * )
 */
class ActionLinkDefault extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {

      $elements[$delta] = $item->getValue();
    }
    return $elements;
  }

}
