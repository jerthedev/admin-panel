<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Fields\Badge;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Badge Field E2E Test
 *
 * Tests the complete end-to-end functionality of Badge fields
 * including field configuration, data flow, and Nova API compatibility.
 * 
 * Focuses on field integration and behavior rather than
 * web interface testing (which is handled by Playwright tests).
 */
class BadgeFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different status values for badge testing
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
    public function it_handles_badge_field_with_boolean_values(): void
    {
        $field = Badge::make('Status', 'is_active')
            ->map([
                true => 'success',
                false => 'danger',
            ])
            ->labels([
                true => 'Active',
                false => 'Inactive',
            ]);

        // Test with active user
        $activeUser = User::find(1);
        $field->resolve($activeUser);

        $this->assertTrue($field->value);
        $this->assertEquals('success', $field->resolveBadgeType($field->value));
        $this->assertEquals('Active', $field->resolveLabel($field->value));

        // Test with inactive user
        $inactiveUser = User::find(3);
        $field->resolve($inactiveUser);

        $this->assertFalse($field->value);
        $this->assertEquals('danger', $field->resolveBadgeType($field->value));
        $this->assertEquals('Inactive', $field->resolveLabel($field->value));
    }

    /** @test */
    public function it_handles_badge_field_with_string_values(): void
    {
        $field = Badge::make('User Type', 'name', function ($resource, $attribute) {
            return $resource->is_admin ? 'admin' : 'user';
        })
            ->map([
                'admin' => 'warning',
                'user' => 'info',
            ])
            ->labels([
                'admin' => 'Administrator',
                'user' => 'Regular User',
            ]);

        // Test with admin user
        $adminUser = User::find(1);
        $field->resolve($adminUser);

        $this->assertEquals('admin', $field->value);
        $this->assertEquals('warning', $field->resolveBadgeType($field->value));
        $this->assertEquals('Administrator', $field->resolveLabel($field->value));

        // Test with regular user
        $regularUser = User::find(2);
        $field->resolve($regularUser);

        $this->assertEquals('user', $field->value);
        $this->assertEquals('info', $field->resolveBadgeType($field->value));
        $this->assertEquals('Regular User', $field->resolveLabel($field->value));
    }

    /** @test */
    public function it_handles_badge_field_with_custom_types_and_icons(): void
    {
        $field = Badge::make('Account Status', 'is_active')
            ->map([
                true => 'success',
                false => 'danger',
            ])
            ->types([
                'success' => 'bg-green-50 text-green-700 ring-green-600/20 font-medium',
                'danger' => 'bg-red-50 text-red-700 ring-red-600/20 font-medium',
            ])
            ->withIcons()
            ->icons([
                'success' => 'check-circle',
                'danger' => 'x-circle',
            ])
            ->labels([
                true => 'Account Active',
                false => 'Account Suspended',
            ]);

        $activeUser = User::find(1);
        $field->resolve($activeUser);

        $this->assertTrue($field->value);
        $this->assertEquals('success', $field->resolveBadgeType($field->value));
        $this->assertEquals('bg-green-50 text-green-700 ring-green-600/20 font-medium', $field->resolveBadgeClasses('success'));
        $this->assertEquals('check-circle', $field->resolveIcon('success'));
        $this->assertEquals('Account Active', $field->resolveLabel($field->value));
        $this->assertTrue($field->withIcons);
    }

    /** @test */
    public function it_handles_badge_field_with_callback_resolution(): void
    {
        $field = Badge::make('User Role', 'email', function ($resource, $attribute) {
            if ($resource->is_admin) {
                return 'admin';
            }
            return $resource->is_active ? 'active_user' : 'inactive_user';
        })
            ->map([
                'admin' => 'danger',
                'active_user' => 'success',
                'inactive_user' => 'warning',
            ])
            ->label(function ($value) {
                return match ($value) {
                    'admin' => 'System Administrator',
                    'active_user' => 'Active Member',
                    'inactive_user' => 'Inactive Member',
                    default => 'Unknown Status',
                };
            });

        // Test admin user
        $adminUser = User::find(1);
        $field->resolve($adminUser);

        $this->assertEquals('admin', $field->value);
        $this->assertEquals('danger', $field->resolveBadgeType($field->value));
        $this->assertEquals('System Administrator', $field->resolveLabel($field->value));

        // Test active regular user
        $activeUser = User::find(2);
        $field->resolve($activeUser);

        $this->assertEquals('active_user', $field->value);
        $this->assertEquals('success', $field->resolveBadgeType($field->value));
        $this->assertEquals('Active Member', $field->resolveLabel($field->value));

        // Test inactive user
        $inactiveUser = User::find(3);
        $field->resolve($inactiveUser);

        $this->assertEquals('inactive_user', $field->value);
        $this->assertEquals('warning', $field->resolveBadgeType($field->value));
        $this->assertEquals('Inactive Member', $field->resolveLabel($field->value));
    }

    /** @test */
    public function it_handles_badge_field_serialization_for_frontend(): void
    {
        $field = Badge::make('Status', 'is_active')
            ->map([
                true => 'success',
                false => 'danger',
            ])
            ->types([
                'success' => 'custom-success-class',
                'danger' => 'custom-danger-class',
            ])
            ->withIcons()
            ->icons([
                'success' => 'check-circle',
                'danger' => 'x-circle',
            ])
            ->labels([
                true => 'Active Status',
                false => 'Inactive Status',
            ])
            ->help('Shows the current status of the user account');

        $user = User::find(1);
        $field->resolve($user);

        $serialized = $field->jsonSerialize();

        // Test basic field properties
        $this->assertEquals('Status', $serialized['name']);
        $this->assertEquals('is_active', $serialized['attribute']);
        $this->assertEquals('BadgeField', $serialized['component']);
        $this->assertTrue($serialized['value']);
        $this->assertEquals('Shows the current status of the user account', $serialized['helpText']);

        // Test Nova-specific badge properties
        $this->assertArrayHasKey('builtInTypes', $serialized);
        $this->assertEquals([true => 'success', false => 'danger'], $serialized['valueMap']);
        $this->assertEquals(['success' => 'custom-success-class', 'danger' => 'custom-danger-class'], $serialized['customTypes']);
        $this->assertTrue($serialized['withIcons']);
        $this->assertEquals(['success' => 'check-circle', 'danger' => 'x-circle'], $serialized['iconMap']);
        $this->assertEquals([true => 'Active Status', false => 'Inactive Status'], $serialized['labelMap']);

        // Test built-in types are included
        $this->assertArrayHasKey('info', $serialized['builtInTypes']);
        $this->assertArrayHasKey('success', $serialized['builtInTypes']);
        $this->assertArrayHasKey('danger', $serialized['builtInTypes']);
        $this->assertArrayHasKey('warning', $serialized['builtInTypes']);
    }

    /** @test */
    public function it_handles_badge_field_with_null_values(): void
    {
        $field = Badge::make('Optional Status', 'nonexistent_field')
            ->map([
                'active' => 'success',
                'inactive' => 'danger',
            ])
            ->labels([
                'active' => 'Active',
                'inactive' => 'Inactive',
            ])
            ->nullable();

        $user = User::find(1);
        $field->resolve($user);

        $this->assertNull($field->value);
        $this->assertEquals('info', $field->resolveBadgeType($field->value)); // Default fallback
        $this->assertEquals('', $field->resolveLabel($field->value)); // Empty string for null
    }

    /** @test */
    public function it_handles_badge_field_with_complex_nova_configuration(): void
    {
        $field = Badge::make('User Status')
            ->map([
                'new' => 'info',
                'active' => 'success',
                'suspended' => 'warning',
                'banned' => 'danger',
            ])
            ->types([
                'info' => 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium',
                'success' => 'bg-green-50 text-green-700 ring-green-600/20 font-medium',
                'warning' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 font-medium',
                'danger' => 'bg-red-50 text-red-700 ring-red-600/20 font-medium',
            ])
            ->withIcons()
            ->icons([
                'info' => 'information-circle',
                'success' => 'check-circle',
                'warning' => 'exclamation-triangle',
                'danger' => 'x-circle',
            ])
            ->labels([
                'new' => 'New User',
                'active' => 'Active User',
                'suspended' => 'Suspended User',
                'banned' => 'Banned User',
            ])
            ->help('Displays the current user account status');

        // Test all status types
        $testCases = [
            ['new', 'info', 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium', 'information-circle', 'New User'],
            ['active', 'success', 'bg-green-50 text-green-700 ring-green-600/20 font-medium', 'check-circle', 'Active User'],
            ['suspended', 'warning', 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 font-medium', 'exclamation-triangle', 'Suspended User'],
            ['banned', 'danger', 'bg-red-50 text-red-700 ring-red-600/20 font-medium', 'x-circle', 'Banned User'],
        ];

        foreach ($testCases as [$status, $expectedType, $expectedClasses, $expectedIcon, $expectedLabel]) {
            // Create a mock field with the status value
            $testField = Badge::make('User Status', 'test_status', function () use ($status) {
                return $status;
            })
                ->map($field->valueMap)
                ->types($field->customTypes)
                ->withIcons()
                ->icons($field->iconMap)
                ->labels($field->labelMap);

            $user = User::find(1);
            $testField->resolve($user);

            $this->assertEquals($status, $testField->value);
            $this->assertEquals($expectedType, $testField->resolveBadgeType($testField->value));
            $this->assertEquals($expectedClasses, $testField->resolveBadgeClasses($expectedType));
            $this->assertEquals($expectedIcon, $testField->resolveIcon($expectedType));
            $this->assertEquals($expectedLabel, $testField->resolveLabel($testField->value));
        }
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle with badge field
        $field = Badge::make('Admin Status', 'is_admin')
            ->map([
                true => 'danger',
                false => 'info',
            ])
            ->labels([
                true => 'Administrator',
                false => 'Regular User',
            ]);

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
        $this->assertEquals('info', $field->resolveBadgeType($field->value));
        $this->assertEquals('Regular User', $field->resolveLabel($field->value));

        // UPDATE - Change user to admin
        $newUser->update(['is_admin' => true]);
        $field->resolve($newUser->fresh());
        $this->assertTrue($field->value);
        $this->assertEquals('danger', $field->resolveBadgeType($field->value));
        $this->assertEquals('Administrator', $field->resolveLabel($field->value));

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertTrue($field->value);
        $this->assertEquals('danger', $field->resolveBadgeType($field->value));

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_handles_badge_field_with_validation_rules(): void
    {
        $field = Badge::make('Status', 'is_active')
            ->map([
                true => 'success',
                false => 'danger',
            ])
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
    }

    /** @test */
    public function it_provides_consistent_nova_api_behavior(): void
    {
        // Test that Badge field behaves exactly like Nova's Badge field
        $field = Badge::make('Status')
            ->map(['draft' => 'danger', 'published' => 'success'])
            ->types(['danger' => 'custom-danger-class'])
            ->addTypes(['warning' => 'custom-warning-class'])
            ->withIcons()
            ->icons(['danger' => 'exclamation-triangle'])
            ->labels(['draft' => 'Draft Post'])
            ->nullable()
            ->help('Post status indicator');

        // Test method chaining returns Badge instance
        $this->assertInstanceOf(Badge::class, $field);

        // Test all Nova API methods exist and work
        $this->assertEquals(['draft' => 'danger', 'published' => 'success'], $field->valueMap);
        $this->assertEquals(['danger' => 'custom-danger-class', 'warning' => 'custom-warning-class'], $field->customTypes);
        $this->assertTrue($field->withIcons);
        $this->assertEquals(['danger' => 'exclamation-triangle'], $field->iconMap);
        $this->assertEquals(['draft' => 'Draft Post'], $field->labelMap);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Post status indicator', $field->helpText);

        // Test component name matches Nova
        $this->assertEquals('BadgeField', $field->component);

        // Test serialization includes all Nova properties
        $serialized = $field->jsonSerialize();
        $this->assertArrayHasKey('builtInTypes', $serialized);
        $this->assertArrayHasKey('valueMap', $serialized);
        $this->assertArrayHasKey('customTypes', $serialized);
        $this->assertArrayHasKey('withIcons', $serialized);
        $this->assertArrayHasKey('iconMap', $serialized);
        $this->assertArrayHasKey('labelMap', $serialized);
    }
}
