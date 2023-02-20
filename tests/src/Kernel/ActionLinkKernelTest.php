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
    $this->actionLinkStorage = $this->entityTypeManager->getStorage('action_link');

    // Checking access to routes requires the current user to be set up.
    $this->user = $this->setUpCurrentUser();

  }

  /**
   * Tests the access to links.
   */
  public function testLinkGeneration() {
    $action_link = $this->actionLinkStorage->create([
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

    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_mocked_access',
      'label' => 'Test',
      'plugin_id' => 'test_mocked_access',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();

    // Deny access.
    $this->state->set('test_mocked_access:access', FALSE);
    $links = $action_link->buildLinkSet($user_no_access);
    $this->assertEmpty($links);

    // Grant access.
    $this->state->set('test_mocked_access:access', TRUE);
    $links = $action_link->buildLinkSet($user_no_access);
    $this->assertNotEmpty($links);
  }

  public function testRouteAccess() {
    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_mocked_access',
      'label' => 'Test',
      'plugin_id' => 'test_mocked_access',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();

    // Mock the CSRF token access check so we don't need to pass them in to
    // our requests.
    $csrf_access = $this->prophesize(CsrfAccessCheck::class);
    $csrf_access->access(Argument::cetera())->willReturn(AccessResult::allowed());
    $this->container->set('access_check.csrf', $csrf_access->reveal());

    // Deny access.
    $this->state->set('test_mocked_access:access', FALSE);

    $request = Request::create("/action-link/test_mocked_access/nojs/change/cake/{$this->user->id()}");

    $http_kernel = $this->container->get('http_kernel');

    // this causes issues - kernel test, repeat request ARGH
    // $response = $http_kernel->handle($request);
    // $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // Grant access.
    $this->state->set('test_mocked_access:access', TRUE);

    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());
  }




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
