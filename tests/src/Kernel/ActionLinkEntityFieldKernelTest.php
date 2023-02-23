<?php

namespace Drupal\Tests\action_link\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Prophecy\Argument;
use Stack\StackedHttpKernel;
use Symfony\Component\DependencyInjection\Loader\Configurator\Traits\PropertyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the entity field state action plugins.
 *
 * @group action_link
 */
class ActionLinkEntityFieldKernelTest extends KernelTestBase {

  use UserCreationTrait;

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

    $this->state = $this->container->get('state');
    $this->messenger = $this->container->get('messenger');
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->stateActionManager = $this->container->get('plugin.manager.action_link_state_action');
    $this->actionLinkStorage = $this->entityTypeManager->getStorage('action_link');

    // Checking access to routes requires the current user to be set up.
    $this->user = $this->setUpCurrentUser();
  }

  /**
   * Tests TODO.
   */
  public function testEntityFieldActions() {
    // Mock the CSRF token access check so we don't need to pass them in to
    // our requests.
    $csrf_access = $this->prophesize(CsrfAccessCheck::class);
    $csrf_access->access(Argument::cetera())->willReturn(AccessResult::allowed());
    $this->container->set('access_check.csrf', $csrf_access->reveal());

    $http_kernel = $this->container->get('http_kernel');

    $node_type = $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'alpha',
    ]);
    $node_type->save();

    $node_storage = $this->entityTypeManager->getStorage('node');

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
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $node = $node_storage->create([
      'type' => 'alpha',
      'title' => '1',
    ]);
    $node->save();

    // User has no access to an action link that toggles the 'status' boolean
    // field, because it is an admin-restricted field.
    $user_no_access = $this->createUser(['access content']);
    // We need to set the user we check for as the current user, as route access
    // is checked when generating links.
    $this->setCurrentUser($user_no_access);
    $links = $action_link->buildLinkSet($user_no_access, $node);
    $this->assertEmpty($links);

    // Access is denied because the user doesn't have access to edit the node.
    $request = Request::create("/action-link/test_status/nojs/toggle/false/{$user_no_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());

    // User who can edit the node but not change published status.
    $user_with_edit_access = $this->createUser(['access content', 'edit any alpha content']);
    $this->setCurrentUser($user_with_edit_access);
    $links = $action_link->buildLinkSet($user_no_access, $node);
    $this->assertEmpty($links);

    // User with admin access who can publish and unpublish nodes.
    $user_with_access = $this->createUser(['access content', 'bypass node access', 'administer nodes']);
    $this->setCurrentUser($user_with_access);
    $links = $action_link->buildLinkSet($user_with_access, $node);
    $this->assertNotEmpty($links);

    $request = Request::create("/action-link/test_status/nojs/toggle/false/{$user_with_access->id()}/{$node->id()}");
    $response = $http_kernel->handle($request);
    $this->assertEquals(Response::HTTP_FOUND, $response->getStatusCode());

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
