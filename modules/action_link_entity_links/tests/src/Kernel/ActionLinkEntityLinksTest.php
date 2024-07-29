<?php

namespace Drupal\Tests\action_link_entity_links\Kernel;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\EntityViewTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests output of action links in entity links.
 *
 * @group action_link_entity_links
 */
class ActionLinkEntityLinksTest extends KernelTestBase {

  use EntityViewTrait;
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
    'field',
    'action_link',
    'action_link_entity_links',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('user');
    $this->installConfig('system');
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->renderer = $this->container->get('renderer');

    $this->setUpCurrentUser([], [], TRUE);

    // Create two node types, one with the field the action link controls, and
    // one without.
    $alpha = $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'alpha',
    ]);
    $alpha->save();

    $beta = $this->entityTypeManager->getStorage('node_type')->create([
      'type' => 'beta',
    ]);
    $beta->save();

    $this->entityTypeManager->getStorage('field_storage_config')->create([
      'field_name' => 'boolean',
      'entity_type' => 'node',
      'type' => 'boolean',
      'settings' => [],
      'cardinality' => 1,
    ])->save();

    $this->entityTypeManager->getStorage('field_config')->create([
      'field_name' => 'boolean',
      'entity_type' => 'node',
      'bundle' => 'alpha',
      'settings' => [],
    ])->save();

    // Create an action link which toggles the field.
    /** @var \Drupal\action_link\Entity\ActionLinkInterface $action_link */
    $action_link = $this->entityTypeManager->getStorage('action_link')->create([
      'id' => 'test_boolean',
      'label' => 'Test',
      'plugin_id' => 'boolean_field',
      'plugin_config' => [
        'entity_type_id' => 'node',
        'field' => 'boolean',
      ],
      'link_style' => 'nojs',
      'output' => [
        'entity_links' => [],
      ],
    ]);
    $action_link->save();
  }

  /**
   * Tests the action link is shown in the node links.
   */
  public function testNodeEntityLinks() {
    $this->assertCount(1, $this->entityTypeManager->getStorage('action_link')->loadByUsingOutput('entity_links'));

    $node_storage = $this->entityTypeManager->getStorage('node');

    // Create a node for which the action link is operable: it is of the bundle
    // that has the field, and it has a value set for the field.
    $alpha_node = $node_storage->create([
      'type' => 'alpha',
      'title' => '1',
      'boolean' => FALSE,
    ]);
    $alpha_node->save();

    $build = $this->buildEntityView($alpha_node);
    $html = DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $this->renderer->renderInIsolation($build),
      deprecatedCallable: fn() => $this->renderer->renderPlain($build),
    );

    // Be very lenient about the parameters of the link to avoid a false
    // negative. We're not testing the parameters are correct; the tests in the
    // main module cover that.
    $this->assertStringContainsString('<a href="/action-link/test_boolean/nojs/toggle/', $html);

    // Create a node of the other bundle which does not have the field.
    $beta_node = $node_storage->create([
      'type' => 'beta',
      'title' => '1',
    ]);
    $beta_node->save();

    $build = $this->buildEntityView($beta_node);
    $html = DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $this->renderer->renderInIsolation($build),
      deprecatedCallable: fn() => $this->renderer->renderPlain($build),
    );

    $this->assertStringNotContainsString('<a href="/action-link/test_boolean/nojs/toggle/', $html);
  }

}
