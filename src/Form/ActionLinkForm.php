<?php

namespace Drupal\action_link\Form;

use Drupal\action_link\Element\StateActionPlugin;
use Drupal\action_link\Utility\Element as UtilityElement;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the default form handler for the Action Link entity.
 */
class ActionLinkForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Workaround for https://www.drupal.org/project/drupal/issues/2360639.
    $form_state->disableCache();

    /** @var \Drupal\action_link\Entity\ActionLinkInterface */
    $action_link = $this->entity;
    // dsm($action_link);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $action_link->label(),
      '#description' => $this->t('A short, descriptive title for this action link.'),
      '#maxlength' => 255,
      '#required' => TRUE,
      '#weight' => -3,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $action_link->id(),
      '#description' => $this->t('The machine-name for this action link. It may be up to 32 characters long and may only contain lowercase letters, underscores, and numbers. It will be used in URLs and in all API calls.'),
      '#weight' => -2,
      '#machine_name' => [
        'exists' => ['Drupal\action_link\Entity\ActionLink', 'load'],
        'source' => ['label'],
      ],
      '#disabled' => !$action_link->isNew(),
      '#required' => TRUE,
    ];

    $form['plugin'] = [
      '#type' => 'state_action_plugin',
      '#title' => $this->t('Action plugin'),
      '#required' => TRUE,
      '#default_value' => [
        'plugin_id' => $action_link->get('plugin_id'),
        'plugin_configuration' => $action_link->get('plugin_config'),
      ],
    ];

    // $form['link_style_details'] = [
    // ];

    $form['link_style'] = [
      '#type' => 'action_link_style_plugin',
      '#title' => $this->t('Link style'),
      '#required' => TRUE,
      '#default_value' => $action_link->get('link_style'),
      '#plugin_type' => 'action_link.link_style',
      '#options_element_type' => 'radios',
    ];

    $output_plugin_definitions = \Drupal::service('plugin.manager.action_link_output')->getApplicableDefinitions($action_link);
    $form['output'] = [
      '#type' => 'details',
      '#tree' => TRUE,
      '#title' => $this->t('Output locations'),
      '#open' => TRUE,
      '#updates_on' => [
        ['plugin', 'container', 'plugin_id'],
      ]
    ];

    if ($output_plugin_definitions) {
      foreach ($output_plugin_definitions as $output_plugin_id => $output_plugin_definition) {
        $form['output'][$output_plugin_id] = [
          '#type' => 'checkbox',
          '#title' => $output_plugin_definition['label'],
          '#default_value' => isset($action_link->get('output')[$output_plugin_id]),
        ];
      }
    }
    else {
      $form['output']['no_plugins'] = [
        '#markup' => $this->t('No ouput location options are available for this action link'),
      ];
    }

    // DOESN'T WORK
    // $form['#after_build'][] = '::afterBuildOurs';
    // URGH dependency on plugin class URGH.
    $form['plugin']['#process'][] = [StateActionPlugin::class, 'processPlugin'];
    $form['plugin']['#process'][] = '::afterBuildOurs';

    return $form;
  }

  public function afterBuildOurs(array $element, FormStateInterface $form_state) {
    // dsm($element);
    $element['container']['plugin_id']['#ajax']['callback'] = get_class() . '::pluginDropdownCallback';

    // ARGH we need to hand over to OUTPUT plugins tp say 'hey what do you need to react to?'
    // BUT NOT here?
    // there's 2-level dependency, here we need to add our own ajax
    // and in our own ajax we need to hand over to plugins
    // so Links plugin ADDS TOTALLY NEW AJAXYNESS to entity type IF IT'S EVEN IN THE FORM
    // to say 'yo, ajax react, update the output plugins area'
    //
    // FUCKSTICKS

    return $element;
  }

  /**
   * AJAX callback for the plugin ID select element.
   */
  public static function pluginDropdownCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = $triggering_element['#array_parents'];

    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Walk the entire form recursively, looking for elements which say they
    // update on the triggering element.
    $collected_elements = [];
    UtilityElement::walkChildrenRecursive($form, function($element) use ($triggering_element_parents, &$collected_elements) {
      if (!isset($element['#updates_on'])) {
        return;
      }

      foreach ($element['#updates_on'] as $updates_on) {
        if ($updates_on == $triggering_element_parents) {
          $collected_elements[] = $element;
        }
      }
    });
    // dsm($collected_elements);

    $response = new AjaxResponse();

    foreach ($collected_elements as $collected_element) {
      $html = $this->renderer->renderRoot($collected_element);

      // TODO location!
      $response->addCommand(new InsertCommand(NULL, $html));

      $response->addAttachments($collected_element['#attached']);
    }

    // TOFDO!!!


    // TODO: if $collected_elements is empty, there's probably been a problem:
    // output an AJAX error message?
    //         $response->addCommand(new AlertCommand($error));


    // Retrieve the element to be rendered.
    // $form = NestedArray::getValue($form, $form_parents);
    //



    // /** @var \Drupal\Core\Ajax\AjaxResponse $response */
    // $routeMatch = \Drupal::routeMatch();



    // $response = \Drupal::service('main_content_renderer.ajax')->renderResponse($form, $request, $routeMatch);

    // $response->addCommand(new InsertCommand('#edit-output', '<p>POOP</p>'));


    return $response;
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    // dsm($form_state->getValues());

    $entity->set('plugin_id', $form_state->getValue(['plugin', 'plugin_id']));
    $entity->set('plugin_config', $form_state->getValue(['plugin', 'plugin_configuration']) ?? []);

    $entity->set('link_style', $form_state->getValue(['link_style']));

    $output_value = [];
    foreach (array_keys(array_filter($form_state->getValue(['output']))) as $output_plugin_id) {
      $output_value[$output_plugin_id] = [];
    }
    $entity->set('output', $output_value);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    $t_args = ['%name' => $this->entity->label()];
    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('The action link %name has been updated.', $t_args));
    }
    elseif ($status == SAVED_NEW) {
      $this->messenger()->addStatus($this->t('The action link %name has been added.', $t_args));
    }

    // $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $status;
  }

}
