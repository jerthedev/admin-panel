<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Fields\BooleanGroup;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Boolean Group Field E2E Test
 *
 * Tests the complete end-to-end functionality of BooleanGroup fields
 * including field configuration, data flow, and Nova API compatibility.
 * 
 * Focuses on field integration and behavior with JSON storage rather than
 * web interface testing (which is handled by Playwright tests).
 */
class BooleanGroupFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different boolean group values for E2E testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'permissions' => [
                'posts.create' => true,
                'posts.edit' => true,
                'posts.delete' => false,
                'users.manage' => false,
            ],
            'features' => [
                'beta_features' => true,
                'advanced_editor' => false,
                'api_access' => true,
            ],
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'permissions' => [
                'posts.create' => false,
                'posts.edit' => true,
                'posts.delete' => false,
                'users.manage' => true,
            ],
            'features' => [
                'beta_features' => false,
                'advanced_editor' => true,
                'api_access' => false,
            ],
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'permissions' => [
                'posts.create' => false,
                'posts.edit' => false,
                'posts.delete' => false,
                'users.manage' => false,
            ],
            'features' => [
                'beta_features' => false,
                'advanced_editor' => false,
                'api_access' => false,
            ],
        ]);
    }

    /** @test */
    public function it_handles_boolean_group_field_with_standard_options(): void
    {
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'posts.create' => 'Create Posts',
                'posts.edit' => 'Edit Posts',
                'posts.delete' => 'Delete Posts',
                'users.manage' => 'Manage Users',
            ]);

        // Test with user who has some permissions
        $user = User::find(1);
        $field->resolve($user);

        $this->assertEquals([
            'posts.create' => true,
            'posts.edit' => true,
            'posts.delete' => false,
            'users.manage' => false,
        ], $field->value);

        // Test with user who has different permissions
        $user2 = User::find(2);
        $field->resolve($user2);

        $this->assertEquals([
            'posts.create' => false,
            'posts.edit' => true,
            'posts.delete' => false,
            'users.manage' => true,
        ], $field->value);
    }

    /** @test */
    public function it_handles_boolean_group_field_with_hide_false_values(): void
    {
        $field = BooleanGroup::make('Features', 'features')
            ->options([
                'beta_features' => 'Beta Features',
                'advanced_editor' => 'Advanced Editor',
                'api_access' => 'API Access',
            ])
            ->hideFalseValues();

        $user = User::find(1); // Has beta_features=true, advanced_editor=false, api_access=true
        $field->resolve($user);

        $this->assertEquals([
            'beta_features' => true,
            'advanced_editor' => false,
            'api_access' => true,
        ], $field->value);

        // Test display values (should hide false values)
        $displayValues = $field->getDisplayValue();
        $this->assertArrayHasKey('beta_features', $displayValues);
        $this->assertArrayHasKey('api_access', $displayValues);
        $this->assertArrayNotHasKey('advanced_editor', $displayValues); // Should be hidden (false)

        // Test serialization includes hide setting
        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['hideFalseValues']);
    }

    /** @test */
    public function it_handles_boolean_group_field_with_hide_true_values(): void
    {
        $field = BooleanGroup::make('Features', 'features')
            ->options([
                'beta_features' => 'Beta Features',
                'advanced_editor' => 'Advanced Editor',
                'api_access' => 'API Access',
            ])
            ->hideTrueValues();

        $user = User::find(1); // Has beta_features=true, advanced_editor=false, api_access=true
        $field->resolve($user);

        $this->assertEquals([
            'beta_features' => true,
            'advanced_editor' => false,
            'api_access' => true,
        ], $field->value);

        // Test display values (should hide true values)
        $displayValues = $field->getDisplayValue();
        $this->assertArrayNotHasKey('beta_features', $displayValues); // Should be hidden (true)
        $this->assertArrayHasKey('advanced_editor', $displayValues);
        $this->assertArrayNotHasKey('api_access', $displayValues); // Should be hidden (true)

        // Test serialization includes hide setting
        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['hideTrueValues']);
    }

    /** @test */
    public function it_handles_boolean_group_field_with_custom_no_value_text(): void
    {
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'admin' => 'Administrator',
                'moderator' => 'Moderator',
            ])
            ->hideFalseValues()
            ->noValueText('No special permissions assigned');

        // Test with user who has no permissions for these options
        $user = User::find(3); // Has all false permissions
        $field->resolve($user);

        // Since all values are false and hideFalseValues is true, no display values
        $this->assertFalse($field->hasDisplayValues());
        $this->assertEquals('No special permissions assigned', $field->getNoValueText());

        // Test serialization
        $serialized = $field->jsonSerialize();
        $this->assertEquals('No special permissions assigned', $serialized['noValueText']);
    }

    /** @test */
    public function it_handles_boolean_group_field_serialization_for_frontend(): void
    {
        $field = BooleanGroup::make('User Permissions', 'permissions')
            ->options([
                'posts.create' => 'Create Posts',
                'posts.edit' => 'Edit Posts',
                'posts.delete' => 'Delete Posts',
                'users.manage' => 'Manage Users',
            ])
            ->hideFalseValues()
            ->noValueText('No permissions selected')
            ->help('Select the permissions for this user')
            ->rules('required');

        $user = User::find(1);
        $field->resolve($user);

        $serialized = $field->jsonSerialize();

        // Test basic field properties
        $this->assertEquals('User Permissions', $serialized['name']);
        $this->assertEquals('permissions', $serialized['attribute']);
        $this->assertEquals('BooleanGroupField', $serialized['component']);
        $this->assertEquals([
            'posts.create' => true,
            'posts.edit' => true,
            'posts.delete' => false,
            'users.manage' => false,
        ], $serialized['value']);
        $this->assertEquals('Select the permissions for this user', $serialized['helpText']);
        $this->assertEquals(['required'], $serialized['rules']);

        // Test Nova-specific boolean group properties
        $this->assertEquals([
            'posts.create' => 'Create Posts',
            'posts.edit' => 'Edit Posts',
            'posts.delete' => 'Delete Posts',
            'users.manage' => 'Manage Users',
        ], $serialized['options']);
        $this->assertTrue($serialized['hideFalseValues']);
        $this->assertFalse($serialized['hideTrueValues']);
        $this->assertEquals('No permissions selected', $serialized['noValueText']);
    }

    /** @test */
    public function it_handles_boolean_group_field_with_null_values(): void
    {
        $field = BooleanGroup::make('Optional Features', 'nonexistent_field')
            ->options([
                'feature_a' => 'Feature A',
                'feature_b' => 'Feature B',
            ])
            ->nullable();

        $user = User::find(1);
        $field->resolve($user);

        // Should default all options to false when field doesn't exist
        $this->assertEquals([
            'feature_a' => false,
            'feature_b' => false,
        ], $field->value);

        // Test serialization
        $serialized = $field->jsonSerialize();
        $this->assertEquals([
            'feature_a' => false,
            'feature_b' => false,
        ], $serialized['value']);
        $this->assertTrue($serialized['nullable']);
    }

    /** @test */
    public function it_handles_boolean_group_field_with_complex_nova_configuration(): void
    {
        $field = BooleanGroup::make('Advanced Permissions')
            ->options([
                'system.admin' => 'System Administrator',
                'content.manage' => 'Content Management',
                'users.create' => 'Create Users',
                'users.edit' => 'Edit Users',
                'users.delete' => 'Delete Users',
                'reports.view' => 'View Reports',
                'reports.export' => 'Export Reports',
            ])
            ->hideFalseValues()
            ->noValueText('No advanced permissions assigned')
            ->nullable()
            ->help('Configure advanced system permissions')
            ->rules('required', 'array');

        // Test with different users
        $testCases = [
            [1, ['posts.create' => true, 'posts.edit' => true, 'posts.delete' => false, 'users.manage' => false]],
            [2, ['posts.create' => false, 'posts.edit' => true, 'posts.delete' => false, 'users.manage' => true]],
            [3, ['posts.create' => false, 'posts.edit' => false, 'posts.delete' => false, 'users.manage' => false]],
        ];

        foreach ($testCases as [$userId, $expectedPermissions]) {
            $user = User::find($userId);
            $testField = BooleanGroup::make('Permissions', 'permissions')
                ->options([
                    'posts.create' => 'Create Posts',
                    'posts.edit' => 'Edit Posts',
                    'posts.delete' => 'Delete Posts',
                    'users.manage' => 'Manage Users',
                ])
                ->hideFalseValues()
                ->noValueText('No advanced permissions assigned')
                ->nullable()
                ->help('Configure advanced system permissions')
                ->rules('required', 'array');

            $testField->resolve($user);

            $this->assertEquals($expectedPermissions, $testField->value);

            // Test serialization
            $serialized = $testField->jsonSerialize();
            $this->assertTrue($serialized['hideFalseValues']);
            $this->assertTrue($serialized['nullable']);
            $this->assertEquals(['required', 'array'], $serialized['rules']);
        }
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle with boolean group field
        $field = BooleanGroup::make('User Features', 'features')
            ->options([
                'beta_features' => 'Beta Features',
                'advanced_editor' => 'Advanced Editor',
                'api_access' => 'API Access',
                'premium_support' => 'Premium Support',
            ]);

        // CREATE - Test with new user
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
            'features' => [
                'beta_features' => false,
                'advanced_editor' => false,
                'api_access' => false,
                'premium_support' => false,
            ],
        ]);

        $field->resolve($newUser);
        $this->assertEquals([
            'beta_features' => false,
            'advanced_editor' => false,
            'api_access' => false,
            'premium_support' => false,
        ], $field->value);

        // Test serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals([
            'beta_features' => false,
            'advanced_editor' => false,
            'api_access' => false,
            'premium_support' => false,
        ], $serialized['value']);

        // UPDATE - Change user features
        $newUser->update([
            'features' => [
                'beta_features' => true,
                'advanced_editor' => true,
                'api_access' => false,
                'premium_support' => true,
            ]
        ]);
        $field->resolve($newUser->fresh());
        $this->assertEquals([
            'beta_features' => true,
            'advanced_editor' => true,
            'api_access' => false,
            'premium_support' => true,
        ], $field->value);

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertEquals([
            'beta_features' => true,
            'advanced_editor' => true,
            'api_access' => false,
            'premium_support' => true,
        ], $field->value);

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_handles_boolean_group_field_with_validation_rules(): void
    {
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
                'update' => 'Update',
                'delete' => 'Delete',
            ])
            ->rules('required', 'array')
            ->nullable(false);

        $user = User::find(1);
        $field->resolve($user);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('array', $field->rules);
        $this->assertFalse($field->nullable);

        // Test field serialization includes validation rules
        $serialized = $field->jsonSerialize();
        $this->assertEquals(['required', 'array'], $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
    }

    /** @test */
    public function it_provides_consistent_nova_api_behavior(): void
    {
        // Test that BooleanGroup field behaves exactly like Nova's BooleanGroup field
        $field = BooleanGroup::make('Permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
                'update' => 'Update',
                'delete' => 'Delete',
            ])
            ->hideFalseValues()
            ->noValueText('No permissions selected')
            ->nullable()
            ->help('Boolean group field for permissions');

        // Test method chaining returns BooleanGroup instance
        $this->assertInstanceOf(BooleanGroup::class, $field);

        // Test all Nova API methods exist and work
        $this->assertEquals([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ], $field->options);
        $this->assertTrue($field->hideFalseValues);
        $this->assertEquals('No permissions selected', $field->noValueText);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Boolean group field for permissions', $field->helpText);

        // Test component name matches Nova
        $this->assertEquals('BooleanGroupField', $field->component);

        // Test serialization includes all Nova properties
        $serialized = $field->jsonSerialize();
        $this->assertEquals([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ], $serialized['options']);
        $this->assertTrue($serialized['hideFalseValues']);
        $this->assertEquals('No permissions selected', $serialized['noValueText']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals('Boolean group field for permissions', $serialized['helpText']);
    }

    /** @test */
    public function it_handles_edge_cases_and_boundary_conditions(): void
    {
        // Test with empty options
        $field1 = BooleanGroup::make('Empty', 'permissions')
            ->options([]);

        $user = User::find(1);
        $field1->resolve($user);

        $this->assertEquals([], $field1->value);

        // Test with complex option keys
        $field2 = BooleanGroup::make('Complex Keys', 'permissions')
            ->options([
                '' => 'Empty Key',
                '0' => 'Zero Key',
                'kebab-case' => 'Kebab Case',
                'snake_case' => 'Snake Case',
                'camelCase' => 'Camel Case',
                'UPPER_CASE' => 'Upper Case',
                'special.chars' => 'Special Characters',
            ]);

        $field2->resolve($user);

        // Should handle all key types and default missing ones to false
        $expected = [
            '' => false,
            '0' => false,
            'kebab-case' => false,
            'snake_case' => false,
            'camelCase' => false,
            'UPPER_CASE' => false,
            'special.chars' => false,
        ];
        $this->assertEquals($expected, $field2->value);

        // Test with both hide methods enabled (edge case)
        $field3 = BooleanGroup::make('Both Hidden', 'permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->hideFalseValues()
            ->hideTrueValues();

        $field3->resolve($user);

        // Should hide everything
        $this->assertFalse($field3->hasDisplayValues());
    }

    /** @test */
    public function it_maintains_type_integrity_across_operations(): void
    {
        // Test that boolean values maintain their types throughout the process
        $field = BooleanGroup::make('Features', 'features')
            ->options([
                'feature_a' => 'Feature A',
                'feature_b' => 'Feature B',
            ]);

        $user = User::find(1);
        $field->resolve($user);

        // Test types are preserved
        foreach ($field->value as $key => $value) {
            $this->assertIsBool($value);
        }

        // Test serialization preserves types
        $serialized = $field->jsonSerialize();
        foreach ($serialized['value'] as $key => $value) {
            $this->assertIsBool($value);
        }

        // Test display values preserve types
        $displayValues = $field->getDisplayValue();
        foreach ($displayValues as $key => $item) {
            $this->assertIsBool($item['value']);
            $this->assertIsString($item['label']);
        }
    }

    /** @test */
    public function it_handles_complex_real_world_scenarios(): void
    {
        // Scenario: Role-based permission system
        $rolePermissions = BooleanGroup::make('Role Permissions', 'permissions')
            ->options([
                'posts.create' => 'Create Posts',
                'posts.edit' => 'Edit Posts',
                'posts.delete' => 'Delete Posts',
                'posts.publish' => 'Publish Posts',
                'users.view' => 'View Users',
                'users.create' => 'Create Users',
                'users.edit' => 'Edit Users',
                'users.delete' => 'Delete Users',
                'admin.settings' => 'Admin Settings',
                'admin.system' => 'System Administration',
            ])
            ->hideFalseValues()
            ->noValueText('No permissions assigned')
            ->help('Configure user role permissions')
            ->rules('required');

        // Scenario: Feature flag system
        $featureFlags = BooleanGroup::make('Feature Flags', 'features')
            ->options([
                'new_dashboard' => 'New Dashboard UI',
                'beta_editor' => 'Beta Editor',
                'api_v2' => 'API Version 2',
                'advanced_analytics' => 'Advanced Analytics',
                'experimental_features' => 'Experimental Features',
            ])
            ->hideFalseValues()
            ->noValueText('No beta features enabled')
            ->nullable()
            ->help('Enable beta features for user');

        // Test admin user (User 1)
        $adminUser = User::find(1);
        $rolePermissions->resolve($adminUser);
        $featureFlags->resolve($adminUser);

        // Admin should have some permissions
        $this->assertArrayHasKey('posts.create', $rolePermissions->value);
        $this->assertArrayHasKey('posts.edit', $rolePermissions->value);
        $this->assertTrue($rolePermissions->value['posts.create']);
        $this->assertTrue($rolePermissions->value['posts.edit']);

        // Test serialization for both fields
        $permissionsSerialized = $rolePermissions->jsonSerialize();
        $featuresSerialized = $featureFlags->jsonSerialize();

        $this->assertTrue($permissionsSerialized['hideFalseValues']);
        $this->assertEquals('No permissions assigned', $permissionsSerialized['noValueText']);
        $this->assertEquals(['required'], $permissionsSerialized['rules']);

        $this->assertTrue($featuresSerialized['hideFalseValues']);
        $this->assertEquals('No beta features enabled', $featuresSerialized['noValueText']);
        $this->assertTrue($featuresSerialized['nullable']);

        // Test regular user (User 2)
        $regularUser = User::find(2);
        $rolePermissions->resolve($regularUser);
        $featureFlags->resolve($regularUser);

        // Regular user should have different permissions
        $this->assertFalse($regularUser->permissions['posts.create']);
        $this->assertTrue($regularUser->permissions['posts.edit']);
        $this->assertTrue($regularUser->permissions['users.manage']);
    }
}
