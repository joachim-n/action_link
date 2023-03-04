<?php

namespace Drupal\Tests\computed_field\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
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
    'node',
    'computed_field',
    'action_link',
    'action_link_field',
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

  // TODOARGH
  protected $strictConfigSchema = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->entityFieldManager = $this->container->get('entity_field.manager');
    $this->entityDisplayRepository = $this->container->get('entity_display.repository');

    // Create bundles.
    $node_type_storage = $this->entityTypeManager->getStorage('node_type');
    foreach (['alpha', 'beta'] as $bundle) {
      $node_type_storage->create([
        'id' => $bundle,
        'type' => $bundle,
      ])->save();
    }

    // Create a config field.
    // Add a translatable field to the vocabulary.
    $field = FieldStorageConfig::create(array(
      'field_name' => 'field_foo',
      'entity_type' => 'node',
      'type' => 'boolean',
    ));
    $field->save();
    FieldConfig::create([
      'field_name' => 'field_foo',
      'entity_type' => 'node',
      'bundle' => 'alpha',
      'label' => 'Foo',
    ])->save();
  }

  /**
   * Tests that action link computed fields are registered as entity fields.
   */
  public function testComputedFields() {
    $action_link_storage = $this->entityTypeManager->getStorage('action_link');

    // Action link on a base field.
    $action_link_on_base_field = $action_link_storage->create([
      'id' => 'on_base_field',
      'label' => 'action_link_on_base_field',
      'plugin_id' => 'boolean_field',
      'plugin_config' => [
        'entity_type_id' => 'node',
        'field' => 'promote',
      ],
      'link_style' => 'nojs',
      'third_party_settings' => [
        'action_link_field' => [
          'computed_field' => TRUE,
        ],
      ]
    ]);
    $action_link_on_base_field->save();

    $this->assertArrayHasKey('action_link:on_base_field', \Drupal::service('plugin.manager.computed_field')->getDefinitions());
    $this->assertArrayHasKey('action_link_on_base_field', $this->entityFieldManager->getFieldDefinitions('node', 'alpha'));
    $this->assertArrayHasKey('action_link_on_base_field', $this->entityFieldManager->getFieldDefinitions('node', 'beta'));

    // Action link on a config field.
    $action_link_on_config_field = $action_link_storage->create([
      'id' => 'on_config_field',
      'label' => 'action_link_on_config_field',
      'plugin_id' => 'boolean_field',
      'plugin_config' => [
        'entity_type_id' => 'node',
        'field' => 'field_foo',
      ],
      'link_style' => 'nojs',
      'third_party_settings' => [
        'action_link_field' => [
          'computed_field' => TRUE,
        ],
      ]
    ]);
    $action_link_on_config_field->save();

    $this->assertArrayHasKey('action_link:on_config_field', \Drupal::service('plugin.manager.computed_field')->getDefinitions());
    $this->assertArrayHasKey('action_link_on_config_field', $this->entityFieldManager->getFieldDefinitions('node', 'alpha'));
    $this->assertArrayNotHasKey('action_link_on_config_field', $this->entityFieldManager->getFieldDefinitions('node', 'beta'));
  }

}
