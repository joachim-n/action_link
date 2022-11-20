<?php

namespace Drupal\action_link\Entity;

use Drupal\action_link\Plugin\StateAction\StateActionInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides the Action Link entity.
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

  /*

  - state action plugin
  - UI strings



  */

  // controller already done access. but not operability access!? or has it? DECIDE
  public function advance(AccountInterface $account, string $state, ...$parameters) {
    $this->getStateActionPlugin()->advance($account, $state, $parameters);
  }


  /**
   * {@inheritdoc}
   */
  public function getStateActionPlugin(): StateActionInterface {
    return $this->getActionLinkPluginCollection()->get($this->plugin_id);
  }


  public function getPluginCollections() {
  // TODO! config key =>
  //    * @return \Drupal\Component\Plugin\LazyPluginCollection[]
  //  *   An array of plugin collections, keyed by the property name they use to
  //  *   store their configuration.

    return [
      'plugin_config' => $this->getActionLinkPluginCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the flag type's plugin collection.
   *
   * @return \Drupal\Component\Plugin\DefaultSingleLazyPluginCollection
   *   The flag type's plugin collection.
   */
  // TODO rename!
  protected function getActionLinkPluginCollection() {
    // temp!
    // $this->plugin_id = 'boolean_field';


    if (!$this->actionLinkPluginCollection) {
      $this->actionLinkPluginCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.action_link_state_action'),
        $this->plugin_id, $this->plugin_config
      );
    }
    return $this->actionLinkPluginCollection;
  }


  // and params!
  // but SOME params are live -> ONLY entity and user? or other shit too??
  // and SOME params are config!
  public function getLink(AccountInterface $user, ...$parameters): Link {
    $plugin = $this->getStateActionPlugin();

    $route_parameters = $plugin->convertParametersForRoute($parameters);
    // ARGH convert a node entity to an ID??

    $url = Url::fromRoute('action_link.action_link', [
      'action_link' => $this->id(),
      'state' => $plugin->getNextStateName($user, ...$parameters),
      'user' => $user->id(),
      'parameters' => implode('/', $route_parameters),
    ]);
    return Link::fromTextAndUrl('TEXT', $url);
  }

  public function getUrl(): string {
    // URL includes name of next state:
    // /action-link/ENTITY_ID/NEXT_STATE/PARAMETERS.../TOKEN
    // PARAMETERS up to plugin - could include user ID, entity to act on, etc.
    //
    // if the system is alreayd in NEXT STATE then nothing happens.
  }

  public function checkOperability() {
    // ask plugin.
  }

  public function checkAccess() {
    //
  }

}
