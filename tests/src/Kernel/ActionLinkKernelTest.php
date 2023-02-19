<?php

namespace Drupal\Tests\action_link\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Test case class TODO.
 *
 * @group action_link
 */
class ActionLinkKernelTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'action_link',
    'action_link_test_plugins',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The state action manager.
   *
   * @var \Drupal\action_link\StateActionManager
   */
  protected $stateActionManager;

  /**
   * The action_link storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionLinkStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('user');
    $this->installConfig('system');
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');

    $this->state = $this->container->get('state');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->stateActionManager = $this->container->get('plugin.manager.action_link_state_action');
    // $this->actionLinkStorage = $this->container->get('storage:action_link');

  }

  /**
   * Tests the TODO.
   */
  public function testLinkGeneration() {
    $action_link_storage = $this->entityTypeManager->getStorage('action_link');
    $action_link = $action_link_storage->create([
      'id' => 'test_null',
      'label' => 'Test',
      'plugin_id' => 'test_null',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();

    $user_no_access = $this->createUser();
    $links = $action_link->buildLinkSet($user_no_access);
    $this->assertEmpty($links);

    $action_link = $action_link_storage->create([
      'id' => 'test_mocked_access',
      'label' => 'Test',
      'plugin_id' => 'test_mocked_access',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();

    $this->state->set('test_mocked_access:access', FALSE);
    $links = $action_link->buildLinkSet($user_no_access);
    $this->assertEmpty($links);

    $this->state->set('test_mocked_access:access', TRUE);
    $links = $action_link->buildLinkSet($user_no_access);
    $this->assertNotEmpty($links);

    //

    dump($links);

    // no access, BUT operable and access to auth: 'log in to FOO'
    // no access, and neither: nothing
    // access, not operable: nothing
    // access, operable: link

    // todo:
    // generate link
    // no link for bad user
    // no link for bad AL entity
    // no link for bad dynamic params
    // access???
    //
  }

}
