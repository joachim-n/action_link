<?php

namespace Drupal\action_link\Plugin\StateAction;

use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;

/**
 * Base class for State Action plugins that operate on an entity.
 *
 * This expects an 'entity' dynamic parameter.
 */
abstract class EntityStateActionBase extends StateActionBase {

  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $plugin_form = [];

    $plugin_form['entity_type_field'] = [
      '#type' => 'entity_type_field',
      '#title' => $this->t('Entity field'),
      '#element_validate' => [
        [static::class, 'entityFieldElementValidate'],
      ],
      '#default_value' => [
        'entity_type_id' => $element['#default_value']['plugin_configuration']['entity_type_id'] ?? '',
        'field' => $element['#default_value']['plugin_configuration']['field'] ?? '',
      ],
    ];

    return $plugin_form;
  }

  public static function entityFieldElementValidate(&$element, FormStateInterface $form_state, &$complete_form) {
    $element_value = $form_state->getValue($element['#parents']);

    // ARGH hardcoded array structure :(
    // Can't get this from slicing up $element['#parents'] because of the
    // 'container' from the plugin form element.
    $plugin_configuration_values = $form_state->getValue(['plugin', 'plugin_configuration']);

    $merged_values = $plugin_configuration_values + $element_value;

    $form_state->setValue(['plugin', 'plugin_configuration'], $merged_values);
  }

  /*
 get links:
  - get directions
    - is there a next state? AHA!




  */

  public function getActionRoute(ActionLinkInterface $action_link): Route {
    $route = parent::getActionRoute($action_link);

    $route->setOption('parameters', [
      'entity' => [
        'type' => 'entity:node', // TODO!!!
      ],
    ]);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl(AccountInterface $account, EntityInterface $entity = NULL): ?Url {
    if ($entity->hasLinkTemplate('canonical')) {
      // Redirect back to the entity. A passed in destination query parameter
      // will automatically override this.
      $url_info = $entity->toUrl();

      $options['absolute'] = TRUE;
      $url = Url::fromRoute($url_info->getRouteName(), $url_info->getRouteParameters(), $options);

      return $url;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertParametersForRoute(array $parameters): array {
    // Convert the entity parameter to an entity ID.
    // TODO: this needs to be able to complain if a param is bad.
    // e.g. no node exists.
    $parameters['entity'] = $parameters['entity']->id();

    return $parameters;
  }

}