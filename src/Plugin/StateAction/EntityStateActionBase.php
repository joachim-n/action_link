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
        'entity_type_id' => $element['#default_value']['entity_type_id'] ?? '',
        'field' => $element['#default_value']['field'] ?? '',
      ],
      '#required' => TRUE,
    ];

    return $plugin_form;
  }

  public static function entityFieldElementValidate(&$element, FormStateInterface $form_state, &$complete_form) {
    $element_parents = $element['#parents'];
    $plugin_form_parents = $element_parents;
    array_pop($plugin_form_parents);

    $element_value = $form_state->getValue($element_parents);

    if ($element_value) {
      // This doesn't work, because it goes into the 'container' level for the
      // plugin form element, and that copies that up one level, but the
      // copying happens later. ARGH.
      // $form_state->setValue([...$plugin_form_parents, 'entity_type_id'], $element_value['entity_type_id']);
      // $form_state->setValue([...$plugin_form_parents, 'field'], $element_value['field']);
    }

    // // ARGH hardcoded array structure :(
    // // Can't get this from slicing up $element['#parents'] because of the
    // // 'container' from the plugin form element.
    // $plugin_configuration_values = $form_state->getValue(['plugin', 'plugin_configuration']);

    // $merged_values = $plugin_configuration_values + $element_value;

    // $form_state->setValue(['plugin', 'plugin_configuration'], $merged_values);
  }


  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // @todo Setting values on the subform state, which is the correct way,
    // doesn't work because SubformState sees that we are in the 'container'
    // element in the 'action_plugin' form element, and so the values get set
    // there. That apparently happens later than the 'action_plugin' form
    // element's valueCallback() setting the form values one level up to get rid
    // of the surplus 'container' nesting.
    // $form_state->setValue(['entity_type_id'], $values['entity_type_field']['entity_type_id']);
    // $form_state->setValue(['field'], $values['entity_type_field']['field']);
    // Therefore we do it directly, which is a hack, as this plugin shouldn't
    // be aware of the form structure it's used in.
    $form_state->getCompleteFormState()->setValue(['plugin', 'plugin_configuration', 'entity_type_id'], $values['entity_type_field']['entity_type_id']);
    $form_state->getCompleteFormState()->setValue(['plugin', 'plugin_configuration', 'field'], $values['entity_type_field']['field']);
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
  public function validateParameters(array $parameters) {
    parent::validateParameters($parameters);

    if ($parameters['entity']->getEntityTypeId() != $this->configuration['entity_type_id']) {
      throw new \ArgumentCountError(sprintf("Wrong entity type for state action plugin %s, expects %s, got %s",
        $this->getPluginId(),
        $this->configuration['entity_type_id'],
        $parameters['entity']->getEntityTypeId(),
      ));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convertParametersForRoute(array $parameters): array {
    $parameters = parent::convertParametersForRoute($parameters);

    // Convert the entity parameter to an entity ID.
    // TODO: this needs to be able to complain if a param is bad.
    // e.g. no node exists.
    $parameters['entity'] = $parameters['entity']->id();

    return $parameters;
  }

}