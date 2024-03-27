<?php

namespace Drupal\action_link\Entity;

use Drupal\action_link\Plugin\ActionLinkStyle\ActionLinkStyleInterface;
use Drupal\action_link\Plugin\StateAction\StateActionInterface;
use Drupal\action_link\Token\StateChangeTokenData;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides the Action Link entity.
 *
 * An action link entity holds configuration to describe ways in which a user
 * can use a link to make a change to the site. The logic of the action link
 * is supplied by a state action plugin.
 *
 * An action link controls something on the site: this is called the operand.
 * The action link has multiple possible states, and operating the action link
 * causes the current state to change. The action link has one or more
 * directions, which define which states are reachable for the current state.
 * These abstract concepts are best explained with examples:
 *  - An action link which controls the 'published' field on a node has two
 *    states, 'published' and 'unpublished', and one direction, which is simply
 *    to toggle between the two states. At any time, only one state is reachable
 *    because the other state is current.
 *  - An action link which increments or decrements an integer field on an
 *    entity has two directions, 'inc' and 'dec', and an infinite number of
 *    states. At any time, only two states are reachable: the values one less
 *    and one greated than the current value.
 *  - An action link which adds a product to the user's cart has two directions,
 *    'add' and 'remove', and an infinite number of states. If the cart has
 *    no items for that product, only one state is reachable, otherwise two
 *    states are reachable: one more of the item and one less.
 *  - An action link which changes the workflow state of an entity has a state
 *    for each workflow state. The directions correspond to the transitions
 *    of the workflow. Reachable states are those which have a transition from
 *    the current state.
 *
 * Several things determine together whether a user can use a link:
 *  - Operability: Whether the action link makes any sense at all, in any
 *    direction. If an action link is not operable, then no directions can be
 *    used. In this situation, only another type of change to the site will
 *    allow an action link to become operable.
 *  - Reachability: Whether the given target state makes sense from the current
 *    state. The reachability of a state can change if the action link's state
 *    is changed.
 *  - Permission access: Each action link exposes permissions which the user
 *    must have to use it. Depending on the plugin there may be permissions to
 *    use particular directions or reach certain states.
 *  - Operand access: The permissions for the thing controlled by the action
 *    link.
 *
 * Some examples:
 *  - An action link which controls the 'published' field on a node is always
 *    operable. If the node is published, then only the 'unpublished' state is
 *    reachable, and vice versa. Operand access requires the user to have
 *    access to publish or unpublish that node.
 *  - An action link which increments or decrements an integer field on an
 *    entity is operable if the entity has a value for that field, and not
 *    operable if the field value is empty. There are two reachable states: the
 *    next largest and next smallest value from the current value. Operand
 *    access requires the user to have access to edit that entity and the
 *    particular field.
 *  - An add to cart link is always operable, as a user can always at least
 *    remove an item from their cart. If there are currently none of the product
 *    in the cart, then only one state is reachable: '1'. If the product is out
 *    of stock, then only one state is reachable: the state that is 1 less than
 *    the current number in the cart. Otherwise, two states are reachable: one
 *    less and one more than the current number in the cart. Operand access
 *    depends on whether the user has permissions to access the product's store
 *    and buy the product.
 *  - An action link which changes the workflow state of an entity is operable
 *    if the entity is configured to use the workflow. A state is reachable if
 *    the workflow defines a transition from the current state. Operand access
 *    requires the user to have access to use the workflow on the entity, and
 *    to have access for the particular transition.
 *
 * @ConfigEntityType(
 *   id = "action_link",
 *   label = @Translation("Action Link"),
 *   label_collection = @Translation("Action Links"),
 *   label_singular = @Translation("action link"),
 *   label_plural = @Translation("action links"),
 *   label_count = @PluralTranslation(
 *     singular = "@count action link",
 *     plural = "@count action links",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\action_link\Entity\Handler\ActionLinkAccess",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\action_link\Form\ActionLinkForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\action_link\Entity\Handler\ActionLinkListBuilder",
 *   },
 *   admin_permission = "administer action_link entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plugin_id",
 *     "plugin_config",
 *     "link_style",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/action_link/add",
 *     "canonical" = "/admin/structure/action_link/{action_link}",
 *     "collection" = "/admin/structure/action_link",
 *     "edit-form" = "/admin/structure/action_link/{action_link}/edit",
 *     "delete-form" = "/admin/structure/action_link/{action_link}/delete",
 *   },
 * )
 */
class ActionLink extends ConfigEntityBase implements ActionLinkInterface {

  /**
   * Machine name.
   *
   * @var string
   */
  protected $id = '';

  /**
   * Name.
   *
   * @var string
   */
  protected $label = '';

  /**
   * State action plugin ID.
   *
   * @var string
   */
  protected $plugin_id;

  /**
   * State action plugin configuration.
   *
   * @var array
   */
  protected $plugin_config = [];

  /**
   * The state action plugin collecton.
   *
   * @var \Drupal\Component\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $stateActionPluginCollection;

  /**
   * The link style plugin ID.
   *
   * @var string
   */
  protected $link_style;

  /**
   * The overridden link style plugin ID.
   *
   * This is for temporary overrides of the link style and is not saved.
   *
   * @var string
   */
  protected $link_style_override;

  /**
   * The link style plugin collection.
   *
   * @var \Drupal\Component\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $linkStylePluginCollection;

  /**
   * {@inheritdoc}
   */
  public function advanceState(AccountInterface $account, string $state, ...$parameters): void {
    $this->getStateActionPlugin()->advanceState($account, $state, ...$parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getStateActionPlugin(): StateActionInterface {
    return $this->getStateActionPluginCollection()->get($this->plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkStylePlugin(): ActionLinkStyleInterface {
    $link_style_to_use = $this->link_style_override ?? $this->link_style;
    return $this->getLinkStylePluginCollection()->get($link_style_to_use);
  }

  /**
   * {@inheritdoc}
   */
  public function setOverrideLinkStyle(string $link_style_plugin_id) {
    $this->link_style_override = $link_style_plugin_id;
    unset($this->linkStylePluginCollection);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    $collections = [];
    if ($this->getStateActionPluginCollection()) {
      $collections['plugin_config'] = $this->getStateActionPluginCollection();
    }
    if ($this->getLinkStylePluginCollection()) {
      // The plugin has no configuration.
      $collections['link_style_collection'] = $this->getLinkStylePluginCollection();
    }
    return $collections;
  }

  /**
   * Encapsulates the creation of the state action plugin collection.
   *
   * @return \Drupal\Component\Plugin\DefaultSingleLazyPluginCollection
   *   The action link plugin collection.
   */
  protected function getStateActionPluginCollection() {
    if (!$this->stateActionPluginCollection && $this->plugin_id) {
      $this->stateActionPluginCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.action_link_state_action'),
        $this->plugin_id, $this->plugin_config
      );
    }
    return $this->stateActionPluginCollection;
  }

  /**
   * Encapsulates the creation of the action link style plugin collection.
   *
   * @return \Drupal\Component\Plugin\DefaultSingleLazyPluginCollection
   *   The action link style plugin collection.
   */
  protected function getLinkStylePluginCollection() {
    // Horrible workaround for the form element's inner element's value getting
    // set and then the resulting value *array* for the outer element being used
    // by copyFormValuesToEntity().
    // See https://drupal.stackexchange.com/questions/314389/interaction-between-form-element-plugins-and-config-entity-plugin-collections
    if (is_array($this->link_style)) {
      return NULL;
    }


    if (!$this->linkStylePluginCollection && $this->link_style) {
      $link_style_to_use = $this->link_style_override ?? $this->link_style;

      $this->linkStylePluginCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.action_link_style'),
        $link_style_to_use,
        []
      );
    }
    return $this->linkStylePluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function buildLinkSet(?AccountInterface $user, ...$parameters) {
    assert(count($parameters) === count(array_filter($parameters, function ($v) {
      return is_null($v) || is_scalar($v);
    })), "Parameters may only contain scalar values.");

    return [
      '#type' => 'action_linkset',
      '#action_link' => $this->id(),
      '#user' => $user?->id() ?? NULL,
      '#dynamic_parameters' => $parameters,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildSingleLink(string $direction, ?AccountInterface $user, ...$parameters): array {
    assert(count($parameters) === count(array_filter($parameters, function ($v) {
      return is_null($v) || is_scalar($v);
    })), "Parameters may only contain scalar values.");

    return [
      '#type' => 'action_linkset',
      '#action_link' => $this->id(),
      '#user' => $user?->id() ?? NULL,
      '#direction' => $direction,
      '#dynamic_parameters' => $parameters,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkReachable(string $direction, string $state, AccountInterface $account, ...$parameters): bool {
    $next_state = $this->getStateActionPlugin()->getNextStateName($direction, $account, ...$parameters);

    return ($next_state === $state);
  }

  /**
   * {@inheritdoc}
   */
  public function checkGeneralAccess(AccountInterface $account, ...$parameters): AccessResult {
    // There's no equivalent method for this on the plugin because the
    // permission is the same for all action links.
    $main_permission_access = AccessResult::allowedIfHasPermission($account, "use {$this->id()} action links");

    $operand_general_access = $this->getStateActionPlugin()->checkOperandGeneralAccess($this, $account, ...$parameters);

    $access_result = $main_permission_access->andIf($operand_general_access);

    return $access_result;
  }

  /**
   * {@inheritdoc}
   */
  public function checkStateAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    $state_action_plugin = $this->getStateActionPlugin();

    // This method calls the general access plugin methods as well as the
    // state-specific, since the general access plugin access result overrides
    // the state-specific.

    // The check for the action link main permission isn't delegated to the
    // plugin as it's the same for every action link entity.
    $general_permission_access = AccessResult::allowedIfHasPermission($account, "use {$this->id()} action links");
    $state_permission_access = $state_action_plugin->checkPermissionStateAccess($this, $direction, $state, $account, ...$parameters);

    $action_link_access = $general_permission_access->orIf($state_permission_access);

    $operand_general_access = $state_action_plugin->checkOperandGeneralAccess($this, $account, ...$parameters);
    $operand_state_access = $state_action_plugin->checkOperandStateAccess($this, $direction, $state, $account, ...$parameters);

    $operand_access = $operand_general_access->orIf($operand_state_access);

    // Access to both the action link and the operand is required.
    $access_result = $action_link_access->andIf($operand_access);

    return $access_result;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkLabel(string $direction, string $state, ...$parameters): string {
    $label = $this->getStateActionPlugin()->getLinkLabel($direction, $state, ...$parameters);

    $data = [
      'action_link' => $this,
      'action_state' => new StateChangeTokenData(
        $this,
        $direction,
        $state,
      ),
    ] + $this->getStateActionPlugin()->getTokenData(...$parameters);

    $label = \Drupal::token()->replace($label, $data);

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage(string $direction, string $state, ...$parameters): string {
    $message = $this->getStateActionPlugin()->getMessage($direction, $state, ...$parameters);

    $data = [
      'action_link' => $this,
      'action_state' => new StateChangeTokenData(
        $this,
        $direction,
        $state,
      ),
    ] + $this->getStateActionPlugin()->getTokenData(...$parameters);

    $message = \Drupal::token()->replace($message, $data);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getFailureMessage(string $direction, string $state, ...$parameters): string {
    $message = $this->getStateActionPlugin()->getFailureMessage($direction, $state, ...$parameters);

    $data = [
      'action_link' => $this,
      'action_state' => new StateChangeTokenData(
        $this,
        $direction,
        $state,
      ),
    ] + $this->getStateActionPlugin()->getTokenData(...$parameters);

    $message = \Drupal::token()->replace($message, $data);

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    \Drupal::service('router.builder')->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  public function getPermissions(): array {
    $replacements = [
      '%label' => $this->label(),
    ];

    // General permission.
    $permissions["use {$this->id()} action links"] = [
      'title' => t('Use %label action links', $replacements),
      'description' => t('Allows use of %label action links in all directions and to all states', $replacements),
    ];

    // Permissions from the plugin.
    $permissions += $this->getStateActionPlugin()->getStateActionPermissions($this);

    foreach ($permissions as &$permission) {
      $permission['dependencies']['config'][] = $this->getConfigDependencyName();

      $permission['dependencies'] += $this->getDependencies();
    }

    return $permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    return 'action_link.action_link.' . $this->id();
  }

}
