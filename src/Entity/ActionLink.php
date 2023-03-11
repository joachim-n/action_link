<?php

namespace Drupal\action_link\Entity;

use Drupal\action_link\Plugin\ActionLinkStyle\ActionLinkStyleInterface;
use Drupal\action_link\Plugin\StateAction\StateActionInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

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
 *
 * Several things determine together whether a user can use a link:
 *  - Operability: Whether the action link makes any sense at all, in any
 *    direction. For example, if an action link toggles a boolean field on an
 *    entity, it is only considered operable when the field on an entity has a
 *    value. The operability of an action link means no directions can be used.
 *    In this situation, only another type of change to the site will allow an
 *    action link to become operable.
 *  - Reachability: Whether the given target state makes sense from the current
 *    state. The reachability of a state can change if the action link's state
 *    is changed.
 *  - Permission access: Each action link exposes permissions which the user
 *    must have to use it. Depending on the plugin there may be permissions to
 *    use particular directions or reach certain states.
 *  - Operand access: The permissions for the thing controlled by the action
 *    link. For example, if an action link controls an entity field, then access
 *    to edit the entity and its field is required.
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
   * Action link plugin.
   *
   * @var string
   */
  protected $plugin_id;

  protected $plugin_config = [];

  protected $actionLinkPluginCollection;

  protected $linkStylePluginCollection;

  protected $link_style;

  /*

  - state action plugin
  - UI strings



  */

  // controller already done access. but not operability access!? or has it? DECIDE
  public function advanceState(AccountInterface $account, string $state, ...$parameters) {
    $this->getStateActionPlugin()->advanceState($account, $state, ...$parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getStateActionPlugin(): StateActionInterface {
    return $this->getActionLinkPluginCollection()->get($this->plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkStylePlugin(): ActionLinkStyleInterface {
    return $this->getLinkStylePluginCollection()->get($this->link_style);
  }


  public function getPluginCollections() {
    $collections = [];
    if ($this->getActionLinkPluginCollection()) {
      $collections['plugin_config'] = $this->getActionLinkPluginCollection();
    }
    if ($this->getLinkStylePluginCollection()) {
      $collections['link_style_collection'] = $this->getLinkStylePluginCollection();
    }
    return $collections;
  }

  /**
   * Encapsulates the creation of the action link plugin collection.
   *
   * @return \Drupal\Component\Plugin\DefaultSingleLazyPluginCollection
   *   The flag type's plugin collection.
   */
  // TODO rename!
  protected function getActionLinkPluginCollection() {
    if (!$this->actionLinkPluginCollection && $this->plugin_id) {
      $this->actionLinkPluginCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.action_link_state_action'),
        $this->plugin_id, $this->plugin_config
      );
    }
    return $this->actionLinkPluginCollection;
  }

  protected function getLinkStylePluginCollection() {
    // Horrible workaround for the form element's inner element's value getting
    // set and then the resulting value *array* for the outer element being used
    // by copyFormValuesToEntity().
    // See https://drupal.stackexchange.com/questions/314389/interaction-between-form-element-plugins-and-config-entity-plugin-collections
    if (is_array($this->link_style)) {
      return NULL;
    }
    // dsm($this->link_style);
    // return;
    if (!$this->linkStylePluginCollection && $this->link_style) {
      $this->linkStylePluginCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.action_link_style'),
        $this->link_style,
        []
      );
    }
    return $this->linkStylePluginCollection;
  }

  /**
   * Gets a render array of all the operable links for the user.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user to get links for. TODO ARGH WANT TO ALLOW EASY DEFAULT TO MEAN CURRENT USER!
   * @param [type] ...$parameters
   */
  public function buildLinkSet(AccountInterface $user, ...$parameters) {
    $plugin = $this->getStateActionPlugin();
    // ARGH need to pass entity to plugin!
    return $plugin->buildLinkSet($this, $user, ...$parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function checkReachable(string $direction, string $state, AccountInterface $account, ...$parameters): bool {
    $next_state = $this->getStateActionPlugin()->getNextStateName($direction, $account, ...$parameters);

    return ($next_state == $state);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccess(string $direction, string $state, AccountInterface $account, ...$parameters): AccessResult {
    $main_permission_access = AccessResult::allowedIfHasPermission($account, "use {$this->id()} action links");

    $specific_permission_access = $this->getStateActionPlugin()->checkPermissionAccess($this, $direction, $state, $account, ...$parameters);

    $action_access = $this->getStateActionPlugin()->checkOperandAccess($this, $direction, $state, $account, ...$parameters);

    $access_result = $main_permission_access;
    $access_result->orIf($specific_permission_access);
    $access_result->andIf($action_access);

    return $action_access;
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
    // General permission.
    $permissions["use {$this->id()} action links"] = [
      'title' => t('Use %label action links', [
        '%label' => $this->label(),
      ]),
    ];

    // Permissions from the plugin.
    $permissions += $this->getStateActionPlugin()->getStateActionPermissions($this);

    foreach ($permissions as &$permission) {
      $permission['dependencies']['config'][] = $this->getConfigDependencyName();

      $permission['dependencies'] += $this->getDependencies();
    }

    return $permissions;
  }

}
