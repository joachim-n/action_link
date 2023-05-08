<?php

namespace Drupal\action_link_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Action linkset formatter that shows action links as reload links.
 *
 * @FieldFormatter(
 *   id = "action_linkset_nojs",
 *   label = @Translation("Reload links"),
 *   field_types = {
 *     "action_linkset",
 *   },
 * )
 */
class ActionLinkNojs extends ActionLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {

      $elements[$delta] = $item->getValue();

      // Change the link style.
      $elements[$delta]['links']['#link_style'] = 'nojs';
    }
    return $elements;
  }

}
