<?php

namespace Drupal\Tests\action_link\Functional;

use Drupal\dynamic_page_cache\EventSubscriber\DynamicPageCacheSubscriber;
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

  /**
   * {@inheritdoc}
   */
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
   * Tests the lazy builders for action links work correctly.
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
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $user_1 = $this->drupalCreateUser([]);
    $this->drupalLogin($user_1);

    $this->drupalGet('action_link_browser_test/test_always');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'MISS');
    // The page's action link has the user's ID.
    $this->assertSession()->linkByHrefExists('action-link/test_always/nojs/change/cake/' . $user_1->id());

    $user_2 = $this->drupalCreateUser([]);
    $this->drupalLogin($user_2);

    // Second user gets a cache hit: action link does not make the page
    // uncacheable.
    $this->drupalGet('action_link_browser_test/test_always');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderEquals(DynamicPageCacheSubscriber::HEADER, 'HIT');
    // The page's action link has the user's ID.
    $this->assertSession()->linkByHrefExists('action-link/test_always/nojs/change/cake/' . $user_2->id());

    // The controller was only called once.
    $call_count = \Drupal::state()->get('ActionLinkBrowserTestController:call_count', 0);
    $this->assertEquals(1, $call_count);
  }

}
