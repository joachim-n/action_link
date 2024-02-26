<?php

namespace Drupal\Tests\action_link\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Core\Entity\EntityInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the entity field state action plugins.
 *
 * @group action_link
 */
class ActionLinkEntityFieldKernelTest extends KernelTestBase implements LoggerInterface {

  use UserCreationTrait;
  use LoggerTrait;

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'node',
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
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The current user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('user');
    $this->installConfig('system');
    $this->installSchema('system', 'sequences');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');

    // Register this class as a logger so we can fail on errors generated during
    // the requests.
    // See https://www.drupal.org/project/drupal/issues/2903456.
    $this->container->get('logger.factory')->addLogger($this);

    $this->state = $this->container->get('state');
    $this->messenger = $this->container->get('messenger');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->stateActionManager = $this->container->get('plugin.manager.action_link_state_action');
    $this->actionLinkStorage = $this->entityTypeManager->getStorage('action_link');

    // Checking access to routes requires the extra setup this does.
    $this->user = $this->setUpCurrentUser();

    // Mock the CSRF token access check so we don't need to pass them in to
    // our requests.
    $csrf_access = $this->prophesize(CsrfAccessCheck::class);
    $csrf_access->access(Argument::cetera())->willReturn(AccessResult::allowed());
    $this->container->set('access_check.csrf', $csrf_access->reveal());

    // Create a node type.
    $node_type = $this->entityTypeManager->getStorage('node_type')->create([
      'name' => 'alpha',
      'type' => 'alpha',
    ]);
    $node_type->save();
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, string|\Stringable $message, array $context = []): void {
    $message = strtr($message, $context);

    // We expect warnings to be logged for requests that result in a 403.
    if (str_contains($message, 'permission is required')) {
      return;
    }

    // Fail the test on any log message: any errors or warnings during a request
    // will be obscured by the error handling system. This makes them visible.
    $level_label = \Drupal\Core\Logger\RfcLogLevel::getLevels()[$level];
    $this->fail("Log $level_label: $message");
  }

  /**
   * Tests the boolean field state action on the 'published' node field.
   */
  public function testPublishedBooleanField() {
    $http_kernel = $this->container->get('http_kernel');

    $node_storage = $this->entityTypeManager->getStorage('node');

    $node = $node_storage->create([
      'type' => 'alpha',
      'title' => '1',
    ]);
    $node->save();

    // Test the boolean field plugin with the 'status' field, which requires
    // admin access.
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_status',
      'label' => 'Test',
      'plugin_id' => 'boolean_field',
      'plugin_config' => [
        'entity_type_id' => 'node',
        'field' => 'status',
      ],
      'link_style' => 'nojs',
    ]);
    $action_link->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    $parameters_combined = [
      [
        'entity' => $node->id(),
      ],
      [
        'entity' => $node,
      ],
    ];

    // 1. User has no access to an action link that toggles the 'status'
    // boolean field, because it is an admin-restricted field.
    // Access is denied because the user doesn't have access to edit the node.
    $user_no_access = $this->createUser(['access content']);
    // Set the current user for the request.
    $this->setCurrentUser($user_no_access);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access, ...$parameters_combined);
    // or use element::children???
    $this->assertEmpty($links);

    $request = Request::create("/action-link/test_status/nojs/toggle/false/{$user_no_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // 2. User who can edit the node but not change published status.
    // Access is denied because the 'published' field has special field access
    // control.
    $user_with_edit_access = $this->createUser(['access content', 'edit any alpha content']);
    $this->setCurrentUser($user_with_edit_access);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access, ...$parameters_combined);
    $this->assertEmpty($links);

    $request = Request::create("/action-link/test_status/nojs/toggle/false/{$user_with_edit_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // 3. User with admin access who can publish and unpublish nodes, but
    // doesn't have access to the action link.
    $user_with_admin_access = $this->createUser(['access content', 'bypass node access', 'administer nodes']);
    $this->setCurrentUser($user_with_admin_access);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_with_admin_access, ...$parameters_combined);
    $this->assertEmpty($links);

    $request = Request::create("/action-link/test_status/nojs/toggle/false/{$user_with_admin_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // 4. User with access to operate the action link, but no other access.
    $user_with_link_access = $this->createUser(["use {$action_link->id()} action links"]);
    $this->setCurrentUser($user_with_link_access);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_with_link_access, ...$parameters_combined);
    $this->assertEmpty($links);

    $request = Request::create("/action-link/test_status/nojs/toggle/false/{$user_with_link_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // 5. User with access to everything.
    $user_with_access = $this->createUser(["use {$action_link->id()} action links", 'access content', 'bypass node access', 'administer nodes']);
    $this->setCurrentUser($user_with_access);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_with_access, ...$parameters_combined);
    $this->assertNotEmpty($links);

    $request = Request::create("/action-link/test_status/nojs/toggle/false/{$user_with_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    // Using the link route changed the node's status.
    $node = $this->reloadEntity($node);
    $this->assertEquals(FALSE, $node->isPublished());

    // Repeating the action with the same parameters has no effect, because the
    // node is already in the target state, and so the action is not operable.
    $request = Request::create("/action-link/test_status/nojs/toggle/false/{$user_with_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    $node = $this->reloadEntity($node);
    $this->assertEquals(FALSE, $node->isPublished());

    $request = Request::create("/action-link/test_status/nojs/toggle/true/{$user_with_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

    $node = $this->reloadEntity($node);
    $this->assertEquals(TRUE, $node->isPublished());

    // Check the action with the Ajax link type.
    $request = Request::create("/action-link/test_status/ajax/toggle/false/{$user_with_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertInstanceOf(\Drupal\Core\Ajax\AjaxResponse::class, $response);
    $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    $ajax_commands = $response->getCommands();
    $this->assertCount(2, $ajax_commands);
    $this->assertEquals('insert', $ajax_commands[0]['command']);
    $this->assertEquals('.action-link-test-status-toggle-' . $user_with_access->id() . '-' . $node->id(), $ajax_commands[0]['selector']);
  }

  /**
   * Tests the numeric field plugin.
   */
  public function testNumeric() {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $node = $node_storage->create([
      'type' => 'alpha',
      'title' => '1',
    ]);
    $node->save();

    // Test the numeric_field plugin with the 'changed' field, which doesn't
    // require admin access. (The numeric_field plugin isn't really meant to be
    // used with a timestamp field but nothing will complain.)
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_changed',
      'label' => 'Test',
      'plugin_id' => 'numeric_field',
      'plugin_config' => [
        'entity_type_id' => 'node',
        'field' => 'changed',
        'step' => '1',
      ],
      'link_style' => 'nojs',
    ]);
    $action_link->save();
    $this->container->get('router.builder')->rebuildIfNeeded();

    $parameters_combined = [
      [
        'entity' => $node->id(),
      ],
      [
        'entity' => $node,
      ],
    ];

    // User has no access to the action link, because they can't edit content.
    $user_no_access = $this->createUser([
      'use test_changed action links',
      'access content',
    ]);
    $this->setCurrentUser($user_no_access);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_no_access, ...$parameters_combined);
    $this->assertEmpty($links);

    // User who can edit the node has access to the action link.
    $user_with_edit_access = $this->createUser([
      'use test_changed action links',
      'access content',
      'edit any alpha content',
    ]);
    $this->setCurrentUser($user_with_edit_access);
    $links = $action_link->getStateActionPlugin()->buildLinkArray($action_link, $user_with_edit_access, ...$parameters_combined);
    $this->assertNotEmpty($links);
  }

  /**
   * Reloads the given entity from the storage and returns it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be reloaded.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The reloaded entity.
   */
  protected function reloadEntity(EntityInterface $entity) {
    $controller = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $controller->resetCache([$entity->id()]);
    return $controller->load($entity->id());
  }

}
