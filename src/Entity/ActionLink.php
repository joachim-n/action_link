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
  public function advanceState(AccountInterface $account, string $state, ...$parameters) {
    $this->getStateActionPlugin()->advanceState($account, $state, $parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getStateActionPlugin(): StateActionInterface {
    return $this->getActionLinkPluginCollection()->get($this->plugin_id);
  }


  public function getPluginCollections() {
    if ($this->getActionLinkPluginCollection()) {
      return [
        'plugin_config' => $this->getActionLinkPluginCollection(),
      ];
    }
    return [];
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


    if (!$this->actionLinkPluginCollection && $this->plugin_id) {
      $this->actionLinkPluginCollection = new DefaultSingleLazyPluginCollection(
        \Drupal::service('plugin.manager.action_link_state_action'),
        $this->plugin_id, $this->plugin_config
      );
    }
    return $this->actionLinkPluginCollection;
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

  public function getUrl(): string {
    // URL includes name of next state:
    // /action-link/ENTITY_ID/NEXT_STATE/PARAMETERS.../TOKEN
    // PARAMETERS up to plugin - could include user ID, entity to act on, etc.
    //
    // if the system is alreayd in NEXT STATE then nothing happens.
  }

  public function checkOperability(AccountInterface $account, string $state, ...$parameters) {
    return $this->getStateActionPlugin()->checkOperability($account, $state, ...$parameters);
  }

  public function checkAccess() {
    //
  }

  public function getRedirectUrl(AccountInterface $account, ...$parameters) {
    return $this->getStateActionPlugin()->getRedirectUrl($account, ...$parameters);
  }

}
