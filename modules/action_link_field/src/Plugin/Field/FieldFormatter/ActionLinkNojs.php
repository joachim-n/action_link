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
      // DOESN"T WORK - get wrong response!
      $elements[$delta]['links']['#link_style'] = 'nojs';
    }
    return $elements;
  }

}
