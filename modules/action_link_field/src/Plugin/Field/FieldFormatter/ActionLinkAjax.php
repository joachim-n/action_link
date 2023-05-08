<?php

namespace Drupal\action_link_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * Action linkset formatter that shows action links as AJAX links.
 *
 * @FieldFormatter(
 *   id = "action_linkset_ajax",
 *   label = @Translation("AJAX links"),
 *   field_types = {
 *     "action_linkset",
 *   },
 * )
 */
class ActionLinkAjax extends ActionLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {

      $elements[$delta] = $item->getValue();

      // Change the link style.
      $elements[$delta]['links']['#link_style'] = 'ajax';
    }
    return $elements;
  }

}
