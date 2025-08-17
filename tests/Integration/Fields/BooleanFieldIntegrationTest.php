<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Boolean Field Integration Test
 *
 * Tests the complete integration between PHP Boolean field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 * 
 * Focuses on field configuration and behavior rather than
 * database operations, testing the Nova API integration.
 */
class BooleanFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different boolean values for testing
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'is_admin' => true, 'is_active' => true]);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'is_admin' => false, 'is_active' => true]);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com', 'is_admin' => false, 'is_active' => false]);
    }

    /** @test */
    public function it_creates_boolean_field_with_nova_syntax(): void
    {
        $field = Boolean::make('Active');

        $this->assertEquals('Active', $field->name);
        $this->assertEquals('active', $field->attribute);
        $this->assertEquals('BooleanField', $field->component);
    }

    /** @test */
    public function it_creates_boolean_field_with_custom_attribute(): void
    {
        $field = Boolean::make('Is Published', 'is_published');

        $this->assertEquals('Is Published', $field->name);
        $this->assertEquals('is_published', $field->attribute);
    }

    /** @test */
    public function it_supports_all_nova_boolean_configuration_methods(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off')
            ->nullable()
            ->help('Toggle the active status');

        $this->assertEquals('On', $field->trueValue);
        $this->assertEquals('Off', $field->falseValue);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Toggle the active status', $field->helpText);
    }

    /** @test */
    public function it_supports_nova_true_value_method(): void
    {
        $field = Boolean::make('Active')->trueValue('On');

        $this->assertEquals('On', $field->trueValue);
        $this->assertFalse($field->falseValue); // Should remain default
    }

    /** @test */
    public function it_supports_nova_false_value_method(): void
    {
        $field = Boolean::make('Active')->falseValue('Off');

        $this->assertTrue($field->trueValue); // Should remain default
        $this->assertEquals('Off', $field->falseValue);
    }

    /** @test */
    public function it_supports_both_true_and_false_values(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('yes')
            ->falseValue('no');

        $this->assertEquals('yes', $field->trueValue);
        $this->assertEquals('no', $field->falseValue);
    }

    /** @test */
    public function it_supports_numeric_true_false_values(): void
    {
        $field = Boolean::make('Active')
            ->trueValue(1)
            ->falseValue(0);

        $this->assertEquals(1, $field->trueValue);
        $this->assertEquals(0, $field->falseValue);
    }

    /** @test */
    public function it_supports_mixed_type_true_false_values(): void
    {
        $field = Boolean::make('Status')
            ->trueValue('active')
            ->falseValue(0);

        $this->assertEquals('active', $field->trueValue);
        $this->assertEquals(0, $field->falseValue);
    }

    /** @test */
    public function it_resolves_boolean_field_value_correctly(): void
    {
        $user = User::find(1);
        $field = Boolean::make('Active', 'is_active');

        $field->resolve($user);

        $this->assertTrue($field->value);
    }

    /** @test */
    public function it_resolves_boolean_field_with_custom_values(): void
    {
        $user = User::find(1); // is_active = true
        $field = Boolean::make('Active', 'is_active')
            ->trueValue('On')
            ->falseValue('Off');

        $field->resolve($user);

        // The resolved value should be the actual database value (true), not the custom value
        $this->assertTrue($field->value);
    }

    /** @test */
    public function it_handles_boolean_field_fill_with_default_values(): void
    {
        $user = new User();
        $request = new Request(['is_active' => true]);
        
        $field = Boolean::make('Active', 'is_active');
        $field->fill($request, $user);

        $this->assertTrue($user->is_active);
    }

    /** @test */
    public function it_handles_boolean_field_fill_with_custom_values(): void
    {
        $user = new User();
        $request = new Request(['status' => true]);
        
        $field = Boolean::make('Status', 'status')
            ->trueValue('active')
            ->falseValue('inactive');
        
        $field->fill($request, $user);

        $this->assertEquals('active', $user->status);
    }

    /** @test */
    public function it_handles_boolean_field_fill_with_false_value(): void
    {
        $user = new User();
        $request = new Request(['status' => false]);
        
        $field = Boolean::make('Status', 'status')
            ->trueValue('On')
            ->falseValue('Off');
        
        $field->fill($request, $user);

        $this->assertEquals('Off', $user->status);
    }

    /** @test */
    public function it_handles_boolean_field_fill_with_numeric_values(): void
    {
        $user = new User();
        $request = new Request(['priority' => true]);
        
        $field = Boolean::make('Priority', 'priority')
            ->trueValue(1)
            ->falseValue(0);
        
        $field->fill($request, $user);

        $this->assertEquals(1, $user->priority);
    }

    /** @test */
    public function it_serializes_boolean_field_for_frontend(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off')
            ->help('Toggle the active status');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Active', $serialized['name']);
        $this->assertEquals('active', $serialized['attribute']);
        $this->assertEquals('BooleanField', $serialized['component']);
        $this->assertEquals('Toggle the active status', $serialized['helpText']);
        
        // Check meta properties
        $this->assertEquals('On', $serialized['trueValue']);
        $this->assertEquals('Off', $serialized['falseValue']);
    }

    /** @test */
    public function it_serializes_default_values_correctly(): void
    {
        $field = Boolean::make('Active');

        $serialized = $field->jsonSerialize();

        $this->assertTrue($serialized['trueValue']);
        $this->assertFalse($serialized['falseValue']);
    }

    /** @test */
    public function it_inherits_all_field_methods(): void
    {
        $field = Boolean::make('Active');

        // Test that Boolean field inherits all base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'jsonSerialize'));
        
        // Test Nova-specific Boolean methods
        $this->assertTrue(method_exists($field, 'trueValue'));
        $this->assertTrue(method_exists($field, 'falseValue'));
    }

    /** @test */
    public function it_handles_complex_boolean_field_configuration(): void
    {
        $field = Boolean::make('User Status')
            ->trueValue('active')
            ->falseValue('inactive')
            ->nullable()
            ->help('Toggle user active status')
            ->rules('required');

        // Test all configurations are set correctly
        $this->assertEquals('active', $field->trueValue);
        $this->assertEquals('inactive', $field->falseValue);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Toggle user active status', $field->helpText);
        $this->assertContains('required', $field->rules);

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('User Status', $serialized['name']);
        $this->assertEquals('user_status', $serialized['attribute']);
        $this->assertEquals('Toggle user active status', $serialized['helpText']);
        $this->assertEquals('active', $serialized['trueValue']);
        $this->assertEquals('inactive', $serialized['falseValue']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals(['required'], $serialized['rules']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off')
            ->nullable()
            ->help('Toggle status')
            ->rules('required');

        $this->assertInstanceOf(Boolean::class, $field);
        $this->assertEquals('On', $field->trueValue);
        $this->assertEquals('Off', $field->falseValue);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Toggle status', $field->helpText);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_boolean_field(): void
    {
        $field = Boolean::make('Active');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Boolean::class, $field->trueValue('On'));
        $this->assertInstanceOf(Boolean::class, $field->falseValue('Off'));
        
        // Test component name matches Nova
        $this->assertEquals('BooleanField', $field->component);
        
        // Test default values match Nova
        $freshField = Boolean::make('Fresh');
        $this->assertTrue($freshField->trueValue);
        $this->assertFalse($freshField->falseValue);
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle with boolean field
        $field = Boolean::make('Admin Status', 'is_admin')
            ->trueValue('admin')
            ->falseValue('user');

        // CREATE - Test with new user
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        $field->resolve($newUser);
        $this->assertFalse($field->value); // Database value, not custom value

        // UPDATE - Change user to admin
        $newUser->update(['is_admin' => true]);
        $field->resolve($newUser->fresh());
        $this->assertTrue($field->value); // Database value

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertTrue($field->value);

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_handles_boolean_field_with_validation_rules(): void
    {
        $field = Boolean::make('Active', 'is_active')
            ->trueValue(1)
            ->falseValue(0)
            ->rules('required', 'boolean')
            ->nullable(false);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('boolean', $field->rules);
        $this->assertFalse($field->nullable);

        // Test field serialization includes validation rules
        $serialized = $field->jsonSerialize();
        $this->assertEquals(['required', 'boolean'], $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
    }

    /** @test */
    public function it_supports_nova_examples_from_documentation(): void
    {
        // Example from Nova docs: Boolean::make('Active')
        $field1 = Boolean::make('Active');
        $this->assertEquals('Active', $field1->name);
        $this->assertEquals('active', $field1->attribute);
        $this->assertTrue($field1->trueValue);
        $this->assertFalse($field1->falseValue);

        // Example from Nova docs: Boolean::make('Active')->trueValue('On')->falseValue('Off')
        $field2 = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off');
        
        $this->assertEquals('On', $field2->trueValue);
        $this->assertEquals('Off', $field2->falseValue);
    }

    /** @test */
    public function it_handles_edge_cases_with_null_and_empty_values(): void
    {
        // Test with null values
        $field1 = Boolean::make('Active')
            ->trueValue(null)
            ->falseValue('inactive');
        
        $this->assertNull($field1->trueValue);
        $this->assertEquals('inactive', $field1->falseValue);

        // Test with empty string values
        $field2 = Boolean::make('Active')
            ->trueValue('')
            ->falseValue('no');
        
        $this->assertEquals('', $field2->trueValue);
        $this->assertEquals('no', $field2->falseValue);
    }

    /** @test */
    public function it_handles_type_coercion_correctly(): void
    {
        // Test that values are stored exactly as provided (no type coercion)
        $field = Boolean::make('Status')
            ->trueValue('1')  // String '1'
            ->falseValue(0);  // Integer 0

        $this->assertSame('1', $field->trueValue);
        $this->assertSame(0, $field->falseValue);
        
        // Verify types are preserved in meta
        $meta = $field->meta();
        $this->assertSame('1', $meta['trueValue']);
        $this->assertSame(0, $meta['falseValue']);
    }

    /** @test */
    public function it_works_with_all_inherited_field_functionality(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('active')
            ->falseValue('inactive')
            ->nullable()
            ->readonly()
            ->help('User active status')
            ->rules('required');

        // Test inherited functionality works
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
        $this->assertEquals('User active status', $field->helpText);
        $this->assertContains('required', $field->rules);
        
        // Test Nova-specific functionality still works
        $this->assertEquals('active', $field->trueValue);
        $this->assertEquals('inactive', $field->falseValue);
    }

    /** @test */
    public function it_handles_request_fill_with_missing_values(): void
    {
        $user = new User();
        $request = new Request([]); // No boolean field in request
        
        $field = Boolean::make('Active', 'is_active')
            ->trueValue('On')
            ->falseValue('Off');
        
        $field->fill($request, $user);

        // When boolean field is missing from request, it should be treated as false
        $this->assertEquals('Off', $user->is_active);
    }

    /** @test */
    public function it_handles_request_fill_with_string_boolean_values(): void
    {
        $user = new User();
        
        // Test with string 'true'
        $request1 = new Request(['status' => 'true']);
        $field1 = Boolean::make('Status', 'status')
            ->trueValue('active')
            ->falseValue('inactive');
        
        $field1->fill($request1, $user);
        $this->assertEquals('active', $user->status);

        // Test with string 'false'
        $request2 = new Request(['status' => 'false']);
        $field2 = Boolean::make('Status', 'status')
            ->trueValue('active')
            ->falseValue('inactive');
        
        $field2->fill($request2, $user);
        $this->assertEquals('inactive', $user->status);
    }
}
