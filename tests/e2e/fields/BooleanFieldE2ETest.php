<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Boolean Field E2E Test
 *
 * Tests the complete end-to-end functionality of Boolean fields
 * including field configuration, data flow, and Nova API compatibility.
 * 
 * Focuses on field integration and behavior rather than
 * web interface testing (which is handled by Playwright tests).
 */
class BooleanFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different boolean values for E2E testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_admin' => true,
            'is_active' => true,
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'is_admin' => false,
            'is_active' => true,
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'is_admin' => false,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_handles_boolean_field_with_standard_true_false_values(): void
    {
        $field = Boolean::make('Active', 'is_active');

        // Test with active user
        $activeUser = User::find(1);
        $field->resolve($activeUser);

        $this->assertTrue($field->value);

        // Test with inactive user
        $inactiveUser = User::find(3);
        $field->resolve($inactiveUser);

        $this->assertFalse($field->value);
    }

    /** @test */
    public function it_handles_boolean_field_with_custom_string_values(): void
    {
        $field = Boolean::make('Status', 'is_active')
            ->trueValue('enabled')
            ->falseValue('disabled');

        $activeUser = User::find(1);
        $field->resolve($activeUser);

        // The resolved value should be the actual database value (true), not the custom value
        $this->assertTrue($field->value);

        // Test serialization includes custom values
        $serialized = $field->jsonSerialize();
        $this->assertEquals('enabled', $serialized['trueValue']);
        $this->assertEquals('disabled', $serialized['falseValue']);
    }

    /** @test */
    public function it_handles_boolean_field_with_numeric_values(): void
    {
        $field = Boolean::make('Priority', 'is_admin')
            ->trueValue(1)
            ->falseValue(0);

        $adminUser = User::find(1);
        $field->resolve($adminUser);

        $this->assertTrue($field->value); // Database value

        // Test serialization
        $serialized = $field->jsonSerialize();
        $this->assertEquals(1, $serialized['trueValue']);
        $this->assertEquals(0, $serialized['falseValue']);
    }

    /** @test */
    public function it_handles_boolean_field_with_mixed_type_values(): void
    {
        $field = Boolean::make('User Type', 'is_admin')
            ->trueValue('admin')
            ->falseValue(0);

        $adminUser = User::find(1);
        $field->resolve($adminUser);

        $this->assertTrue($field->value); // Database value

        $regularUser = User::find(2);
        $field->resolve($regularUser);

        $this->assertFalse($field->value); // Database value
    }

    /** @test */
    public function it_handles_boolean_field_serialization_for_frontend(): void
    {
        $field = Boolean::make('Active', 'is_active')
            ->trueValue('On')
            ->falseValue('Off')
            ->help('Toggle the user active status')
            ->rules('required');

        $user = User::find(1);
        $field->resolve($user);

        $serialized = $field->jsonSerialize();

        // Test basic field properties
        $this->assertEquals('Active', $serialized['name']);
        $this->assertEquals('is_active', $serialized['attribute']);
        $this->assertEquals('BooleanField', $serialized['component']);
        $this->assertTrue($serialized['value']);
        $this->assertEquals('Toggle the user active status', $serialized['helpText']);
        $this->assertEquals(['required'], $serialized['rules']);

        // Test Nova-specific boolean properties
        $this->assertEquals('On', $serialized['trueValue']);
        $this->assertEquals('Off', $serialized['falseValue']);
    }

    /** @test */
    public function it_handles_boolean_field_with_null_values(): void
    {
        $field = Boolean::make('Optional Status', 'nonexistent_field')
            ->trueValue('active')
            ->falseValue('inactive')
            ->nullable();

        $user = User::find(1);
        $field->resolve($user);

        $this->assertNull($field->value);

        // Test serialization
        $serialized = $field->jsonSerialize();
        $this->assertNull($serialized['value']);
        $this->assertTrue($serialized['nullable']);
    }

    /** @test */
    public function it_handles_boolean_field_with_complex_nova_configuration(): void
    {
        $field = Boolean::make('User Status')
            ->trueValue('active_user')
            ->falseValue('inactive_user')
            ->nullable()
            ->help('Displays the current user account status')
            ->rules('required', 'boolean');

        // Test with different users
        $testCases = [
            [1, true],   // Active admin user
            [2, true],   // Active regular user
            [3, false],  // Inactive user
        ];

        foreach ($testCases as [$userId, $expectedValue]) {
            $user = User::find($userId);
            $testField = Boolean::make('User Status', 'is_active')
                ->trueValue('active_user')
                ->falseValue('inactive_user')
                ->nullable()
                ->help('Displays the current user account status')
                ->rules('required', 'boolean');

            $testField->resolve($user);

            $this->assertEquals($expectedValue, $testField->value);

            // Test serialization
            $serialized = $testField->jsonSerialize();
            $this->assertEquals('active_user', $serialized['trueValue']);
            $this->assertEquals('inactive_user', $serialized['falseValue']);
            $this->assertTrue($serialized['nullable']);
            $this->assertEquals(['required', 'boolean'], $serialized['rules']);
        }
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle with boolean field
        $field = Boolean::make('Admin Status', 'is_admin')
            ->trueValue('administrator')
            ->falseValue('regular_user');

        // CREATE - Test with new user
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        $field->resolve($newUser);
        $this->assertFalse($field->value);

        // Test serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertFalse($serialized['value']);
        $this->assertEquals('administrator', $serialized['trueValue']);
        $this->assertEquals('regular_user', $serialized['falseValue']);

        // UPDATE - Change user to admin
        $newUser->update(['is_admin' => true]);
        $field->resolve($newUser->fresh());
        $this->assertTrue($field->value);

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

        $user = User::find(1);
        $field->resolve($user);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('boolean', $field->rules);
        $this->assertFalse($field->nullable);

        // Test field serialization includes validation rules
        $serialized = $field->jsonSerialize();
        $this->assertEquals(['required', 'boolean'], $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
        $this->assertEquals(1, $serialized['trueValue']);
        $this->assertEquals(0, $serialized['falseValue']);
    }

    /** @test */
    public function it_provides_consistent_nova_api_behavior(): void
    {
        // Test that Boolean field behaves exactly like Nova's Boolean field
        $field = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off')
            ->nullable()
            ->help('Boolean field indicator');

        // Test method chaining returns Boolean instance
        $this->assertInstanceOf(Boolean::class, $field);

        // Test all Nova API methods exist and work
        $this->assertEquals('On', $field->trueValue);
        $this->assertEquals('Off', $field->falseValue);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Boolean field indicator', $field->helpText);

        // Test component name matches Nova
        $this->assertEquals('BooleanField', $field->component);

        // Test serialization includes all Nova properties
        $serialized = $field->jsonSerialize();
        $this->assertEquals('On', $serialized['trueValue']);
        $this->assertEquals('Off', $serialized['falseValue']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals('Boolean field indicator', $serialized['helpText']);
    }

    /** @test */
    public function it_handles_boolean_field_with_callback_resolution(): void
    {
        $field = Boolean::make('User Type', 'email', function ($resource, $attribute) {
            return str_contains($resource->{$attribute}, 'admin');
        })
            ->trueValue('admin_user')
            ->falseValue('regular_user');

        // Test with admin email
        $adminUser = User::find(1); // john@example.com (no admin in email)
        $field->resolve($adminUser);
        $this->assertFalse($field->value); // No 'admin' in email

        // Create user with admin email
        $adminEmailUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        $field->resolve($adminEmailUser);
        $this->assertTrue($field->value); // 'admin' found in email

        // Clean up
        $adminEmailUser->delete();
    }

    /** @test */
    public function it_handles_edge_cases_and_boundary_conditions(): void
    {
        // Test with null custom values
        $field1 = Boolean::make('Status', 'is_active')
            ->trueValue(null)
            ->falseValue('inactive');

        $user = User::find(1);
        $field1->resolve($user);

        $serialized1 = $field1->jsonSerialize();
        $this->assertNull($serialized1['trueValue']);
        $this->assertEquals('inactive', $serialized1['falseValue']);

        // Test with empty string values
        $field2 = Boolean::make('Status', 'is_active')
            ->trueValue('')
            ->falseValue('no');

        $field2->resolve($user);

        $serialized2 = $field2->jsonSerialize();
        $this->assertEquals('', $serialized2['trueValue']);
        $this->assertEquals('no', $serialized2['falseValue']);

        // Test with identical true/false values (edge case)
        $field3 = Boolean::make('Status', 'is_active')
            ->trueValue('same')
            ->falseValue('same');

        $field3->resolve($user);

        $serialized3 = $field3->jsonSerialize();
        $this->assertEquals('same', $serialized3['trueValue']);
        $this->assertEquals('same', $serialized3['falseValue']);
    }

    /** @test */
    public function it_maintains_type_integrity_across_operations(): void
    {
        // Test that custom values maintain their types throughout the process
        $field = Boolean::make('Status')
            ->trueValue('1')  // String '1'
            ->falseValue(0);  // Integer 0

        $user = User::find(1);
        $field->resolve($user);

        // Test types are preserved
        $this->assertSame('1', $field->trueValue);
        $this->assertSame(0, $field->falseValue);

        // Test serialization preserves types
        $serialized = $field->jsonSerialize();
        $this->assertSame('1', $serialized['trueValue']);
        $this->assertSame(0, $serialized['falseValue']);

        // Test meta preserves types
        $meta = $field->meta();
        $this->assertSame('1', $meta['trueValue']);
        $this->assertSame(0, $meta['falseValue']);
    }

    /** @test */
    public function it_handles_complex_real_world_scenarios(): void
    {
        // Scenario: User permission system with custom boolean values
        $permissionField = Boolean::make('Has Admin Access', 'is_admin')
            ->trueValue('full_access')
            ->falseValue('limited_access')
            ->help('Determines user access level')
            ->rules('required');

        // Scenario: Feature flag system
        $featureField = Boolean::make('Beta Features', 'is_active')
            ->trueValue('enabled')
            ->falseValue('disabled')
            ->nullable()
            ->help('Enable beta features for user');

        // Test admin user
        $adminUser = User::find(1);
        $permissionField->resolve($adminUser);
        $featureField->resolve($adminUser);

        $this->assertTrue($permissionField->value);
        $this->assertTrue($featureField->value);

        // Test serialization for both fields
        $permissionSerialized = $permissionField->jsonSerialize();
        $featureSerialized = $featureField->jsonSerialize();

        $this->assertEquals('full_access', $permissionSerialized['trueValue']);
        $this->assertEquals('limited_access', $permissionSerialized['falseValue']);
        $this->assertEquals(['required'], $permissionSerialized['rules']);

        $this->assertEquals('enabled', $featureSerialized['trueValue']);
        $this->assertEquals('disabled', $featureSerialized['falseValue']);
        $this->assertTrue($featureSerialized['nullable']);

        // Test regular user
        $regularUser = User::find(2);
        $permissionField->resolve($regularUser);
        $featureField->resolve($regularUser);

        $this->assertFalse($permissionField->value);
        $this->assertTrue($featureField->value);
    }
}
