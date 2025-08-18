<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use DateTimeZone;
use JTD\AdminPanel\Fields\Timezone;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Timezone Field Unit Tests
 *
 * Tests for Timezone field class following Nova API compatibility.
 * Tests basic field creation, timezone options, and meta data.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TimezoneFieldTest extends TestCase
{
    public function test_timezone_field_creation(): void
    {
        $field = Timezone::make('Timezone');

        $this->assertEquals('Timezone', $field->name);
        $this->assertEquals('timezone', $field->attribute);
        $this->assertEquals('TimezoneField', $field->component);
    }

    public function test_timezone_field_with_custom_attribute(): void
    {
        $field = Timezone::make('User Timezone', 'user_timezone');

        $this->assertEquals('User Timezone', $field->name);
        $this->assertEquals('user_timezone', $field->attribute);
    }

    public function test_timezone_field_meta_contains_options(): void
    {
        $field = Timezone::make('Timezone');

        $meta = $field->meta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('options', $meta);
        $this->assertIsArray($meta['options']);
        $this->assertNotEmpty($meta['options']);
    }

    public function test_timezone_field_options_include_all_php_timezones(): void
    {
        $field = Timezone::make('Timezone');

        $meta = $field->meta();
        $options = $meta['options'];
        $phpTimezones = DateTimeZone::listIdentifiers();

        // Should include all PHP timezones
        foreach ($phpTimezones as $timezone) {
            $this->assertArrayHasKey($timezone, $options);
            $this->assertEquals($timezone, $options[$timezone]);
        }

        // Should have same count as PHP timezones
        $this->assertCount(count($phpTimezones), $options);
    }

    public function test_timezone_field_options_are_sorted(): void
    {
        $field = Timezone::make('Timezone');

        $meta = $field->meta();
        $options = $meta['options'];

        $sortedOptions = $options;
        asort($sortedOptions);

        $this->assertEquals($sortedOptions, $options);
    }

    public function test_timezone_field_options_contain_common_timezones(): void
    {
        $field = Timezone::make('Timezone');

        $meta = $field->meta();
        $options = $meta['options'];

        // Should contain common timezones
        $this->assertArrayHasKey('America/New_York', $options);
        $this->assertArrayHasKey('Europe/London', $options);
        $this->assertArrayHasKey('Asia/Tokyo', $options);
        $this->assertArrayHasKey('UTC', $options);
        $this->assertArrayHasKey('Australia/Sydney', $options);
    }

    public function test_timezone_field_options_values_equal_keys(): void
    {
        $field = Timezone::make('Timezone');

        $meta = $field->meta();
        $options = $meta['options'];

        // In Nova's simple API, timezone values should equal their keys
        foreach ($options as $key => $value) {
            $this->assertEquals($key, $value);
        }
    }

    public function test_timezone_field_fill_validates_timezone(): void
    {
        $field = Timezone::make('Timezone');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['timezone' => 'America/New_York']);

        $field->fill($request, $model);

        $this->assertEquals('America/New_York', $model->timezone);
    }

    public function test_timezone_field_fill_handles_empty_value(): void
    {
        $field = Timezone::make('Timezone');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['timezone' => '']);

        $field->fill($request, $model);

        $this->assertEquals('', $model->timezone);
    }

    public function test_timezone_field_fill_with_callback(): void
    {
        $field = Timezone::make('Timezone')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'UTC';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['timezone' => 'America/New_York']);

        $field->fill($request, $model);

        $this->assertEquals('UTC', $model->timezone);
    }

    public function test_timezone_field_inheritance_from_field(): void
    {
        $field = Timezone::make('Timezone');

        // Test that Timezone field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_timezone_field_json_serialization(): void
    {
        $field = Timezone::make('User Timezone')
            ->required()
            ->help('Select your timezone');

        $json = $field->jsonSerialize();

        $this->assertEquals('User Timezone', $json['name']);
        $this->assertEquals('user_timezone', $json['attribute']);
        $this->assertEquals('TimezoneField', $json['component']);
        $this->assertArrayHasKey('options', $json);
        $this->assertIsArray($json['options']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select your timezone', $json['helpText']);
    }

    public function test_timezone_field_with_validation_rules(): void
    {
        $field = Timezone::make('Timezone')
            ->rules('timezone');

        $this->assertEquals(['timezone'], $field->rules);
    }

    public function test_timezone_field_resolve_preserves_value(): void
    {
        $field = Timezone::make('Timezone');
        $resource = (object) ['timezone' => 'America/Los_Angeles'];

        $field->resolve($resource);

        $this->assertEquals('America/Los_Angeles', $field->value);
    }

    public function test_timezone_field_comprehensive_coverage(): void
    {
        $field = Timezone::make('Timezone');

        // Test all public methods exist and work
        $this->assertTrue(method_exists($field, 'meta'));

        $meta = $field->meta();
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('options', $meta);

        // Test method chaining with base Field methods
        $chainedField = Timezone::make('Timezone')
            ->required()
            ->help('Select timezone')
            ->placeholder('Choose timezone...');

        $this->assertContains('required', $chainedField->rules);
        $this->assertEquals('Select timezone', $chainedField->helpText);
        $this->assertEquals('Choose timezone...', $chainedField->placeholder);
    }
}
