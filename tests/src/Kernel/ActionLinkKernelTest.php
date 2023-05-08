<?php

namespace Drupal\Tests\action_link\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests basic operation of action links.
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
    $this->messenger = $this->container->get('messenger');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->stateActionManager = $this->container->get('plugin.manager.action_link_state_action');
    $this->actionLinkStorage = $this->entityTypeManager->getStorage('action_link');

    // Checking access to routes requires the current user to be set up.
    $this->user = $this->setUpCurrentUser();
  }

  /*
   * Tests access and operability checks on building and accessing links
   */
  public function testLinkAccess() {
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_mocked_control',
      'label' => 'Test',
      'plugin_id' => 'test_mocked_control',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();

    // Checking access to routes requires the current user to be set up.
    // @todo Change this when we add proxy functionality.
    $user_no_access = $this->setUpCurrentUser();

    $http_kernel = $this->container->get('http_kernel');

    // Mock the CSRF token access check so we don't need to pass them in to
    // our requests.
    $csrf_access = $this->prophesize(CsrfAccessCheck::class);
    $csrf_access->access(Argument::cetera())->willReturn(AccessResult::allowed());
    $this->container->set('access_check.csrf', $csrf_access->reveal());

    // No access at all.
    $this->state->set('test_mocked_control:permission_access', AccessResult::forbidden());
    $this->state->set('test_mocked_control:operand_access', AccessResult::forbidden());
    // Get the plain array of links from the plugin, to bypass the lazy builder
    // and the action linkset theme.
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access);
    $this->assertEmpty($links);

    $request = Request::create("/action-link/test_mocked_control/nojs/change/cake/{$user_no_access->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // Permission access only.
    $this->state->set('test_mocked_control:permission_access', AccessResult::allowed());
    $this->state->set('test_mocked_control:operand_access', AccessResult::forbidden());
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access);
    $this->assertEmpty($links);

    $request = Request::create("/action-link/test_mocked_control/nojs/change/cake/{$user_no_access->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // Operand access only.
    $this->state->set('test_mocked_control:permission_access', AccessResult::forbidden());
    $this->state->set('test_mocked_control:operand_access', AccessResult::allowed());
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access);
    $this->assertEmpty($links);

    $request = Request::create("/action-link/test_mocked_control/nojs/change/cake/{$user_no_access->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // Both access, but not operable.
    $this->state->set('test_mocked_control:permission_access', AccessResult::allowed());
    $this->state->set('test_mocked_control:operand_access', AccessResult::allowed());
    $this->state->set('test_mocked_control:operability', FALSE);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access);
    $this->assertEmpty($links);

    $request = Request::create("/action-link/test_mocked_control/nojs/change/cake/{$user_no_access->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    $messages = $this->messenger->messagesByType(MessengerInterface::TYPE_STATUS);
    $this->assertEquals([0 => 'Unable to perform the action. The link may be outdated.'], $messages);
    $this->messenger->deleteAll();

    // Operable, but next state not reachable.
    $this->state->set('test_mocked_control:operability', TRUE);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access);
    $this->assertNotEmpty($links);
    // The actual link is empty, because the next state is not reachable.
    $this->assertEmpty($links['change']['#link']);

    // The request is OK, but does nothing.
    $request = Request::create("/action-link/test_mocked_control/nojs/change/cake/{$user_no_access->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    $this->assertEquals(NULL, $this->state->get('test_mocked_control:set_state'));

    $messages = $this->messenger->messagesByType(MessengerInterface::TYPE_STATUS);
    $this->assertEquals([0 => 'Unable to perform the action. The link may be outdated.'], $messages);
    $this->messenger->deleteAll();

    // All systems go!
    $this->state->set('test_mocked_control:next_state', 'cake');
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access);
    $this->assertNotEmpty($links);
    $this->assertNotEmpty($links['change']['#link']);

    $request = Request::create("/action-link/test_mocked_control/nojs/change/cake/{$user_no_access->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    $this->assertEquals('cake', $this->state->get('test_mocked_control:set_state'));

    $messages = $this->messenger->messagesByType(MessengerInterface::TYPE_STATUS);
    $this->assertEquals([0 => 'Changed'], $messages);
    $this->messenger->deleteAll();
  }

  /**
   * Tests building of action link routes.
   */
  public function testRouteBuilding() {
    // Mock the CSRF token access check so we don't need to pass them in to
    // our requests.
    $csrf_access = $this->prophesize(CsrfAccessCheck::class);
    $csrf_access->access(Argument::cetera())->willReturn(AccessResult::allowed());
    $this->container->set('access_check.csrf', $csrf_access->reveal());

    $http_kernel = $this->container->get('http_kernel');

    // Test that the router is kept in sync with action link entities.
    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_always_1',
      'label' => 'Test',
      'plugin_id' => 'test_always',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $request = Request::create("/action-link/test_always_1/nojs/change/cake/{$this->user->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    // Test a new action link gets a route.
    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_always_2',
      'label' => 'Test',
      'plugin_id' => 'test_always',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $request = Request::create("/action-link/test_always_2/nojs/change/cake/{$this->user->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    // Test that changing the ID causes the old route to no longer exist and a
    // new one to exist in its place.
    $action_link->set('id', 'test_always_2b');
    $action_link->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $request = Request::create("/action-link/test_always_2/nojs/change/cake/{$this->user->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

    $request = Request::create("/action-link/test_always_2b/nojs/change/cake/{$this->user->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    // Test that deleting causes the route to no longer exist.
    $action_link->delete();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $request = Request::create("/action-link/test_always_2b/nojs/change/cake/{$this->user->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
  }

  /**
   * Tests that dynamic parameters passed to buildLinkArray() are validated.
   */
  public function testLinkGenerationValidation() {
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_dynamic_parameters',
      'label' => 'Test',
      'plugin_id' => 'test_dynamic_parameters',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();

    $user = $this->setUpCurrentUser();

    $this->expectException(\ArgumentCountError::class);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user);
  }

}
