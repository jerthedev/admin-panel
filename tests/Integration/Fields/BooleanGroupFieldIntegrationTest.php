<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\BooleanGroup;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Boolean Group Field Integration Test
 *
 * Tests the complete integration between PHP BooleanGroup field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 * 
 * Focuses on field configuration and behavior with JSON storage,
 * testing the Nova API integration.
 */
class BooleanGroupFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different boolean group values for testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'permissions' => ['create' => true, 'read' => true, 'update' => false, 'delete' => false],
            'features' => ['beta' => true, 'advanced' => false],
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'permissions' => ['create' => false, 'read' => true, 'update' => true, 'delete' => false],
            'features' => ['beta' => false, 'advanced' => true],
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'permissions' => ['create' => false, 'read' => false, 'update' => false, 'delete' => false],
            'features' => ['beta' => false, 'advanced' => false],
        ]);
    }

    /** @test */
    public function it_creates_boolean_group_field_with_nova_syntax(): void
    {
        $field = BooleanGroup::make('Permissions');

        $this->assertEquals('Permissions', $field->name);
        $this->assertEquals('permissions', $field->attribute);
        $this->assertEquals('BooleanGroupField', $field->component);
    }

    /** @test */
    public function it_creates_boolean_group_field_with_custom_attribute(): void
    {
        $field = BooleanGroup::make('User Permissions', 'user_permissions');

        $this->assertEquals('User Permissions', $field->name);
        $this->assertEquals('user_permissions', $field->attribute);
    }

    /** @test */
    public function it_supports_all_nova_boolean_group_configuration_methods(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->options([
                'create' => 'Create Posts',
                'edit' => 'Edit Posts',
                'delete' => 'Delete Posts',
            ])
            ->hideFalseValues()
            ->noValueText('No permissions selected')
            ->nullable()
            ->help('Select user permissions');

        $this->assertEquals([
            'create' => 'Create Posts',
            'edit' => 'Edit Posts',
            'delete' => 'Delete Posts',
        ], $field->options);
        $this->assertTrue($field->hideFalseValues);
        $this->assertEquals('No permissions selected', $field->noValueText);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Select user permissions', $field->helpText);
    }

    /** @test */
    public function it_supports_nova_options_method(): void
    {
        $options = [
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ];

        $field = BooleanGroup::make('Permissions')->options($options);

        $this->assertEquals($options, $field->options);
    }

    /** @test */
    public function it_supports_nova_hide_false_values_method(): void
    {
        $field = BooleanGroup::make('Permissions')->hideFalseValues();

        $this->assertTrue($field->hideFalseValues);
        $this->assertFalse($field->hideTrueValues); // Should remain default
    }

    /** @test */
    public function it_supports_nova_hide_true_values_method(): void
    {
        $field = BooleanGroup::make('Permissions')->hideTrueValues();

        $this->assertTrue($field->hideTrueValues);
        $this->assertFalse($field->hideFalseValues); // Should remain default
    }

    /** @test */
    public function it_supports_nova_no_value_text_method(): void
    {
        $field = BooleanGroup::make('Permissions')->noValueText('No permissions assigned.');

        $this->assertEquals('No permissions assigned.', $field->noValueText);
    }

    /** @test */
    public function it_resolves_boolean_group_field_value_correctly(): void
    {
        $user = User::find(1);
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
                'update' => 'Update',
                'delete' => 'Delete',
            ]);

        $field->resolve($user);

        $this->assertEquals([
            'create' => true,
            'read' => true,
            'update' => false,
            'delete' => false,
        ], $field->value);
    }

    /** @test */
    public function it_resolves_boolean_group_field_with_missing_keys(): void
    {
        $user = User::find(1); // Has permissions but not all keys
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
                'update' => 'Update',
                'delete' => 'Delete',
                'admin' => 'Admin', // This key doesn't exist in user data
            ]);

        $field->resolve($user);

        $this->assertEquals([
            'create' => true,
            'read' => true,
            'update' => false,
            'delete' => false,
            'admin' => false, // Should default to false for missing keys
        ], $field->value);
    }

    /** @test */
    public function it_handles_boolean_group_field_fill_with_request_data(): void
    {
        $user = new User();
        $request = new Request([
            'permissions' => [
                'create' => true,
                'read' => true,
                'update' => false,
                'delete' => false,
            ]
        ]);
        
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
                'update' => 'Update',
                'delete' => 'Delete',
            ]);
        
        $field->fill($request, $user);

        $this->assertEquals([
            'create' => true,
            'read' => true,
            'update' => false,
            'delete' => false,
        ], $user->permissions);
    }

    /** @test */
    public function it_handles_boolean_group_field_fill_with_partial_data(): void
    {
        $user = new User();
        $request = new Request([
            'permissions' => [
                'create' => true,
                // Missing other keys
            ]
        ]);
        
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
                'update' => 'Update',
                'delete' => 'Delete',
            ]);
        
        $field->fill($request, $user);

        $this->assertEquals([
            'create' => true,
            'read' => false, // Missing keys should default to false
            'update' => false,
            'delete' => false,
        ], $user->permissions);
    }

    /** @test */
    public function it_handles_boolean_group_field_fill_with_empty_data(): void
    {
        $user = new User();
        $request = new Request([
            'permissions' => []
        ]);
        
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
            ]);
        
        $field->fill($request, $user);

        $this->assertEquals([
            'create' => false,
            'read' => false,
        ], $user->permissions);
    }

    /** @test */
    public function it_serializes_boolean_group_field_for_frontend(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->options([
                'create' => 'Create Posts',
                'read' => 'Read Posts',
                'update' => 'Update Posts',
                'delete' => 'Delete Posts',
            ])
            ->hideFalseValues()
            ->noValueText('No permissions selected')
            ->help('Select the permissions for this user');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Permissions', $serialized['name']);
        $this->assertEquals('permissions', $serialized['attribute']);
        $this->assertEquals('BooleanGroupField', $serialized['component']);
        $this->assertEquals('Select the permissions for this user', $serialized['helpText']);
        
        // Check meta properties
        $this->assertEquals([
            'create' => 'Create Posts',
            'read' => 'Read Posts',
            'update' => 'Update Posts',
            'delete' => 'Delete Posts',
        ], $serialized['options']);
        $this->assertTrue($serialized['hideFalseValues']);
        $this->assertFalse($serialized['hideTrueValues']);
        $this->assertEquals('No permissions selected', $serialized['noValueText']);
    }

    /** @test */
    public function it_serializes_default_values_correctly(): void
    {
        $field = BooleanGroup::make('Permissions');

        $serialized = $field->jsonSerialize();

        $this->assertEquals([], $serialized['options']);
        $this->assertFalse($serialized['hideFalseValues']);
        $this->assertFalse($serialized['hideTrueValues']);
        $this->assertEquals('No Data', $serialized['noValueText']);
    }

    /** @test */
    public function it_inherits_all_field_methods(): void
    {
        $field = BooleanGroup::make('Permissions');

        // Test that BooleanGroup field inherits all base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'jsonSerialize'));
        
        // Test Nova-specific BooleanGroup methods
        $this->assertTrue(method_exists($field, 'options'));
        $this->assertTrue(method_exists($field, 'hideFalseValues'));
        $this->assertTrue(method_exists($field, 'hideTrueValues'));
        $this->assertTrue(method_exists($field, 'noValueText'));
    }

    /** @test */
    public function it_handles_complex_boolean_group_field_configuration(): void
    {
        $field = BooleanGroup::make('User Permissions')
            ->options([
                'posts.create' => 'Create Posts',
                'posts.edit' => 'Edit Posts',
                'posts.delete' => 'Delete Posts',
                'users.manage' => 'Manage Users',
            ])
            ->hideFalseValues()
            ->noValueText('No permissions assigned')
            ->nullable()
            ->help('Select user permissions')
            ->rules('required');

        // Test all configurations are set correctly
        $this->assertEquals([
            'posts.create' => 'Create Posts',
            'posts.edit' => 'Edit Posts',
            'posts.delete' => 'Delete Posts',
            'users.manage' => 'Manage Users',
        ], $field->options);
        $this->assertTrue($field->hideFalseValues);
        $this->assertEquals('No permissions assigned', $field->noValueText);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Select user permissions', $field->helpText);
        $this->assertContains('required', $field->rules);

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('User Permissions', $serialized['name']);
        $this->assertEquals('user_permissions', $serialized['attribute']);
        $this->assertEquals('Select user permissions', $serialized['helpText']);
        $this->assertEquals([
            'posts.create' => 'Create Posts',
            'posts.edit' => 'Edit Posts',
            'posts.delete' => 'Delete Posts',
            'users.manage' => 'Manage Users',
        ], $serialized['options']);
        $this->assertTrue($serialized['hideFalseValues']);
        $this->assertEquals('No permissions assigned', $serialized['noValueText']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals(['required'], $serialized['rules']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->hideFalseValues()
            ->noValueText('No permissions')
            ->nullable()
            ->help('Select permissions')
            ->rules('required');

        $this->assertInstanceOf(BooleanGroup::class, $field);
        $this->assertEquals(['create' => 'Create', 'read' => 'Read'], $field->options);
        $this->assertTrue($field->hideFalseValues);
        $this->assertEquals('No permissions', $field->noValueText);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Select permissions', $field->helpText);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_boolean_group_field(): void
    {
        $field = BooleanGroup::make('Permissions');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(BooleanGroup::class, $field->options(['create' => 'Create']));
        $this->assertInstanceOf(BooleanGroup::class, $field->hideFalseValues());
        $this->assertInstanceOf(BooleanGroup::class, $field->hideTrueValues());
        $this->assertInstanceOf(BooleanGroup::class, $field->noValueText('Custom text'));
        
        // Test component name matches Nova
        $this->assertEquals('BooleanGroupField', $field->component);
        
        // Test default values match Nova
        $freshField = BooleanGroup::make('Fresh');
        $this->assertEquals([], $freshField->options);
        $this->assertFalse($freshField->hideFalseValues);
        $this->assertFalse($freshField->hideTrueValues);
        $this->assertEquals('No Data', $freshField->noValueText);
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle with boolean group field
        $field = BooleanGroup::make('Features', 'features')
            ->options([
                'beta' => 'Beta Features',
                'advanced' => 'Advanced Features',
                'experimental' => 'Experimental Features',
            ]);

        // CREATE - Test with new user
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
            'features' => ['beta' => false, 'advanced' => false, 'experimental' => false],
        ]);

        $field->resolve($newUser);
        $this->assertEquals([
            'beta' => false,
            'advanced' => false,
            'experimental' => false,
        ], $field->value);

        // UPDATE - Change user features
        $newUser->update(['features' => ['beta' => true, 'advanced' => true, 'experimental' => false]]);
        $field->resolve($newUser->fresh());
        $this->assertEquals([
            'beta' => true,
            'advanced' => true,
            'experimental' => false,
        ], $field->value);

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertEquals([
            'beta' => true,
            'advanced' => true,
            'experimental' => false,
        ], $field->value);

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_handles_boolean_group_field_with_validation_rules(): void
    {
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->rules('required', 'array')
            ->nullable(false);

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
    public function it_supports_nova_examples_from_documentation(): void
    {
        // Example from Nova docs: BooleanGroup::make('Permissions')->options([...])
        $field1 = BooleanGroup::make('Permissions')->options([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ]);
        
        $this->assertEquals('Permissions', $field1->name);
        $this->assertEquals('permissions', $field1->attribute);
        $this->assertEquals([
            'create' => 'Create',
            'read' => 'Read',
            'update' => 'Update',
            'delete' => 'Delete',
        ], $field1->options);

        // Example with hide methods
        $field2 = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->hideFalseValues();
        
        $this->assertTrue($field2->hideFalseValues);
        $this->assertFalse($field2->hideTrueValues);

        // Example with custom no value text
        $field3 = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create'])
            ->noValueText('No permissions selected.');
        
        $this->assertEquals('No permissions selected.', $field3->noValueText);
    }

    /** @test */
    public function it_handles_edge_cases_with_empty_and_null_options(): void
    {
        // Test with empty options
        $field1 = BooleanGroup::make('Permissions')->options([]);
        $this->assertEquals([], $field1->options);

        // Test with complex keys
        $field2 = BooleanGroup::make('Permissions')->options([
            '' => 'Empty Key',
            '0' => 'Zero Key',
            'kebab-case' => 'Kebab Case',
            'snake_case' => 'Snake Case',
            'camelCase' => 'Camel Case',
        ]);
        
        $this->assertEquals([
            '' => 'Empty Key',
            '0' => 'Zero Key',
            'kebab-case' => 'Kebab Case',
            'snake_case' => 'Snake Case',
            'camelCase' => 'Camel Case',
        ], $field2->options);
    }

    /** @test */
    public function it_works_with_all_inherited_field_functionality(): void
    {
        $field = BooleanGroup::make('Permissions')
            ->options(['create' => 'Create', 'read' => 'Read'])
            ->hideFalseValues()
            ->noValueText('No permissions')
            ->nullable()
            ->readonly()
            ->help('User permissions')
            ->rules('required');

        // Test inherited functionality works
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
        $this->assertEquals('User permissions', $field->helpText);
        $this->assertContains('required', $field->rules);
        
        // Test Nova-specific functionality still works
        $this->assertEquals(['create' => 'Create', 'read' => 'Read'], $field->options);
        $this->assertTrue($field->hideFalseValues);
        $this->assertEquals('No permissions', $field->noValueText);
    }

    /** @test */
    public function it_handles_request_fill_with_missing_field(): void
    {
        $user = new User();
        $request = new Request([]); // No permissions field in request
        
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
            ]);
        
        $field->fill($request, $user);

        // When field is missing from request, all options should be false
        $this->assertEquals([
            'create' => false,
            'read' => false,
        ], $user->permissions);
    }

    /** @test */
    public function it_handles_request_fill_with_non_array_data(): void
    {
        $user = new User();
        $request = new Request(['permissions' => 'invalid']); // Non-array data
        
        $field = BooleanGroup::make('Permissions', 'permissions')
            ->options([
                'create' => 'Create',
                'read' => 'Read',
            ]);
        
        $field->fill($request, $user);

        // When field is not an array, all options should be false
        $this->assertEquals([
            'create' => false,
            'read' => false,
        ], $user->permissions);
    }
}
