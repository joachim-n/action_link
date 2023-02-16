<?php

namespace Drupal\action_link_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\action_link\ActionLinkStyleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * TODO: class docs.
 *
 * TODO: also a 'default' formatter that just shows the option from the action link.
 *
 * @FieldFormatter(
 *   id = "action_linkset_ajax",
 *   label = @Translation("AJAX links"),
 *   field_types = {
 *     "action_linkset",
 *   },
 * )
 */
class ActionLinkAjax extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {

      // TODO! change link style!
      $elements[$delta] = $item->getValue();
    }
    return $elements;
  }

}
