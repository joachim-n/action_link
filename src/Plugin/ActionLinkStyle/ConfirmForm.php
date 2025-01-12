<?php

namespace Drupal\action_link\Plugin\ActionLinkStyle;

use Drupal\action_link\Attribute\ActionLinkStyle;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Link style which takes the user to a form to confirm the action.
 *
 * The action is carried out when the user submits the form. The user is then
 * redirected to the original page where they clicked the action link.
 */
#[ActionLinkStyle(
  id: 'confirm_form_page',
  label: new TranslatableMarkup('Confirmation form'),
  description: new TranslatableMarkup('A link which takes the user to a page showing a confirmation form.'),
  handle_state_change: TRUE,
)]
class ConfirmForm extends ActionLinkStyleBase implements FormInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder'),
      $container->get('redirect.destination'),
      $container->get('messenger'),
    );
  }

  /**
   * Creates a ConfirmForm instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination helper.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $form_builder,
    RedirectDestinationInterface $redirect_destination,
    MessengerInterface $messenger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formBuilder = $form_builder;
    $this->redirectDestination = $redirect_destination;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function alterLinksBuild(array &$build, ActionLinkInterface $action_link, AccountInterface $user, array $dynamic_parameters, array $scalar_parameters) {
    // Add a destination query parameter to links for this plugin, so that the
    // form submission returns to the original page.
    foreach ($build as $direction => $direction_link_build) {
      $query_options = [
        'query' => $this->redirectDestination->getAsArray(),
      ];

      $build[$direction]['#link']['#url']->mergeOptions($query_options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleActionRequest(bool $success, Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, ...$parameters): Response|array {
    if ($success) {
      // If the action can be carried out, show the confirmation form.
      // Submitting the form advances the action's state.
      return $this->formBuilder->getForm($this, $request, $action_link, $direction, $state, $user, ...$parameters);
    }
    else {
      // If the action can't be carried out, redirect to the referrer.
      $message = $action_link->getFailureMessage($direction, $state, ...$parameters);
      $this->messenger->addMessage($message);

      if ($request->headers->get('referer')) {
        $response = new RedirectResponse($request->headers->get('referer'));
      }
      else {
        // This shouldn't happen outside of tests.
        $response = new RedirectResponse(Url::fromRoute('<front>')->toString());
      }

      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'action_link_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $args = $form_state->getBuildInfo()['args'];

    [
      $request,
      $action_link,
      $direction,
      $state,
      $user,
    ] = $args;
    $parameters = array_slice($args, 4);

    $form['#title'] = 'Confirm action';

    $form['#attributes']['class'][] = 'confirmation';

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $action_link->getLinkLabel($direction, $state, $parameters),
      '#button_type' => 'primary',
    ];

    if ($request->headers->get('referer')) {
      $url = Url::fromUri($request->headers->get('referer'));
    }
    else {
      // This shouldn't happen outside of tests.
      $url = Url::fromRoute('<front>')->toString();
    }

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'dialog-cancel']],
      '#url' => $url,
    ];

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // There is no validation needed. In the event that the form is outdated
    // because the state has been changed elsewhere, this will be detected by
    // ActionLinkController, and this plugin will return a redirect response
    // rather build the form, which means the form submission never reaches the
    // form API.
    // @see static::handleActionRequest()
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $args = $form_state->getBuildInfo()['args'];

    [
      $request,
      $action_link,
      $direction,
      $state,
      $user,
    ] = $args;
    $parameters = array_slice($args, 4);

    $action_link->advanceState($user, $state, ...$parameters);

    $message = $action_link->getMessage($direction, $state, ...$parameters);
    $this->messenger->addMessage($message);
  }

}
