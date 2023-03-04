<?php

namespace Drupal\Tests\computed_field\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that action link computed fields are registered with the field system.
 *
 * @group computed_field
 */
class ActionLinkComputedFieldAttachingKernelTest extends KernelTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'field_ui',
    'entity_test',
    'computed_field',
    'test_computed_field_plugins',
    'test_computed_field_automatic',
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test_with_bundle');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->entityDisplayRepository = $this->container->get('entity_display.repository');

    // Create bundles.
    $entity_test_bundle_storage = $this->entityTypeManager->getStorage('entity_test_bundle');
    foreach (['alpha', 'beta'] as $bundle) {
      $entity_test_bundle_storage->create([
        'id' => $bundle,
      ])->save();
    }
  }

  /**
   * Tests that computed fields are registered as entity fields.
   */
  public function testComputedFields() {
    // Test field from config entity.
    $computed_field_storage = $this->entityTypeManager->getStorage('computed_field');

    $computed_field = $computed_field_storage->create([
      'field_name' => 'test_bundle',
      'label' => 'Test',
      'plugin_id' => 'test_string',
      'entity_type' => 'entity_test_with_bundle',
      'bundle' => 'alpha',
    ]);
    $computed_field->save();

    $this->assertEquals('entity_test_with_bundle.alpha.test_bundle', $computed_field->id());

    $this->assertEquals([0 => 'entity_test.entity_test_bundle.alpha'], $computed_field->getDependencies()['config']);
    $this->assertEquals([0 => 'test_computed_field_plugins'], $computed_field->getDependencies()['module']);

    $this->assertArrayHasKey('test_bundle', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'alpha'));
    $this->assertArrayNotHasKey('test_bundle', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'beta'));

    // Automatic plugins base fields.
    $this->assertArrayHasKey('test_automatic_base', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'alpha'));
    $this->assertArrayHasKey('test_automatic_base', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'beta'));

    $this->assertArrayNotHasKey('test_automatic_base_unused', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'alpha'));
    $this->assertArrayNotHasKey('test_automatic_base_unused', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'beta'));

    // Automatic plugins bundle fields.
    $this->assertArrayHasKey('test_automatic_bundle', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'alpha'));
    $this->assertArrayNotHasKey('test_automatic_bundle', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'beta'));

    $this->assertArrayNotHasKey('test_automatic_bundle_unused', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'alpha'));
    $this->assertArrayNotHasKey('test_automatic_bundle_unused', $this->entityFieldManager->getFieldDefinitions('entity_test_with_bundle', 'beta'));
  }

}
