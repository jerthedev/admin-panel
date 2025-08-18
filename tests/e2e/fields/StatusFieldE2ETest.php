<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Fields\Status;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Status Field E2E Test
 *
 * Tests the complete end-to-end functionality of Status fields
 * including field configuration, data flow, and Nova API compatibility.
 * 
 * Focuses on field integration and behavior rather than
 * web interface testing (which is handled by Playwright tests).
 */
class StatusFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different status values for status testing
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
    public function it_handles_status_field_with_boolean_values(): void
    {
        $field = Status::make('Account Status', 'is_active')
            ->loadingWhen([])
            ->failedWhen([false])
            ->successWhen([true])
            ->labels([
                true => 'Active Account',
                false => 'Inactive Account',
            ]);

        // Test with active user
        $activeUser = User::find(1);
        $field->resolve($activeUser);

        $this->assertEquals([
            'value' => true,
            'label' => 'Active Account',
            'type' => 'success',
            'classes' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'icon' => 'check-circle',
        ], $field->value);

        // Test with inactive user
        $inactiveUser = User::find(3);
        $field->resolve($inactiveUser);

        $this->assertEquals([
            'value' => false,
            'label' => 'Inactive Account',
            'type' => 'failed',
            'classes' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'icon' => 'exclamation-circle',
        ], $field->value);
    }

    /** @test */
    public function it_handles_status_field_with_string_values(): void
    {
        $field = Status::make('User Role', 'name', function ($resource, $attribute) {
            return $resource->is_admin ? 'admin' : 'user';
        })
            ->loadingWhen(['pending'])
            ->failedWhen(['banned'])
            ->successWhen(['admin'])
            ->labels([
                'admin' => 'Administrator',
                'user' => 'Regular User',
                'pending' => 'Pending Approval',
                'banned' => 'Banned User',
            ]);

        // Test with admin user
        $adminUser = User::find(1);
        $field->resolve($adminUser);

        $this->assertEquals([
            'value' => 'admin',
            'label' => 'Administrator',
            'type' => 'success',
            'classes' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'icon' => 'check-circle',
        ], $field->value);

        // Test with regular user
        $regularUser = User::find(2);
        $field->resolve($regularUser);

        $this->assertEquals([
            'value' => 'user',
            'label' => 'Regular User',
            'type' => 'default',
            'classes' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            'icon' => 'information-circle',
        ], $field->value);
    }

    /** @test */
    public function it_handles_status_field_with_custom_types_and_icons(): void
    {
        $field = Status::make('Job Status', 'is_active')
            ->loadingWhen([true])
            ->failedWhen([false])
            ->successWhen([])
            ->types([
                'loading' => 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium',
                'failed' => 'bg-red-50 text-red-700 ring-red-600/20 font-medium',
            ])
            ->icons([
                'loading' => 'clock',
                'failed' => 'x-circle',
            ])
            ->withIcons(true)
            ->labels([
                true => 'Job Running',
                false => 'Job Failed',
            ]);

        $activeUser = User::find(1);
        $field->resolve($activeUser);

        $this->assertEquals([
            'value' => true,
            'label' => 'Job Running',
            'type' => 'loading',
            'classes' => 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium',
            'icon' => 'clock',
        ], $field->value);

        $this->assertTrue($field->withIcons);
        $this->assertEquals(['loading' => 'clock', 'failed' => 'x-circle'], $field->customIcons);
    }

    /** @test */
    public function it_handles_status_field_with_callback_resolution(): void
    {
        $field = Status::make('User Status', 'email', function ($resource, $attribute) {
            if ($resource->is_admin) {
                return 'admin';
            }
            return $resource->is_active ? 'active_user' : 'inactive_user';
        })
            ->loadingWhen(['pending'])
            ->failedWhen(['inactive_user', 'banned'])
            ->successWhen(['admin', 'active_user'])
            ->label(function ($value) {
                return match ($value) {
                    'admin' => 'System Administrator',
                    'active_user' => 'Active Member',
                    'inactive_user' => 'Inactive Member',
                    'pending' => 'Pending Approval',
                    'banned' => 'Banned User',
                    default => 'Unknown Status',
                };
            });

        // Test admin user
        $adminUser = User::find(1);
        $field->resolve($adminUser);

        $this->assertEquals([
            'value' => 'admin',
            'label' => 'System Administrator',
            'type' => 'success',
            'classes' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'icon' => 'check-circle',
        ], $field->value);

        // Test active regular user
        $activeUser = User::find(2);
        $field->resolve($activeUser);

        $this->assertEquals([
            'value' => 'active_user',
            'label' => 'Active Member',
            'type' => 'success',
            'classes' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'icon' => 'check-circle',
        ], $field->value);

        // Test inactive user
        $inactiveUser = User::find(3);
        $field->resolve($inactiveUser);

        $this->assertEquals([
            'value' => 'inactive_user',
            'label' => 'Inactive Member',
            'type' => 'failed',
            'classes' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'icon' => 'exclamation-circle',
        ], $field->value);
    }

    /** @test */
    public function it_handles_status_field_serialization_for_frontend(): void
    {
        $field = Status::make('Processing Status', 'is_active')
            ->loadingWhen(['waiting', 'running'])
            ->failedWhen(['failed', 'error'])
            ->successWhen(['completed', 'done'])
            ->types([
                'loading' => 'custom-loading-class',
                'failed' => 'custom-failed-class',
            ])
            ->icons([
                'loading' => 'clock',
                'failed' => 'x-circle',
            ])
            ->withIcons(true)
            ->labels([
                'waiting' => 'Waiting in Queue',
                'running' => 'Currently Processing',
                'completed' => 'Successfully Completed',
                'failed' => 'Processing Failed',
            ])
            ->help('Shows the current processing status');

        $user = User::find(1);
        $field->resolve($user);

        $serialized = $field->jsonSerialize();

        // Test basic field properties
        $this->assertEquals('Processing Status', $serialized['name']);
        $this->assertEquals('is_active', $serialized['attribute']);
        $this->assertEquals('StatusField', $serialized['component']);
        $this->assertEquals('Shows the current processing status', $serialized['helpText']);

        // Test Nova-specific status properties
        $this->assertArrayHasKey('builtInTypes', $serialized);
        $this->assertArrayHasKey('builtInIcons', $serialized);
        $this->assertEquals(['waiting', 'running'], $serialized['loadingWhen']);
        $this->assertEquals(['failed', 'error'], $serialized['failedWhen']);
        $this->assertEquals(['completed', 'done'], $serialized['successWhen']);
        $this->assertEquals(['loading' => 'custom-loading-class', 'failed' => 'custom-failed-class'], $serialized['customTypes']);
        $this->assertEquals(['loading' => 'clock', 'failed' => 'x-circle'], $serialized['customIcons']);
        $this->assertTrue($serialized['withIcons']);
        $this->assertEquals([
            'waiting' => 'Waiting in Queue',
            'running' => 'Currently Processing',
            'completed' => 'Successfully Completed',
            'failed' => 'Processing Failed',
        ], $serialized['labelMap']);

        // Test built-in types are included
        $this->assertArrayHasKey('loading', $serialized['builtInTypes']);
        $this->assertArrayHasKey('failed', $serialized['builtInTypes']);
        $this->assertArrayHasKey('success', $serialized['builtInTypes']);
        $this->assertArrayHasKey('default', $serialized['builtInTypes']);

        // Test built-in icons are included
        $this->assertArrayHasKey('loading', $serialized['builtInIcons']);
        $this->assertArrayHasKey('failed', $serialized['builtInIcons']);
        $this->assertArrayHasKey('success', $serialized['builtInIcons']);
        $this->assertArrayHasKey('default', $serialized['builtInIcons']);
    }

    /** @test */
    public function it_handles_status_field_with_null_values(): void
    {
        $field = Status::make('Optional Status', 'nonexistent_field')
            ->loadingWhen(['waiting'])
            ->failedWhen(['failed'])
            ->successWhen(['completed'])
            ->labels([
                'waiting' => 'Waiting',
                'failed' => 'Failed',
                'completed' => 'Completed',
            ])
            ->nullable();

        $user = User::find(1);
        $field->resolve($user);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_handles_status_field_with_complex_nova_configuration(): void
    {
        $field = Status::make('Job Status')
            ->loadingWhen(['waiting', 'running', 'processing'])
            ->failedWhen(['failed', 'error', 'cancelled'])
            ->successWhen(['completed', 'finished', 'done'])
            ->types([
                'loading' => 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium',
                'failed' => 'bg-red-50 text-red-700 ring-red-600/20 font-medium',
                'success' => 'bg-green-50 text-green-700 ring-green-600/20 font-medium',
            ])
            ->icons([
                'loading' => 'clock',
                'failed' => 'exclamation-triangle',
                'success' => 'check-circle',
                'default' => 'information-circle',
            ])
            ->withIcons(true)
            ->labels([
                'waiting' => 'Waiting in Queue',
                'running' => 'Currently Processing',
                'processing' => 'Processing Data',
                'failed' => 'Processing Failed',
                'error' => 'System Error',
                'cancelled' => 'Job Cancelled',
                'completed' => 'Successfully Completed',
                'finished' => 'Job Finished',
                'done' => 'All Done',
            ])
            ->help('Displays the current job processing status');

        // Test all status types
        $testCases = [
            ['waiting', 'loading', 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium', 'clock', 'Waiting in Queue'],
            ['running', 'loading', 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium', 'clock', 'Currently Processing'],
            ['processing', 'loading', 'bg-blue-50 text-blue-700 ring-blue-600/20 font-medium', 'clock', 'Processing Data'],
            ['failed', 'failed', 'bg-red-50 text-red-700 ring-red-600/20 font-medium', 'exclamation-triangle', 'Processing Failed'],
            ['error', 'failed', 'bg-red-50 text-red-700 ring-red-600/20 font-medium', 'exclamation-triangle', 'System Error'],
            ['cancelled', 'failed', 'bg-red-50 text-red-700 ring-red-600/20 font-medium', 'exclamation-triangle', 'Job Cancelled'],
            ['completed', 'success', 'bg-green-50 text-green-700 ring-green-600/20 font-medium', 'check-circle', 'Successfully Completed'],
            ['finished', 'success', 'bg-green-50 text-green-700 ring-green-600/20 font-medium', 'check-circle', 'Job Finished'],
            ['done', 'success', 'bg-green-50 text-green-700 ring-green-600/20 font-medium', 'check-circle', 'All Done'],
        ];

        foreach ($testCases as [$status, $expectedType, $expectedClasses, $expectedIcon, $expectedLabel]) {
            // Create a mock field with the status value
            $testField = Status::make('Job Status', 'test_status', function () use ($status) {
                return $status;
            })
                ->loadingWhen($field->loadingWhen)
                ->failedWhen($field->failedWhen)
                ->successWhen($field->successWhen)
                ->types($field->customTypes)
                ->icons($field->customIcons)
                ->withIcons($field->withIcons)
                ->labels($field->labelMap);

            $user = User::find(1);
            $testField->resolve($user);

            $this->assertEquals([
                'value' => $status,
                'label' => $expectedLabel,
                'type' => $expectedType,
                'classes' => $expectedClasses,
                'icon' => $expectedIcon,
            ], $testField->value);
        }
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle with status field
        $field = Status::make('Admin Status', 'is_admin')
            ->loadingWhen([])
            ->failedWhen([false])
            ->successWhen([true])
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
        $this->assertEquals([
            'value' => false,
            'label' => 'Regular User',
            'type' => 'failed',
            'classes' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'icon' => 'exclamation-circle',
        ], $field->value);

        // UPDATE - Change user to admin
        $newUser->update(['is_admin' => true]);
        $field->resolve($newUser->fresh());
        $this->assertEquals([
            'value' => true,
            'label' => 'Administrator',
            'type' => 'success',
            'classes' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'icon' => 'check-circle',
        ], $field->value);

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertEquals([
            'value' => true,
            'label' => 'Administrator',
            'type' => 'success',
            'classes' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'icon' => 'check-circle',
        ], $field->value);

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_handles_status_field_with_validation_rules(): void
    {
        $field = Status::make('Status', 'is_active')
            ->loadingWhen(['pending'])
            ->failedWhen([false])
            ->successWhen([true])
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
        // Test that Status field behaves exactly like Nova's Status field
        $field = Status::make('Job Status')
            ->loadingWhen(['waiting', 'running'])
            ->failedWhen(['failed', 'error'])
            ->successWhen(['completed', 'done'])
            ->types(['loading' => 'custom-loading-class'])
            ->icons(['loading' => 'clock'])
            ->withIcons(true)
            ->labels(['waiting' => 'Waiting in Queue'])
            ->nullable()
            ->help('Job processing status indicator');

        // Test method chaining returns Status instance
        $this->assertInstanceOf(Status::class, $field);

        // Test all Nova API methods exist and work
        $this->assertEquals(['waiting', 'running'], $field->loadingWhen);
        $this->assertEquals(['failed', 'error'], $field->failedWhen);
        $this->assertEquals(['completed', 'done'], $field->successWhen);
        $this->assertEquals(['loading' => 'custom-loading-class'], $field->customTypes);
        $this->assertEquals(['loading' => 'clock'], $field->customIcons);
        $this->assertTrue($field->withIcons);
        $this->assertEquals(['waiting' => 'Waiting in Queue'], $field->labelMap);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Job processing status indicator', $field->helpText);

        // Test component name matches Nova
        $this->assertEquals('StatusField', $field->component);

        // Test serialization includes all Nova properties
        $serialized = $field->jsonSerialize();
        $this->assertArrayHasKey('builtInTypes', $serialized);
        $this->assertArrayHasKey('builtInIcons', $serialized);
        $this->assertArrayHasKey('loadingWhen', $serialized);
        $this->assertArrayHasKey('failedWhen', $serialized);
        $this->assertArrayHasKey('successWhen', $serialized);
        $this->assertArrayHasKey('customTypes', $serialized);
        $this->assertArrayHasKey('customIcons', $serialized);
        $this->assertArrayHasKey('withIcons', $serialized);
        $this->assertArrayHasKey('labelMap', $serialized);
    }
}
