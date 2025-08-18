<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Status;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Status Field Integration Test
 *
 * Tests the complete integration between PHP Status field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 *
 * Focuses on field configuration and behavior rather than
 * database operations, testing the Nova API integration.
 */
class StatusFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different status values
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_status_field_with_nova_syntax(): void
    {
        $field = Status::make('Status');

        $this->assertEquals('Status', $field->name);
        $this->assertEquals('status', $field->attribute);
        $this->assertEquals('StatusField', $field->component);
    }

    /** @test */
    public function it_creates_status_field_with_custom_attribute(): void
    {
        $field = Status::make('Job Status', 'job_status');

        $this->assertEquals('Job Status', $field->name);
        $this->assertEquals('job_status', $field->attribute);
    }

    /** @test */
    public function it_supports_all_nova_status_configuration_methods(): void
    {
        $field = Status::make('Status')
            ->loadingWhen(['waiting', 'running'])
            ->failedWhen(['failed', 'error'])
            ->successWhen(['completed', 'done'])
            ->types(['loading' => 'bg-blue-50 text-blue-700'])
            ->icons(['loading' => 'clock'])
            ->withIcons(true)
            ->labels(['waiting' => 'Waiting in Queue'])
            ->nullable()
            ->help('Select the job status');

        $this->assertEquals(['waiting', 'running'], $field->loadingWhen);
        $this->assertEquals(['failed', 'error'], $field->failedWhen);
        $this->assertEquals(['completed', 'done'], $field->successWhen);
        $this->assertEquals(['loading' => 'bg-blue-50 text-blue-700'], $field->customTypes);
        $this->assertEquals(['loading' => 'clock'], $field->customIcons);
        $this->assertTrue($field->withIcons);
        $this->assertEquals(['waiting' => 'Waiting in Queue'], $field->labelMap);
    }

    /** @test */
    public function it_supports_nova_loading_when_method(): void
    {
        $loadingValues = ['waiting', 'running', 'processing'];

        $field = Status::make('Status')->loadingWhen($loadingValues);

        $this->assertEquals($loadingValues, $field->loadingWhen);
        $this->assertEquals('loading', $field->resolveStatusType('waiting'));
        $this->assertEquals('loading', $field->resolveStatusType('running'));
        $this->assertEquals('loading', $field->resolveStatusType('processing'));
    }

    /** @test */
    public function it_supports_nova_failed_when_method(): void
    {
        $failedValues = ['failed', 'error', 'cancelled'];

        $field = Status::make('Status')->failedWhen($failedValues);

        $this->assertEquals($failedValues, $field->failedWhen);
        $this->assertEquals('failed', $field->resolveStatusType('failed'));
        $this->assertEquals('failed', $field->resolveStatusType('error'));
        $this->assertEquals('failed', $field->resolveStatusType('cancelled'));
    }

    /** @test */
    public function it_supports_nova_success_when_method(): void
    {
        $successValues = ['completed', 'finished', 'done'];

        $field = Status::make('Status')->successWhen($successValues);

        $this->assertEquals($successValues, $field->successWhen);
        $this->assertEquals('success', $field->resolveStatusType('completed'));
        $this->assertEquals('success', $field->resolveStatusType('finished'));
        $this->assertEquals('success', $field->resolveStatusType('done'));
    }

    /** @test */
    public function it_supports_nova_types_method(): void
    {
        $customTypes = [
            'loading' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            'failed' => 'bg-red-50 text-red-700 ring-red-600/20',
        ];

        $field = Status::make('Status')->types($customTypes);

        $this->assertEquals($customTypes, $field->customTypes);
        $this->assertEquals('bg-blue-50 text-blue-700 ring-blue-600/20', $field->resolveStatusClasses('loading'));
        $this->assertEquals('bg-red-50 text-red-700 ring-red-600/20', $field->resolveStatusClasses('failed'));
    }

    /** @test */
    public function it_supports_nova_icons_method(): void
    {
        $customIcons = [
            'loading' => 'clock',
            'failed' => 'exclamation-triangle',
            'success' => 'check-circle',
        ];

        $field = Status::make('Status')->icons($customIcons);

        $this->assertEquals($customIcons, $field->customIcons);
        $this->assertEquals('clock', $field->resolveIcon('loading'));
        $this->assertEquals('exclamation-triangle', $field->resolveIcon('failed'));
        $this->assertEquals('check-circle', $field->resolveIcon('success'));
    }

    /** @test */
    public function it_supports_nova_with_icons_method(): void
    {
        $field = Status::make('Status')->withIcons(false);

        $this->assertFalse($field->withIcons);
        $this->assertNull($field->resolveIcon('loading'));

        $field = Status::make('Status')->withIcons(true);

        $this->assertTrue($field->withIcons);
        $this->assertEquals('spinner', $field->resolveIcon('loading'));
    }

    /** @test */
    public function it_supports_nova_label_method(): void
    {
        $labelCallback = function ($value) {
            return strtoupper($value) . ' STATUS';
        };

        $field = Status::make('Status')->label($labelCallback);

        $this->assertEquals($labelCallback, $field->labelCallback);
        $this->assertEquals('WAITING STATUS', $field->resolveLabel('waiting'));
        $this->assertEquals('COMPLETED STATUS', $field->resolveLabel('completed'));
    }

    /** @test */
    public function it_supports_nova_labels_method(): void
    {
        $labelMap = [
            'waiting' => 'Waiting in Queue',
            'running' => 'Currently Processing',
            'completed' => 'Successfully Completed',
        ];

        $field = Status::make('Status')->labels($labelMap);

        $this->assertEquals($labelMap, $field->labelMap);
        $this->assertEquals('Waiting in Queue', $field->resolveLabel('waiting'));
        $this->assertEquals('Currently Processing', $field->resolveLabel('running'));
        $this->assertEquals('Successfully Completed', $field->resolveLabel('completed'));
    }

    /** @test */
    public function it_resolves_status_field_value_with_callback(): void
    {
        $user = User::find(1);
        $field = Status::make('User Status', 'name', function ($resource, $attribute) {
            return strtolower($resource->{$attribute}) === 'john doe' ? 'active' : 'inactive';
        });

        $field->resolve($user);

        $this->assertEquals([
            'value' => 'active',
            'label' => 'Active',
            'type' => 'default',
            'classes' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            'icon' => 'information-circle',
        ], $field->value);
    }

    /** @test */
    public function it_handles_status_type_resolution(): void
    {
        $field = Status::make('Status')
            ->loadingWhen(['waiting', 'running'])
            ->failedWhen(['failed', 'error'])
            ->successWhen(['completed', 'done']);

        // Test mapped values
        $this->assertEquals('loading', $field->resolveStatusType('waiting'));
        $this->assertEquals('loading', $field->resolveStatusType('running'));
        $this->assertEquals('failed', $field->resolveStatusType('failed'));
        $this->assertEquals('failed', $field->resolveStatusType('error'));
        $this->assertEquals('success', $field->resolveStatusType('completed'));
        $this->assertEquals('success', $field->resolveStatusType('done'));

        // Test unmapped value (should default to 'default')
        $this->assertEquals('default', $field->resolveStatusType('unknown'));
    }

    /** @test */
    public function it_handles_status_classes_resolution(): void
    {
        $field = Status::make('Status')
            ->types([
                'loading' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                'failed' => 'bg-red-50 text-red-700 ring-red-600/20',
            ]);

        // Test custom types
        $this->assertEquals('bg-blue-50 text-blue-700 ring-blue-600/20', $field->resolveStatusClasses('loading'));
        $this->assertEquals('bg-red-50 text-red-700 ring-red-600/20', $field->resolveStatusClasses('failed'));

        // Test fallback to built-in types
        $expectedBuiltInSuccess = 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        $this->assertEquals($expectedBuiltInSuccess, $field->resolveStatusClasses('success'));
    }

    /** @test */
    public function it_handles_icon_resolution(): void
    {
        $field = Status::make('Status')->icons([
            'loading' => 'clock',
            'failed' => 'exclamation-triangle',
        ]);

        $this->assertEquals('clock', $field->resolveIcon('loading'));
        $this->assertEquals('exclamation-triangle', $field->resolveIcon('failed'));
        $this->assertEquals('check-circle', $field->resolveIcon('success')); // Built-in fallback
    }

    /** @test */
    public function it_handles_label_resolution(): void
    {
        // Test with label mapping
        $field1 = Status::make('Status')->labels([
            'waiting' => 'Waiting in Queue',
            'completed' => 'Successfully Completed',
        ]);

        $this->assertEquals('Waiting in Queue', $field1->resolveLabel('waiting'));
        $this->assertEquals('Successfully Completed', $field1->resolveLabel('completed'));
        $this->assertEquals('Unknown status', $field1->resolveLabel('unknown_status')); // Default formatting

        // Test with label callback
        $field2 = Status::make('Status')->label(function ($value) {
            return ucfirst($value) . ' Status';
        });

        $this->assertEquals('Waiting Status', $field2->resolveLabel('waiting'));
        $this->assertEquals('Completed Status', $field2->resolveLabel('completed'));

        // Test default behavior
        $field3 = Status::make('Status');
        $this->assertEquals('Waiting', $field3->resolveLabel('waiting'));
        $this->assertEquals('In progress', $field3->resolveLabel('in_progress'));
    }

    /** @test */
    public function it_serializes_status_field_for_frontend(): void
    {
        $field = Status::make('Status')
            ->loadingWhen(['waiting', 'running'])
            ->failedWhen(['failed', 'error'])
            ->successWhen(['completed', 'done'])
            ->types(['loading' => 'custom-loading-class'])
            ->icons(['loading' => 'clock'])
            ->withIcons(true)
            ->labels(['waiting' => 'Waiting in Queue'])
            ->help('Select the job status');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Status', $serialized['name']);
        $this->assertEquals('status', $serialized['attribute']);
        $this->assertEquals('StatusField', $serialized['component']);
        $this->assertEquals('Select the job status', $serialized['helpText']);

        // Check meta properties
        $this->assertArrayHasKey('builtInTypes', $serialized);
        $this->assertArrayHasKey('builtInIcons', $serialized);
        $this->assertEquals(['waiting', 'running'], $serialized['loadingWhen']);
        $this->assertEquals(['failed', 'error'], $serialized['failedWhen']);
        $this->assertEquals(['completed', 'done'], $serialized['successWhen']);
        $this->assertEquals(['loading' => 'custom-loading-class'], $serialized['customTypes']);
        $this->assertEquals(['loading' => 'clock'], $serialized['customIcons']);
        $this->assertTrue($serialized['withIcons']);
        $this->assertEquals(['waiting' => 'Waiting in Queue'], $serialized['labelMap']);
    }

    /** @test */
    public function it_inherits_all_field_methods(): void
    {
        $field = Status::make('Status');

        // Test that Status field inherits all base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'jsonSerialize'));

        // Test Nova-specific Status methods
        $this->assertTrue(method_exists($field, 'loadingWhen'));
        $this->assertTrue(method_exists($field, 'failedWhen'));
        $this->assertTrue(method_exists($field, 'successWhen'));
        $this->assertTrue(method_exists($field, 'types'));
        $this->assertTrue(method_exists($field, 'icons'));
        $this->assertTrue(method_exists($field, 'withIcons'));
        $this->assertTrue(method_exists($field, 'label'));
        $this->assertTrue(method_exists($field, 'labels'));
    }

    /** @test */
    public function it_handles_complex_status_field_configuration(): void
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
                'completed' => 'Successfully Completed',
                'failed' => 'Processing Failed',
            ])
            ->nullable()
            ->help('Select the current status of the job');

        // Test all configurations are set correctly
        $this->assertEquals('loading', $field->resolveStatusType('waiting'));
        $this->assertEquals('bg-blue-50 text-blue-700 ring-blue-600/20 font-medium', $field->resolveStatusClasses('loading'));
        $this->assertEquals('clock', $field->resolveIcon('loading'));
        $this->assertEquals('Waiting in Queue', $field->resolveLabel('waiting'));

        $this->assertTrue($field->withIcons);
        $this->assertCount(3, $field->loadingWhen);
        $this->assertCount(3, $field->failedWhen);
        $this->assertCount(3, $field->successWhen);
        $this->assertCount(3, $field->customTypes);
        $this->assertCount(4, $field->customIcons);
        $this->assertCount(4, $field->labelMap);

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('Job Status', $serialized['name']);
        $this->assertEquals('job_status', $serialized['attribute']);
        $this->assertEquals('Select the current status of the job', $serialized['helpText']);
        $this->assertTrue($serialized['withIcons']);
        $this->assertCount(3, $serialized['loadingWhen']);
        $this->assertCount(3, $serialized['failedWhen']);
        $this->assertCount(3, $serialized['successWhen']);
        $this->assertCount(3, $serialized['customTypes']);
        $this->assertCount(4, $serialized['customIcons']);
        $this->assertCount(4, $serialized['labelMap']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Status::make('Status')
            ->loadingWhen(['waiting', 'running'])
            ->failedWhen(['failed', 'error'])
            ->successWhen(['completed', 'done'])
            ->types(['loading' => 'custom-loading-class'])
            ->icons(['loading' => 'clock'])
            ->withIcons(true)
            ->labels(['waiting' => 'Waiting'])
            ->nullable()
            ->help('Status field')
            ->rules('required');

        $this->assertInstanceOf(Status::class, $field);
        $this->assertEquals(['waiting', 'running'], $field->loadingWhen);
        $this->assertEquals(['failed', 'error'], $field->failedWhen);
        $this->assertEquals(['completed', 'done'], $field->successWhen);
        $this->assertEquals(['loading' => 'custom-loading-class'], $field->customTypes);
        $this->assertEquals(['loading' => 'clock'], $field->customIcons);
        $this->assertTrue($field->withIcons);
        $this->assertEquals(['waiting' => 'Waiting'], $field->labelMap);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_status_field(): void
    {
        // Create a completely fresh field instance to avoid test pollution
        $field = new Status('Status');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Status::class, $field->loadingWhen([]));
        $this->assertInstanceOf(Status::class, $field->failedWhen([]));
        $this->assertInstanceOf(Status::class, $field->successWhen([]));
        $this->assertInstanceOf(Status::class, $field->types([]));
        $this->assertInstanceOf(Status::class, $field->icons([]));
        $this->assertInstanceOf(Status::class, $field->withIcons());
        $this->assertInstanceOf(Status::class, $field->label(fn($v) => $v));
        $this->assertInstanceOf(Status::class, $field->labels([]));

        // Test component name matches Nova
        $this->assertEquals('StatusField', $field->component);

        // Test built-in status types match Nova
        $this->assertArrayHasKey('loading', $field->builtInTypes);
        $this->assertArrayHasKey('failed', $field->builtInTypes);
        $this->assertArrayHasKey('success', $field->builtInTypes);
        $this->assertArrayHasKey('default', $field->builtInTypes);

        // Test built-in icons exist
        $this->assertArrayHasKey('loading', $field->builtInIcons);
        $this->assertArrayHasKey('failed', $field->builtInIcons);
        $this->assertArrayHasKey('success', $field->builtInIcons);
        $this->assertArrayHasKey('default', $field->builtInIcons);

        // Test default values - create a completely fresh instance to avoid test pollution
        $freshField = new Status('Fresh Status');
        $this->assertEquals([], $freshField->loadingWhen);
        $this->assertEquals([], $freshField->failedWhen);
        $this->assertEquals([], $freshField->successWhen);
        $this->assertEquals([], $freshField->customTypes);
        $this->assertEquals([], $freshField->customIcons);
        $this->assertTrue($freshField->withIcons);
        $this->assertNull($freshField->labelCallback);
        $this->assertEquals([], $freshField->labelMap);
    }
}
