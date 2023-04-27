<?php

namespace Drupal\Tests\action_link\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Browser test for action links.
 *
 * @group action_link
 */
class ActionLinkBrowserTest extends BrowserTestBase {

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
    'action_link_browser_test',
  ];

  protected $defaultTheme = 'stark';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->actionLinkStorage = $this->entityTypeManager->getStorage('action_link');
  }

  /**
   * Tests the TODO.
   */
  public function testLazyBuilderCaching() {
    // Create an action link.
    $action_link = $this->actionLinkStorage->create([
      'id' => 'test_always',
      'label' => 'Test',
      'plugin_id' => 'test_always',
      'plugin_config' => [],
      'link_style' => 'nojs',
    ]);
    $action_link->save();

    $this->drupalGet('action_link_browser_test/test_always');
  }

}
