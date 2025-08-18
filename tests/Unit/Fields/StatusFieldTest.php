<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Status;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Status Field Unit Tests.
 *
 * Tests for Status field class with 100% Nova API compatibility.
 * Tests all Nova Status field features including loadingWhen, failedWhen,
 * successWhen, types, icons, withIcons, label, and labels methods.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class StatusFieldTest extends TestCase
{
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
    public function it_has_correct_default_properties(): void
    {
        $field = Status::make('Status');

        // Test built-in types
        $expectedBuiltInTypes = [
            'loading' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'default' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        ];
        $this->assertEquals($expectedBuiltInTypes, $field->builtInTypes);

        // Test built-in icons
        $expectedBuiltInIcons = [
            'loading' => 'spinner',
            'failed' => 'exclamation-circle',
            'success' => 'check-circle',
            'default' => 'information-circle',
        ];
        $this->assertEquals($expectedBuiltInIcons, $field->builtInIcons);

        $this->assertEquals([], $field->loadingWhen);
        $this->assertEquals([], $field->failedWhen);
        $this->assertEquals([], $field->successWhen);
        $this->assertEquals([], $field->customTypes);
        $this->assertEquals([], $field->customIcons);
        $this->assertTrue($field->withIcons);
        $this->assertNull($field->labelCallback);
        $this->assertEquals([], $field->labelMap);
    }

    /** @test */
    public function it_supports_nova_loading_when_method(): void
    {
        $loadingValues = ['waiting', 'running', 'processing'];

        $field = Status::make('Status')->loadingWhen($loadingValues);

        $this->assertEquals($loadingValues, $field->loadingWhen);
    }

    /** @test */
    public function it_supports_nova_failed_when_method(): void
    {
        $failedValues = ['failed', 'error', 'cancelled'];

        $field = Status::make('Status')->failedWhen($failedValues);

        $this->assertEquals($failedValues, $field->failedWhen);
    }

    /** @test */
    public function it_supports_nova_success_when_method(): void
    {
        $successValues = ['completed', 'finished', 'done'];

        $field = Status::make('Status')->successWhen($successValues);

        $this->assertEquals($successValues, $field->successWhen);
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
    }

    /** @test */
    public function it_supports_nova_icons_method(): void
    {
        $customIcons = [
            'loading' => 'clock',
            'failed' => 'x-circle',
            'success' => 'shield-check',
        ];

        $field = Status::make('Status')->icons($customIcons);

        $this->assertEquals($customIcons, $field->customIcons);
    }

    /** @test */
    public function it_supports_nova_with_icons_method(): void
    {
        $field = Status::make('Status')->withIcons(false);

        $this->assertFalse($field->withIcons);

        $field = Status::make('Status')->withIcons(true);

        $this->assertTrue($field->withIcons);
    }

    /** @test */
    public function it_supports_nova_label_method(): void
    {
        $labelCallback = function ($value) {
            return strtoupper($value);
        };

        $field = Status::make('Status')->label($labelCallback);

        $this->assertEquals($labelCallback, $field->labelCallback);
    }

    /** @test */
    public function it_supports_nova_labels_method(): void
    {
        $labelMap = [
            'waiting' => 'Waiting for Processing',
            'running' => 'Currently Running',
            'completed' => 'Successfully Completed',
        ];

        $field = Status::make('Status')->labels($labelMap);

        $this->assertEquals($labelMap, $field->labelMap);
    }

    /** @test */
    public function it_resolves_status_type_correctly(): void
    {
        $field = Status::make('Status')
            ->loadingWhen(['waiting', 'running'])
            ->failedWhen(['failed', 'error'])
            ->successWhen(['completed', 'done']);

        $this->assertEquals('loading', $field->resolveStatusType('waiting'));
        $this->assertEquals('loading', $field->resolveStatusType('running'));
        $this->assertEquals('failed', $field->resolveStatusType('failed'));
        $this->assertEquals('failed', $field->resolveStatusType('error'));
        $this->assertEquals('success', $field->resolveStatusType('completed'));
        $this->assertEquals('success', $field->resolveStatusType('done'));
        $this->assertEquals('default', $field->resolveStatusType('unknown')); // Default
    }

    /** @test */
    public function it_resolves_status_classes_with_built_in_types(): void
    {
        $field = Status::make('Status');

        $this->assertEquals(
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            $field->resolveStatusClasses('loading'),
        );
        $this->assertEquals(
            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            $field->resolveStatusClasses('failed'),
        );
        $this->assertEquals(
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            $field->resolveStatusClasses('success'),
        );
        $this->assertEquals(
            'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
            $field->resolveStatusClasses('default'),
        );
    }

    /** @test */
    public function it_resolves_status_classes_with_custom_types(): void
    {
        $customTypes = [
            'loading' => 'bg-blue-50 text-blue-700',
            'failed' => 'bg-red-50 text-red-700',
        ];

        $field = Status::make('Status')->types($customTypes);

        $this->assertEquals('bg-blue-50 text-blue-700', $field->resolveStatusClasses('loading'));
        $this->assertEquals('bg-red-50 text-red-700', $field->resolveStatusClasses('failed'));

        // Should fall back to built-in for unknown custom types
        $this->assertEquals(
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            $field->resolveStatusClasses('success'),
        );
    }

    /** @test */
    public function it_resolves_icons_correctly(): void
    {
        $field = Status::make('Status');

        $this->assertEquals('spinner', $field->resolveIcon('loading'));
        $this->assertEquals('exclamation-circle', $field->resolveIcon('failed'));
        $this->assertEquals('check-circle', $field->resolveIcon('success'));
        $this->assertEquals('information-circle', $field->resolveIcon('default'));
    }

    /** @test */
    public function it_resolves_custom_icons_correctly(): void
    {
        $customIcons = [
            'loading' => 'clock',
            'failed' => 'x-circle',
        ];

        $field = Status::make('Status')->icons($customIcons);

        $this->assertEquals('clock', $field->resolveIcon('loading'));
        $this->assertEquals('x-circle', $field->resolveIcon('failed'));

        // Should fall back to built-in for unknown custom icons
        $this->assertEquals('check-circle', $field->resolveIcon('success'));
    }

    /** @test */
    public function it_returns_null_icon_when_icons_disabled(): void
    {
        $field = Status::make('Status')->withIcons(false);

        $this->assertNull($field->resolveIcon('loading'));
        $this->assertNull($field->resolveIcon('failed'));
        $this->assertNull($field->resolveIcon('success'));
    }

    /** @test */
    public function it_resolves_labels_with_callback(): void
    {
        $field = Status::make('Status')->label(function ($value) {
            return strtoupper($value);
        });

        $this->assertEquals('WAITING', $field->resolveLabel('waiting'));
        $this->assertEquals('COMPLETED', $field->resolveLabel('completed'));
    }

    /** @test */
    public function it_resolves_labels_with_mapping(): void
    {
        $labelMap = [
            'waiting' => 'Waiting for Processing',
            'completed' => 'Successfully Completed',
        ];

        $field = Status::make('Status')->labels($labelMap);

        $this->assertEquals('Waiting for Processing', $field->resolveLabel('waiting'));
        $this->assertEquals('Successfully Completed', $field->resolveLabel('completed'));
        $this->assertEquals('Unknown status', $field->resolveLabel('unknown_status')); // Default formatting
    }

    /** @test */
    public function it_resolves_labels_with_default_behavior(): void
    {
        $field = Status::make('Status');

        $this->assertEquals('Waiting', $field->resolveLabel('waiting'));
        $this->assertEquals('In progress', $field->resolveLabel('in_progress'));
        $this->assertEquals('Multi word status', $field->resolveLabel('multi-word-status'));
    }

    /** @test */
    public function it_serializes_all_nova_properties_in_meta(): void
    {
        $loadingWhen = ['waiting', 'running'];
        $failedWhen = ['failed', 'error'];
        $successWhen = ['completed', 'done'];
        $customTypes = ['loading' => 'custom-loading-class'];
        $customIcons = ['loading' => 'clock'];
        $labelMap = ['waiting' => 'Waiting'];

        $field = Status::make('Status')
            ->loadingWhen($loadingWhen)
            ->failedWhen($failedWhen)
            ->successWhen($successWhen)
            ->types($customTypes)
            ->icons($customIcons)
            ->withIcons(false)
            ->labels($labelMap);

        $meta = $field->meta();

        $this->assertArrayHasKey('builtInTypes', $meta);
        $this->assertArrayHasKey('builtInIcons', $meta);
        $this->assertEquals($loadingWhen, $meta['loadingWhen']);
        $this->assertEquals($failedWhen, $meta['failedWhen']);
        $this->assertEquals($successWhen, $meta['successWhen']);
        $this->assertEquals($customTypes, $meta['customTypes']);
        $this->assertEquals($customIcons, $meta['customIcons']);
        $this->assertFalse($meta['withIcons']);
        $this->assertEquals($labelMap, $meta['labelMap']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Status::make('Status')
            ->loadingWhen(['waiting', 'running'])
            ->failedWhen(['failed', 'error'])
            ->successWhen(['completed', 'done'])
            ->types(['loading' => 'custom-class'])
            ->icons(['loading' => 'clock'])
            ->withIcons(true)
            ->labels(['waiting' => 'Waiting']);

        $this->assertInstanceOf(Status::class, $field);
        $this->assertEquals(['waiting', 'running'], $field->loadingWhen);
        $this->assertEquals(['failed', 'error'], $field->failedWhen);
        $this->assertEquals(['completed', 'done'], $field->successWhen);
        $this->assertEquals(['loading' => 'custom-class'], $field->customTypes);
        $this->assertEquals(['loading' => 'clock'], $field->customIcons);
        $this->assertTrue($field->withIcons);
        $this->assertEquals(['waiting' => 'Waiting'], $field->labelMap);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_status_field(): void
    {
        $field = Status::make('Status');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Status::class, $field->loadingWhen([]));
        $this->assertInstanceOf(Status::class, $field->failedWhen([]));
        $this->assertInstanceOf(Status::class, $field->successWhen([]));
        $this->assertInstanceOf(Status::class, $field->types([]));
        $this->assertInstanceOf(Status::class, $field->icons([]));
        $this->assertInstanceOf(Status::class, $field->withIcons());
        $this->assertInstanceOf(Status::class, $field->label(fn ($v) => $v));
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
    }

    /** @test */
    public function it_handles_complex_nova_configuration(): void
    {
        $field = Status::make('Job Status')
            ->loadingWhen(['waiting', 'running', 'processing'])
            ->failedWhen(['failed', 'error', 'cancelled'])
            ->successWhen(['completed', 'finished', 'done'])
            ->types([
                'loading' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                'failed' => 'bg-red-50 text-red-700 ring-red-600/20',
                'success' => 'bg-green-50 text-green-700 ring-green-600/20',
            ])
            ->icons([
                'loading' => 'clock',
                'failed' => 'exclamation-triangle',
                'success' => 'check-circle',
            ])
            ->withIcons(true)
            ->labels([
                'waiting' => 'Waiting in Queue',
                'running' => 'Currently Processing',
                'completed' => 'Successfully Completed',
                'failed' => 'Processing Failed',
            ]);

        // Test all configurations are set correctly
        $this->assertEquals('loading', $field->resolveStatusType('waiting'));
        $this->assertEquals('failed', $field->resolveStatusType('failed'));
        $this->assertEquals('success', $field->resolveStatusType('completed'));

        $this->assertEquals('bg-blue-50 text-blue-700 ring-blue-600/20', $field->resolveStatusClasses('loading'));
        $this->assertEquals('bg-red-50 text-red-700 ring-red-600/20', $field->resolveStatusClasses('failed'));

        $this->assertEquals('clock', $field->resolveIcon('loading'));
        $this->assertEquals('exclamation-triangle', $field->resolveIcon('failed'));

        $this->assertEquals('Waiting in Queue', $field->resolveLabel('waiting'));
        $this->assertEquals('Processing Failed', $field->resolveLabel('failed'));

        $this->assertTrue($field->withIcons);
        $this->assertCount(3, $field->loadingWhen);
        $this->assertCount(3, $field->failedWhen);
        $this->assertCount(3, $field->successWhen);
        $this->assertCount(3, $field->customTypes);
        $this->assertCount(3, $field->customIcons);
        $this->assertCount(4, $field->labelMap);
    }
}
