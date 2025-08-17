<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\KeyValue;
use JTD\AdminPanel\Tests\TestCase;

/**
 * KeyValue Field Integration Tests
 *
 * Tests the complete integration between PHP backend and frontend for KeyValue field.
 * Validates PHP/Inertia/Vue interoperability and all API options.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class KeyValueFieldIntegrationTest extends TestCase
{
    /** @test */
    public function it_serializes_field_configuration_for_frontend(): void
    {
        $field = KeyValue::make('Meta')
            ->keyLabel('Property')
            ->valueLabel('Content')
            ->actionText('Add new item')
            ->help('Enter key-value pairs')
            ->rules('json', 'required');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('KeyValueField', $serialized['component']);
        $this->assertEquals('Meta', $serialized['name']);
        $this->assertEquals('meta', $serialized['attribute']);
        $this->assertEquals('Property', $serialized['keyLabel']);
        $this->assertEquals('Content', $serialized['valueLabel']);
        $this->assertEquals('Add new item', $serialized['actionText']);
        $this->assertEquals('Enter key-value pairs', $serialized['helpText']);
        $this->assertEquals(['json', 'required'], $serialized['rules']);
    }

    /** @test */
    public function it_handles_complete_create_update_cycle(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];

        // Simulate frontend sending key-value pairs
        $request = new Request([
            'meta' => [
                ['key' => 'name', 'value' => 'John Doe'],
                ['key' => 'email', 'value' => 'john@example.com'],
                ['key' => 'age', 'value' => '30'],
            ],
        ]);

        // Fill model from request (create operation)
        $field->fill($request, $model);

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '30',
        ], $model->meta);

        // Resolve for display/editing (read operation)
        $field->resolve($model);

        $this->assertEquals([
            ['key' => 'name', 'value' => 'John Doe'],
            ['key' => 'email', 'value' => 'john@example.com'],
            ['key' => 'age', 'value' => '30'],
        ], $field->value);

        // Update with modified data
        $updateRequest = new Request([
            'meta' => [
                ['key' => 'name', 'value' => 'Jane Doe'], // Updated
                ['key' => 'email', 'value' => 'jane@example.com'], // Updated
                ['key' => 'phone', 'value' => '+1234567890'], // Added
                // 'age' removed
            ],
        ]);

        $field->fill($updateRequest, $model);

        $this->assertEquals([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
        ], $model->meta);
    }

    /** @test */
    public function it_handles_json_database_storage_format(): void
    {
        $field = KeyValue::make('Settings');
        $model = (object) [];

        // Simulate data coming from JSON database column
        $jsonData = [
            'theme' => 'dark',
            'notifications' => 'enabled',
            'language' => 'en',
        ];

        $model->settings = $jsonData;

        // Resolve should convert to frontend format
        $field->resolve($model);

        $expected = [
            ['key' => 'theme', 'value' => 'dark'],
            ['key' => 'notifications', 'value' => 'enabled'],
            ['key' => 'language', 'value' => 'en'],
        ];

        $this->assertEquals($expected, $field->value);

        // Fill should convert back to associative array for database
        $request = new Request([
            'settings' => [
                ['key' => 'theme', 'value' => 'light'],
                ['key' => 'notifications', 'value' => 'disabled'],
                ['key' => 'timezone', 'value' => 'UTC'],
            ],
        ]);

        $field->fill($request, $model);

        $this->assertEquals([
            'theme' => 'light',
            'notifications' => 'disabled',
            'timezone' => 'UTC',
        ], $model->settings);
    }

    /** @test */
    public function it_handles_empty_and_null_values_correctly(): void
    {
        $field = KeyValue::make('Meta');

        // Test null value resolution
        $model = (object) ['meta' => null];
        $field->resolve($model);
        $this->assertEquals([], $field->value);

        // Test empty array resolution
        $model = (object) ['meta' => []];
        $field->resolve($model);
        $this->assertEquals([], $field->value);

        // Test non-array value resolution
        $model = (object) ['meta' => 'not an array'];
        $field->resolve($model);
        $this->assertEquals([], $field->value);

        // Test empty request handling
        $request = new Request([]);
        $model = (object) [];
        $field->fill($request, $model);
        $this->assertEquals([], $model->meta);
    }

    /** @test */
    public function it_filters_invalid_data_from_frontend(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];

        // Simulate frontend sending malformed data
        $request = new Request([
            'meta' => [
                ['key' => 'valid', 'value' => 'data'],
                ['key' => '', 'value' => 'empty key should be filtered'],
                ['key' => '   ', 'value' => 'whitespace key should be filtered'],
                ['value' => 'missing key should be filtered'],
                'invalid structure',
                ['key' => 'another_valid', 'value' => 'more data'],
                ['key' => 'incomplete'], // Missing value
            ],
        ]);

        $field->fill($request, $model);

        $this->assertEquals([
            'valid' => 'data',
            'another_valid' => 'more data',
        ], $model->meta);
    }

    /** @test */
    public function it_supports_custom_fill_callback(): void
    {
        $callbackExecuted = false;
        $field = KeyValue::make('Meta')->fillUsing(function ($request, $model, $attribute) use (&$callbackExecuted) {
            $callbackExecuted = true;
            $model->{$attribute} = ['custom' => 'processing'];
        });

        $model = (object) [];
        $request = new Request(['meta' => [['key' => 'test', 'value' => 'data']]]);

        $field->fill($request, $model);

        $this->assertTrue($callbackExecuted);
        $this->assertEquals(['custom' => 'processing'], $model->meta);
    }

    /** @test */
    public function it_handles_complex_values_and_special_characters(): void
    {
        $field = KeyValue::make('Config');
        $model = (object) [];

        $request = new Request([
            'config' => [
                ['key' => 'database.host', 'value' => 'localhost'],
                ['key' => 'app.name', 'value' => 'My "Awesome" App'],
                ['key' => 'json_config', 'value' => '{"nested": {"value": true}}'],
                ['key' => 'special_chars', 'value' => 'àáâãäåæçèéêë'],
                ['key' => 'unicode', 'value' => '🚀 Rocket Ship'],
                ['key' => 'multiline', 'value' => "Line 1\nLine 2\nLine 3"],
            ],
        ]);

        $field->fill($request, $model);

        $expected = [
            'database.host' => 'localhost',
            'app.name' => 'My "Awesome" App',
            'json_config' => '{"nested": {"value": true}}',
            'special_chars' => 'àáâãäåæçèéêë',
            'unicode' => '🚀 Rocket Ship',
            'multiline' => "Line 1\nLine 2\nLine 3",
        ];

        $this->assertEquals($expected, $model->config);

        // Test resolution back to frontend format
        $field->resolve($model);

        $expectedResolved = [
            ['key' => 'database.host', 'value' => 'localhost'],
            ['key' => 'app.name', 'value' => 'My "Awesome" App'],
            ['key' => 'json_config', 'value' => '{"nested": {"value": true}}'],
            ['key' => 'special_chars', 'value' => 'àáâãäåæçèéêë'],
            ['key' => 'unicode', 'value' => '🚀 Rocket Ship'],
            ['key' => 'multiline', 'value' => "Line 1\nLine 2\nLine 3"],
        ];

        $this->assertEquals($expectedResolved, $field->value);
    }

    /** @test */
    public function it_maintains_data_integrity_across_multiple_operations(): void
    {
        $field = KeyValue::make('User Preferences', 'user_preferences');
        $model = (object) [];

        // Initial data
        $initialRequest = new Request([
            'user_preferences' => [
                ['key' => 'theme', 'value' => 'dark'],
                ['key' => 'language', 'value' => 'en'],
                ['key' => 'timezone', 'value' => 'UTC'],
            ],
        ]);

        $field->fill($initialRequest, $model);

        // Verify initial fill worked
        $this->assertEquals([
            'theme' => 'dark',
            'language' => 'en',
            'timezone' => 'UTC',
        ], $model->user_preferences);

        $field->resolve($model);

        $this->assertCount(3, $field->value);

        // Update operation - modify existing and add new
        $updateRequest = new Request([
            'user_preferences' => [
                ['key' => 'theme', 'value' => 'light'], // Modified
                ['key' => 'language', 'value' => 'en'], // Unchanged
                ['key' => 'notifications', 'value' => 'enabled'], // New
                // timezone removed
            ],
        ]);

        $field->fill($updateRequest, $model);

        $this->assertEquals([
            'theme' => 'light',
            'language' => 'en',
            'notifications' => 'enabled',
        ], $model->user_preferences);

        // Resolve again to verify frontend format
        $field->resolve($model);

        $this->assertEquals([
            ['key' => 'theme', 'value' => 'light'],
            ['key' => 'language', 'value' => 'en'],
            ['key' => 'notifications', 'value' => 'enabled'],
        ], $field->value);
    }

    /** @test */
    public function it_supports_fill_attribute_from_request_method(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];

        $request = new Request([
            'meta' => [
                ['key' => 'name', 'value' => 'John Doe'],
                ['key' => 'email', 'value' => 'john@example.com'],
            ],
        ]);

        $field->fillAttributeFromRequest($request, 'meta', $model, 'meta');

        $this->assertEquals([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ], $model->meta);
    }

    /** @test */
    public function it_provides_display_methods_for_frontend(): void
    {
        $field = KeyValue::make('Meta');

        // Test with empty value
        $field->value = [];
        $this->assertEquals([], $field->getDisplayValue());
        $this->assertFalse($field->hasDisplayValues());

        // Test with data
        $field->value = [
            ['key' => 'name', 'value' => 'John Doe'],
            ['key' => 'email', 'value' => 'john@example.com'],
        ];

        $this->assertEquals([
            ['key' => 'name', 'value' => 'John Doe'],
            ['key' => 'email', 'value' => 'john@example.com'],
        ], $field->getDisplayValue());
        $this->assertTrue($field->hasDisplayValues());
    }

    /** @test */
    public function it_handles_large_datasets_efficiently(): void
    {
        $field = KeyValue::make('Large Config', 'large_config');
        $model = (object) [];

        // Generate large dataset
        $largeDataset = [];
        for ($i = 1; $i <= 100; $i++) {
            $largeDataset[] = ['key' => "config_{$i}", 'value' => "value_{$i}"];
        }

        $request = new Request(['large_config' => $largeDataset]);

        $field->fill($request, $model);

        // Verify the property exists and has correct data
        $this->assertTrue(property_exists($model, 'large_config'));
        $this->assertCount(100, $model->large_config);
        $this->assertEquals('value_1', $model->large_config['config_1']);
        $this->assertEquals('value_100', $model->large_config['config_100']);

        // Test resolution
        $field->resolve($model);

        $this->assertCount(100, $field->value);
        $this->assertEquals(['key' => 'config_1', 'value' => 'value_1'], $field->value[0]);
        $this->assertEquals(['key' => 'config_100', 'value' => 'value_100'], $field->value[99]);
    }
}
