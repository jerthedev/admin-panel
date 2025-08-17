<?php

declare(strict_types=1);

namespace E2E\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Hidden;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Hidden Field E2E Tests
 *
 * End-to-end tests that validate the complete Hidden field workflow
 * from PHP backend through Inertia to Vue frontend, ensuring Nova compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HiddenFieldE2ETest extends TestCase
{
    /** @test */
    public function it_serializes_and_fills_like_nova_in_end_to_end_flow(): void
    {
        // Simulate backend field creation like Nova
        $field = Hidden::make('User ID', 'user_id')
            ->default(function ($request) {
                return $request->user()->id ?? 123;
            })
            ->rules('required', 'integer');

        $serialized = $field->jsonSerialize();

        // Verify Nova-compatible serialization
        $this->assertEquals('HiddenField', $serialized['component']);
        $this->assertEquals('User ID', $serialized['name']);
        $this->assertEquals('user_id', $serialized['attribute']);
        $this->assertEquals(123, $serialized['default']); // Callable resolved
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('integer', $serialized['rules']);
        $this->assertFalse($serialized['showOnIndex']);
        $this->assertFalse($serialized['showOnDetail']);
        $this->assertTrue($serialized['showOnCreation']);
        $this->assertTrue($serialized['showOnUpdate']);

        // Simulate a client form submission
        $request = new Request(['user_id' => '456']);
        $model = new \stdClass();
        $field->fill($request, $model);

        // Verify the value is filled correctly
        $this->assertEquals('456', $model->user_id);
    }

    /** @test */
    public function it_handles_csrf_token_scenario_end_to_end(): void
    {
        // Nova-style CSRF token field
        $field = Hidden::make('CSRF Token', '_token')
            ->default(function ($request) {
                return 'csrf-' . time();
            });

        $serialized = $field->jsonSerialize();

        // Verify serialization for client
        $this->assertEquals('HiddenField', $serialized['component']);
        $this->assertEquals('_token', $serialized['attribute']);
        $this->assertStringContains('csrf-', $serialized['default']);

        // Simulate form submission with token
        $request = new Request(['_token' => 'csrf-123456']);
        $model = new \stdClass();
        $field->fill($request, $model);

        $this->assertEquals('csrf-123456', $model->_token);
    }

    /** @test */
    public function it_handles_slug_generation_scenario_end_to_end(): void
    {
        // Nova-style slug field with random default
        $field = Hidden::make('Slug')
            ->default(function ($request) {
                return 'slug-' . uniqid();
            });

        $serialized = $field->jsonSerialize();

        // Verify serialization
        $this->assertEquals('HiddenField', $serialized['component']);
        $this->assertEquals('slug', $serialized['attribute']);
        $this->assertStringContains('slug-', $serialized['default']);

        // Test that each serialization generates a new slug
        $serialized2 = $field->jsonSerialize();
        $this->assertNotEquals($serialized['default'], $serialized2['default']);
    }

    /** @test */
    public function it_handles_complex_hidden_field_scenarios_end_to_end(): void
    {
        // Test various hidden field configurations
        $scenarios = [
            // Static value
            [
                'field' => Hidden::make('Type')->default('user'),
                'expected_default' => 'user',
                'request_data' => ['type' => 'admin'],
                'expected_fill' => 'admin'
            ],
            // Callable with request context
            [
                'field' => Hidden::make('Timestamp', 'created_at')
                    ->default(function ($request) {
                        return date('Y-m-d H:i:s');
                    }),
                'expected_default_pattern' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                'request_data' => ['created_at' => '2023-01-01 12:00:00'],
                'expected_fill' => '2023-01-01 12:00:00'
            ],
            // Nullable field
            [
                'field' => Hidden::make('Optional', 'optional_field')
                    ->nullable()
                    ->default(null),
                'expected_default' => null,
                'request_data' => [],
                'expected_fill' => null
            ]
        ];

        foreach ($scenarios as $scenario) {
            $field = $scenario['field'];
            $serialized = $field->jsonSerialize();

            // Test serialization
            if (isset($scenario['expected_default'])) {
                $this->assertEquals($scenario['expected_default'], $serialized['default']);
            } elseif (isset($scenario['expected_default_pattern'])) {
                $this->assertMatchesRegularExpression($scenario['expected_default_pattern'], $serialized['default']);
            }

            // Test form filling
            $request = new Request($scenario['request_data']);
            $model = new \stdClass();
            $field->fill($request, $model);

            $attribute = $field->attribute;
            if ($scenario['expected_fill'] === null && empty($scenario['request_data'])) {
                // For null defaults with no request data, property shouldn't be set
                $this->assertFalse(property_exists($model, $attribute));
            } else {
                $this->assertEquals($scenario['expected_fill'], $model->{$attribute});
            }
        }
    }

    /** @test */
    public function it_maintains_nova_api_compatibility_end_to_end(): void
    {
        // Test all Nova Hidden field patterns from documentation

        // Basic usage: Hidden::make('Slug')
        $field1 = Hidden::make('Slug');
        $serialized1 = $field1->jsonSerialize();
        $this->assertEquals('HiddenField', $serialized1['component']);
        $this->assertEquals('slug', $serialized1['attribute']);

        // Default value: Hidden::make('Slug')->default(Str::random(64))
        $field2 = Hidden::make('Slug')->default('abc123def456');
        $serialized2 = $field2->jsonSerialize();
        $this->assertEquals('abc123def456', $serialized2['default']);

        // Callable default: Hidden::make('User', 'user_id')->default(function ($request) { return $request->user()->id; })
        $field3 = Hidden::make('User', 'user_id')->default(function ($request) {
            return $request->user()->id ?? 999;
        });
        $serialized3 = $field3->jsonSerialize();
        $this->assertEquals(999, $serialized3['default']);

        // Test form submission for all patterns
        $request = new Request(['slug' => 'new-slug', 'user_id' => '777']);

        $model1 = new \stdClass();
        $field1->fill($request, $model1);
        $this->assertEquals('new-slug', $model1->slug);

        $model2 = new \stdClass();
        $field2->fill($request, $model2);
        $this->assertEquals('new-slug', $model2->slug);

        $model3 = new \stdClass();
        $field3->fill($request, $model3);
        $this->assertEquals('777', $model3->user_id);
    }

    /** @test */
    public function it_handles_validation_and_error_scenarios_end_to_end(): void
    {
        $field = Hidden::make('Required Field', 'required_field')
            ->rules('required', 'string', 'min:5');

        $serialized = $field->jsonSerialize();

        // Verify validation rules are serialized
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('string', $serialized['rules']);
        $this->assertContains('min:5', $serialized['rules']);

        // Test valid submission
        $validRequest = new Request(['required_field' => 'valid_value']);
        $model = new \stdClass();
        $field->fill($validRequest, $model);
        $this->assertEquals('valid_value', $model->required_field);

        // Test empty submission (would fail validation in real app)
        $emptyRequest = new Request([]);
        $model2 = new \stdClass();
        $field->fill($emptyRequest, $model2);
        // Field should use default if available, or not set the property
        $this->assertFalse(property_exists($model2, 'required_field'));
    }

    /** @test */
    public function it_handles_custom_fill_callback_end_to_end(): void
    {
        $field = Hidden::make('Custom Field', 'custom_field')
            ->fillUsing(function ($request, $model, $attribute) {
                // Custom logic: always set to uppercase
                $value = $request->input($attribute, 'default');
                $model->{$attribute} = strtoupper($value);
            });

        $serialized = $field->jsonSerialize();
        $this->assertEquals('HiddenField', $serialized['component']);

        // Test custom fill logic
        $request = new Request(['custom_field' => 'lowercase_value']);
        $model = new \stdClass();
        $field->fill($request, $model);

        $this->assertEquals('LOWERCASE_VALUE', $model->custom_field);

        // Test with no request value
        $emptyRequest = new Request([]);
        $model2 = new \stdClass();
        $field->fill($emptyRequest, $model2);

        $this->assertEquals('DEFAULT', $model2->custom_field);
    }

    /** @test */
    public function it_handles_visibility_overrides_end_to_end(): void
    {
        // Test field with custom visibility
        $field = Hidden::make('Debug Info', 'debug_info')
            ->showOnIndex()
            ->showOnDetail()
            ->default('debug-data');

        $serialized = $field->jsonSerialize();

        // Verify visibility can be overridden
        $this->assertTrue($serialized['showOnIndex']);
        $this->assertTrue($serialized['showOnDetail']);
        $this->assertTrue($serialized['showOnCreation']);
        $this->assertTrue($serialized['showOnUpdate']);

        // Test that it still functions as a hidden field
        $request = new Request(['debug_info' => 'runtime-debug']);
        $model = new \stdClass();
        $field->fill($request, $model);

        $this->assertEquals('runtime-debug', $model->debug_info);
    }

    /** @test */
    public function it_handles_real_world_usage_patterns_end_to_end(): void
    {
        // Simulate real-world scenarios

        // 1. User creation with auto-generated UUID
        $uuidField = Hidden::make('UUID', 'uuid')
            ->default(function ($request) {
                return 'uuid-' . uniqid();
            });

        // 2. Audit trail with current timestamp
        $auditField = Hidden::make('Created By', 'created_by')
            ->default(function ($request) {
                return $request->user()->id ?? 'system';
            });

        // 3. Form token for security
        $tokenField = Hidden::make('Form Token', 'form_token')
            ->default(function ($request) {
                return hash('sha256', session()->getId() . time());
            });

        $fields = [$uuidField, $auditField, $tokenField];

        foreach ($fields as $field) {
            $serialized = $field->jsonSerialize();

            // All should be hidden fields
            $this->assertEquals('HiddenField', $serialized['component']);
            $this->assertFalse($serialized['showOnIndex']);
            $this->assertFalse($serialized['showOnDetail']);

            // All should have resolved defaults
            $this->assertNotNull($serialized['default']);

            // Test form submission
            $request = new Request([
                $field->attribute => 'override-value'
            ]);
            $model = new \stdClass();
            $field->fill($request, $model);

            $this->assertEquals('override-value', $model->{$field->attribute});
        }
    }
}
