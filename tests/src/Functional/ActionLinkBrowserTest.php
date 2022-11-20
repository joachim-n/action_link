<?php

namespace Drupal\Tests\action_link\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test case class TODO.
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
    'action_link_browser_test',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get('entity_type.manager');

  }

  /**
   * Tests the TODO.
   */
  public function testMyTest() {
    // TODO: test code here.
  }

}
