<?php

namespace Drupal\action_link_formatter_links\Plugin\ActionLinkStyle;

use Drupal\action_link_formatter_links\DisplayBuildAlter;
use Drupal\action_link\Ajax\ActionLinkMessageCommand;
use Drupal\action_link\Entity\ActionLinkInterface;
use Drupal\action_link\Plugin\ActionLinkStyle\Ajax;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Replaces the ajax link style for action links in field formatters.
 *
 * This returns the entire field output as an AJAX replacement rather than just
 * the action link when the link is clicked, so that the updated field value is
 * shown.
 *
 * If the field is output using custom display options, the returned field is
 * rendered using the default display settings, as there is no way to know
 * what custom display settings were used.
 *
 * @ActionLinkStyle(
 *   id = "ajax_entity_field",
 *   label = @Translation("Ajax Entity Field"),
 *   no_ui = TRUE,
 * )
 */
class AjaxEntityField extends Ajax {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The display build alter service.
   *
   * @var \Drupal\action_link_formatter_links\DisplayBuildAlter
   */
  protected $displayBuildAlter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('action_link_formatter_links.display_build_alter'),
    );
  }

  /**
   * Creates a AjaxEntityField instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository service.
   * @param \Drupal\action_link_formatter_links\DisplayBuildAlter $display_build_alter
   *   The display build alter service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    DisplayBuildAlter $display_build_alter
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $renderer);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
    $this->displayBuildAlter = $display_build_alter;
  }

  /**
   * {@inheritdoc}
   */
  public function handleActionRequest(bool $action_completed, Request $request, RouteMatchInterface $route_match, ActionLinkInterface $action_link, string $direction, string $state, UserInterface $user, ...$parameters): Response {
    // If the action did not complete, fall back to the parent AJAX plugin,
    // which only returns the action links and not the field formatter.
    // It's likely that if the action didn't complete, the displayed value is
    // out of date, but changing that would look weird since we're also
    // telling the user that the link has had no effect.
    if (!$action_completed) {
      // Switch the link style so we get the right link link style on the
      // replaced action links.
      $action_link->set('link_style', $this->getPluginId());

      return parent::handleActionRequest(
        $action_completed,
        $request,
        $route_match,
        $action_link,
        $direction,
        $state,
        $user,
        ...$parameters
      );
    }

    [$entity] = $parameters;
    $field_name = $action_link->getStateActionPlugin()->getTargetFieldName();

    // Create a new AJAX response. We return the rendered field in multiple view
    // modes, partly because passing the view mode that's being displayed into
    // the action link route controller is horribly fiddly, as it would have to
    // be passed into the action link render element lazy builder, but also
    // because this covers the corner case where a field value is shown on the
    // page in more than one view mode. Drupal core's ReplaceCommand fails
    // silently if it does not find the given selector.
    $response = new AjaxResponse();

    // Hardcode the delta since multiple deltas aren't properly supported yet
    // anyway.
    $delta = 0;

    // View modes can be disabled, but what that means is that if they are
    // requested for rendering, the 'default' view display is used as a
    // fallback (see https://www.drupal.org/project/drupal/issues/2844203).
    // Therefore:
    //  - If the default display shows our field, we must output all disabled
    //    view modes, and all enabled view modes that show our field.
    //  - If the default display does not show our field, we output only all
    //    enabled view modes that show our field, as a disabled view mode won't
    //    show our field when it falls back to the default display.
    // We don't check for our settings in the formatter, as we should return
    // any output of this field regardless of whether it include action links.
    $view_modes = $this->entityDisplayRepository->getViewModes($entity->getEntityTypeId());
    $view_modes_to_output = [];

    $default_view_display = $this->entityDisplayRepository->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), 'default');
    $default_view_display_has_field = !empty($default_view_display->getComponent($field_name));

    // Add the 'default' view mode explicitly, as it's not returned from
    // getViewModes(). See https://www.drupal.org/project/drupal/issues/3411185.
    $view_modes_to_output[] = 'default';

    foreach ($view_modes as $view_mode_name => $view_mode_info) {
      // Skip view modes that are not intended for rendering.
      // TODO: Core should provide a way to know which ones these are rather
      // than having to hardcode them!
      if (in_array($view_mode_name, ['rss', 'token', 'search_index'])) {
        continue;
      }

      if ($default_view_display_has_field && empty($view_mode_info['status'])) {
        $view_modes_to_output[] = $view_mode_name;

        // No need to load its display, since we already know we're outputting
        // it.
        continue;
      }

      $view_display = $this->entityDisplayRepository->getViewDisplay($entity->getEntityTypeId(), $entity->bundle(), $view_mode_name);
      if ($view_display->getComponent($field_name)) {
        $view_modes_to_output[] = $view_mode_name;
      }
    }

    $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    foreach ($view_modes_to_output as $view_mode_name) {
      // Render the field in the view mode.
      // Because of our alterations in hook_entity_display_build_alter(), the
      // rendered field will include the action links.
      $field_build = $view_builder->viewField($entity->get($field_name), $view_mode_name);

      $selector = '.' . $this->displayBuildAlter->getWrapperCssClass($action_link, $entity, $field_name, $delta, $view_mode_name);
      $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($field_build[$delta]));
      $response->addCommand($replace);
    }

    // Additionally, return a replacement for the custom view mode selector.
    // Use the default display to render the field, as we have no way of knowing
    // how the field was rendered (or at least not without major pain and faff;
    // we'd have to serialise the display options to pass them to the action
    // link URL).
    $field_build = $view_builder->viewField($entity->get($field_name), 'default');
    $selector = '.' . $this->displayBuildAlter->getWrapperCssClass($action_link, $entity, $field_name, $delta, EntityDisplayBase::CUSTOM_MODE);
    $replace = new ReplaceCommand($selector, $this->renderer->renderPlain($field_build[$delta]));
    $response->addCommand($replace);

    // We only need to consider the case that the action completed.
    $message = $action_link->getMessage($direction, $state, ...$parameters);
    if ($message) {
      // Add a message command to the stack.
      // Put the message on the specific link that was clicked.
      $message_selector = ".action-link-id-{$action_link->id()}.action-link-direction-{$direction}";
      $message_command = new ActionLinkMessageCommand($message_selector, $message);
      $response->addCommand($message_command);
    }

    return $response;
  }

}
