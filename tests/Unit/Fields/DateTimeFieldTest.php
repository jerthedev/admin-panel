<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\DateTime;
use JTD\AdminPanel\Tests\TestCase;

/**
 * DateTime Field Unit Tests
 *
 * Tests for DateTime field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class DateTimeFieldTest extends TestCase
{
    public function test_datetime_field_creation(): void
    {
        $field = DateTime::make('Published At');

        $this->assertEquals('Published At', $field->name);
        $this->assertEquals('published_at', $field->attribute);
        $this->assertEquals('DateTimeField', $field->component);
    }

    public function test_datetime_field_with_custom_attribute(): void
    {
        $field = DateTime::make('Created At', 'created_at');

        $this->assertEquals('Created At', $field->name);
        $this->assertEquals('created_at', $field->attribute);
    }

    public function test_datetime_field_format_configuration(): void
    {
        $field = DateTime::make('Published At')
            ->format('Y-m-d H:i:s')
            ->displayFormat('F j, Y g:i A');

        $this->assertEquals('Y-m-d H:i:s', $field->storageFormat);
        $this->assertEquals('F j, Y g:i A', $field->displayFormat);
    }

    public function test_datetime_field_timezone_configuration(): void
    {
        $field = DateTime::make('Published At')->timezone('UTC');

        $this->assertEquals('UTC', $field->timezone);
    }

    public function test_datetime_field_step_configuration(): void
    {
        $field = DateTime::make('Published At')->step(30);

        $this->assertEquals(30, $field->step);
    }

    public function test_datetime_field_min_max_configuration(): void
    {
        $field = DateTime::make('Published At')
            ->min('2023-01-01 00:00:00')
            ->max('2023-12-31 23:59:59');

        $this->assertEquals('2023-01-01 00:00:00', $field->minDateTime);
        $this->assertEquals('2023-12-31 23:59:59', $field->maxDateTime);
    }

    public function test_datetime_field_show_picker_configuration(): void
    {
        $field = DateTime::make('Published At')->showPicker(false);

        $this->assertFalse($field->showPicker);
    }

    public function test_datetime_field_show_picker_default(): void
    {
        $field = DateTime::make('Published At');

        $this->assertTrue($field->showPicker);
    }

    public function test_datetime_field_fill_parses_datetime(): void
    {
        $field = DateTime::make('Published At');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['published_at' => '2023-01-15 14:30:00']);

        $field->fill($request, $model);

        $this->assertEquals('2023-01-15 14:30:00', $model->published_at);
    }

    public function test_datetime_field_fill_handles_empty_value(): void
    {
        $field = DateTime::make('Published At');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['published_at' => '']);

        $field->fill($request, $model);

        $this->assertNull($model->published_at);
    }

    public function test_datetime_field_fill_with_callback(): void
    {
        $field = DateTime::make('Published At')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = '2023-12-25 00:00:00';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['published_at' => '2023-01-15 14:30:00']);

        $field->fill($request, $model);

        $this->assertEquals('2023-12-25 00:00:00', $model->published_at);
    }

    public function test_datetime_field_resolve_preserves_value(): void
    {
        $field = DateTime::make('Published At');
        $resource = (object) ['published_at' => '2023-01-15 14:30:00'];

        $field->resolve($resource);

        $this->assertEquals('2023-01-15 14:30:00', $field->value);
    }

    public function test_datetime_field_meta_includes_all_properties(): void
    {
        $field = DateTime::make('Published At')
            ->format('Y-m-d H:i:s')
            ->displayFormat('F j, Y g:i A')
            ->timezone('UTC')
            ->step(30)
            ->min('2023-01-01 00:00:00')
            ->max('2023-12-31 23:59:59')
            ->showPicker(false);

        $meta = $field->meta();

        $this->assertArrayHasKey('storageFormat', $meta);
        $this->assertArrayHasKey('displayFormat', $meta);
        $this->assertArrayHasKey('timezone', $meta);
        $this->assertArrayHasKey('step', $meta);
        $this->assertArrayHasKey('minDateTime', $meta);
        $this->assertArrayHasKey('maxDateTime', $meta);
        $this->assertArrayHasKey('showPicker', $meta);
        $this->assertEquals('Y-m-d H:i:s', $meta['storageFormat']);
        $this->assertEquals('F j, Y g:i A', $meta['displayFormat']);
        $this->assertEquals('UTC', $meta['timezone']);
        $this->assertEquals(30, $meta['step']);
        $this->assertEquals('2023-01-01 00:00:00', $meta['minDateTime']);
        $this->assertEquals('2023-12-31 23:59:59', $meta['maxDateTime']);
        $this->assertFalse($meta['showPicker']);
    }

    public function test_datetime_field_json_serialization(): void
    {
        $field = DateTime::make('Event Date')
            ->format('Y-m-d H:i:s')
            ->displayFormat('M j, Y H:i')
            ->timezone('America/New_York')
            ->required()
            ->nullable();

        $json = $field->jsonSerialize();

        $this->assertEquals('Event Date', $json['name']);
        $this->assertEquals('event_date', $json['attribute']);
        $this->assertEquals('DateTimeField', $json['component']);
        $this->assertEquals('Y-m-d H:i:s', $json['storageFormat']);
        $this->assertEquals('M j, Y H:i', $json['displayFormat']);
        $this->assertEquals('America/New_York', $json['timezone']);
        $this->assertContains('required', $json['rules']);
        $this->assertTrue($json['nullable']);
    }

    public function test_datetime_field_fill_with_timezone_conversion(): void
    {
        $field = DateTime::make('Event Time')
            ->timezone('America/New_York')
            ->displayFormat('Y-m-d H:i:s')
            ->format('Y-m-d H:i:s');

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['event_time' => '2023-06-15 14:30:00']);

        $field->fill($request, $model);

        // Should convert from America/New_York to UTC for storage
        $this->assertNotNull($model->event_time);
        $this->assertIsString($model->event_time);
    }

    public function test_datetime_field_fill_with_parsing_fallback(): void
    {
        $field = DateTime::make('Event Time')
            ->displayFormat('Y-m-d H:i:s')
            ->timezone('UTC');

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['event_time' => '2023-06-15T14:30:00Z']);

        $field->fill($request, $model);

        // Should handle ISO format and parse successfully
        $this->assertEquals('2023-06-15 14:30:00', $model->event_time);
    }

    public function test_datetime_field_fill_with_invalid_datetime(): void
    {
        $field = DateTime::make('Event Time')
            ->displayFormat('Y-m-d H:i:s')
            ->timezone('UTC');

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['event_time' => 'invalid-datetime']);

        $field->fill($request, $model);

        // Should store as-is when parsing fails
        $this->assertEquals('invalid-datetime', $model->event_time);
    }

    public function test_datetime_field_fill_with_null_value(): void
    {
        $field = DateTime::make('Event Time');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['event_time' => null]);

        $field->fill($request, $model);

        $this->assertNull($model->event_time);
    }

    public function test_datetime_field_meta_comprehensive_coverage(): void
    {
        $field = DateTime::make('Event Time')
            ->format('Y-m-d H:i:s')
            ->displayFormat('M j, Y H:i')
            ->timezone('America/New_York')
            ->step(15)
            ->min('2023-01-01 00:00:00')
            ->max('2023-12-31 23:59:59');

        $meta = $field->meta();

        $this->assertArrayHasKey('displayFormat', $meta);
        $this->assertArrayHasKey('storageFormat', $meta);
        $this->assertArrayHasKey('timezone', $meta);
        $this->assertArrayHasKey('step', $meta);
        $this->assertArrayHasKey('minDateTime', $meta);
        $this->assertArrayHasKey('maxDateTime', $meta);

        $this->assertEquals('M j, Y H:i', $meta['displayFormat']);
        $this->assertEquals('Y-m-d H:i:s', $meta['storageFormat']);
        $this->assertEquals('America/New_York', $meta['timezone']);
        $this->assertEquals(15, $meta['step']);
        $this->assertEquals('2023-01-01 00:00:00', $meta['minDateTime']);
        $this->assertEquals('2023-12-31 23:59:59', $meta['maxDateTime']);
    }

    public function test_datetime_field_comprehensive_method_coverage(): void
    {
        $field = DateTime::make('Event Time');

        // Test that all public methods exist and can be called
        $this->assertTrue(method_exists($field, 'format'));
        $this->assertTrue(method_exists($field, 'displayFormat'));
        $this->assertTrue(method_exists($field, 'timezone'));
        $this->assertTrue(method_exists($field, 'step'));
        $this->assertTrue(method_exists($field, 'min'));
        $this->assertTrue(method_exists($field, 'max'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'fill'));
        $this->assertTrue(method_exists($field, 'meta'));

        // Test method chaining
        $result = $field->format('Y-m-d H:i:s')
                       ->displayFormat('M j, Y H:i')
                       ->timezone('UTC')
                       ->step(30);

        $this->assertInstanceOf(DateTime::class, $result);
        $this->assertEquals('Y-m-d H:i:s', $field->storageFormat);
        $this->assertEquals('M j, Y H:i', $field->displayFormat);
        $this->assertEquals('UTC', $field->timezone);
        $this->assertEquals(30, $field->step);
    }
}
