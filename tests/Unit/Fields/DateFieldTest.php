<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Date;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Date Field Unit Tests
 *
 * Tests for Date field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class DateFieldTest extends TestCase
{
    public function test_date_field_creation(): void
    {
        $field = Date::make('Birth Date');

        $this->assertEquals('Birth Date', $field->name);
        $this->assertEquals('birth_date', $field->attribute);
        $this->assertEquals('DateField', $field->component);
    }

    public function test_date_field_with_custom_attribute(): void
    {
        $field = Date::make('Date of Birth', 'dob');

        $this->assertEquals('Date of Birth', $field->name);
        $this->assertEquals('dob', $field->attribute);
    }

    public function test_date_field_default_properties(): void
    {
        $field = Date::make('Birth Date');

        $this->assertEquals('Y-m-d', $field->displayFormat);
        $this->assertEquals('Y-m-d', $field->storageFormat);
        $this->assertNull($field->minDate);
        $this->assertNull($field->maxDate);
        $this->assertTrue($field->showPicker);
    }

    public function test_date_field_format_configuration(): void
    {
        $field = Date::make('Birth Date')->format('Y-m-d');

        $this->assertEquals('Y-m-d', $field->storageFormat);
    }

    public function test_date_field_min_date_configuration(): void
    {
        $field = Date::make('Birth Date')->min('1900-01-01');

        $this->assertEquals('1900-01-01', $field->minDate);
    }

    public function test_date_field_max_date_configuration(): void
    {
        $field = Date::make('Birth Date')->max('2023-12-31');

        $this->assertEquals('2023-12-31', $field->maxDate);
    }

    public function test_date_field_show_picker_configuration(): void
    {
        $field = Date::make('Birth Date')->showPicker(false);

        $this->assertFalse($field->showPicker);
    }

    public function test_date_field_show_picker_default(): void
    {
        $field = Date::make('Birth Date');

        $this->assertTrue($field->showPicker);
    }

    public function test_date_field_fill_parses_date(): void
    {
        $field = Date::make('Birth Date');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['birth_date' => '1990-05-15']);

        $field->fill($request, $model);

        $this->assertEquals('1990-05-15', $model->birth_date);
    }

    public function test_date_field_fill_handles_empty_value(): void
    {
        $field = Date::make('Birth Date');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['birth_date' => '']);

        $field->fill($request, $model);

        $this->assertNull($model->birth_date);
    }

    public function test_date_field_fill_with_different_formats(): void
    {
        $field = Date::make('Birth Date')->format('Y-m-d');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['birth_date' => '1990-05-15']);

        $field->fill($request, $model);

        $this->assertEquals('1990-05-15', $model->birth_date);
    }

    public function test_date_field_fill_with_callback(): void
    {
        $field = Date::make('Birth Date')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = '2000-01-01';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['birth_date' => '1990-05-15']);

        $field->fill($request, $model);

        $this->assertEquals('2000-01-01', $model->birth_date);
    }

    public function test_date_field_resolve_preserves_value(): void
    {
        $field = Date::make('Birth Date');
        $resource = (object) ['birth_date' => '1990-05-15'];

        $field->resolve($resource);

        $this->assertEquals('1990-05-15', $field->value);
    }

    public function test_date_field_resolve_handles_carbon_instance(): void
    {
        $field = Date::make('Birth Date');
        $carbonDate = \Carbon\Carbon::parse('1990-05-15');
        $resource = (object) ['birth_date' => $carbonDate];

        $field->resolve($resource);

        $this->assertEquals('1990-05-15', $field->value);
    }

    public function test_date_field_resolve_handles_invalid_date(): void
    {
        $field = Date::make('Birth Date');
        $resource = (object) ['birth_date' => 'invalid-date'];

        $field->resolve($resource);

        $this->assertEquals('invalid-date', $field->value);
    }

    public function test_date_field_resolve_handles_null_value(): void
    {
        $field = Date::make('Birth Date');
        $resource = (object) ['birth_date' => null];

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    public function test_date_field_meta_includes_all_properties(): void
    {
        $field = Date::make('Birth Date')
            ->format('Y-m-d')
            ->min('1900-01-01')
            ->max('2023-12-31')
            ->showPicker(false);

        $meta = $field->meta();

        $this->assertArrayHasKey('storageFormat', $meta);
        $this->assertArrayHasKey('minDate', $meta);
        $this->assertArrayHasKey('maxDate', $meta);
        $this->assertArrayHasKey('showPicker', $meta);
        $this->assertEquals('Y-m-d', $meta['storageFormat']);
        $this->assertEquals('1900-01-01', $meta['minDate']);
        $this->assertEquals('2023-12-31', $meta['maxDate']);
        $this->assertFalse($meta['showPicker']);
    }

    public function test_date_field_json_serialization(): void
    {
        $field = Date::make('Event Date')
            ->format('Y-m-d')
            ->min('2023-01-01')
            ->max('2023-12-31')
            ->required()
            ->help('Select event date');

        $json = $field->jsonSerialize();

        $this->assertEquals('Event Date', $json['name']);
        $this->assertEquals('event_date', $json['attribute']);
        $this->assertEquals('DateField', $json['component']);
        $this->assertEquals('Y-m-d', $json['storageFormat']);
        $this->assertEquals('2023-01-01', $json['minDate']);
        $this->assertEquals('2023-12-31', $json['maxDate']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Select event date', $json['helpText']);
    }

    public function test_date_field_with_validation_rules(): void
    {
        $field = Date::make('Birth Date')
            ->rules('required', 'date', 'before:today');

        $this->assertEquals(['required', 'date', 'before:today'], $field->rules);
    }

    public function test_date_field_with_nullable(): void
    {
        $field = Date::make('Birth Date')->nullable();

        $this->assertTrue($field->nullable);
    }

    public function test_date_field_with_readonly(): void
    {
        $field = Date::make('Birth Date')->readonly();

        $this->assertTrue($field->readonly);
    }

    public function test_date_field_storage_format_configuration(): void
    {
        $field = Date::make('Birth Date')->storageFormat('Y/m/d');

        $this->assertEquals('Y/m/d', $field->storageFormat);
    }

    public function test_date_field_storage_format_method_chaining(): void
    {
        $field = Date::make('Birth Date')
            ->format('d/m/Y')
            ->storageFormat('Y-m-d')
            ->min('1900-01-01')
            ->max('2023-12-31');

        $this->assertEquals('d/m/Y', $field->displayFormat);
        $this->assertEquals('Y-m-d', $field->storageFormat);
        $this->assertEquals('1900-01-01', $field->minDate);
        $this->assertEquals('2023-12-31', $field->maxDate);
    }

    public function test_date_field_resolve_with_carbon_object(): void
    {
        $field = Date::make('Birth Date')->format('Y-m-d');
        $carbon = \Carbon\Carbon::create(1990, 5, 15);
        $resource = (object) ['birth_date' => $carbon];

        $field->resolve($resource);

        $this->assertEquals('1990-05-15', $field->value);
    }

    public function test_date_field_resolve_with_string_date(): void
    {
        $field = Date::make('Birth Date')->format('d/m/Y');
        $resource = (object) ['birth_date' => '1990-05-15'];

        $field->resolve($resource);

        // String values are not reformatted by the resolve method
        // Only non-string values (like Carbon objects) are formatted
        $this->assertEquals('1990-05-15', $field->value);
    }

    public function test_date_field_resolve_with_invalid_date(): void
    {
        $field = Date::make('Birth Date')->format('Y-m-d');
        $resource = (object) ['birth_date' => 'invalid-date'];

        $field->resolve($resource);

        // Should keep original value when parsing fails
        $this->assertEquals('invalid-date', $field->value);
    }

    public function test_date_field_resolve_with_null_value(): void
    {
        $field = Date::make('Birth Date');
        $resource = (object) ['birth_date' => null];

        $field->resolve($resource);

        $this->assertNull($field->value);
    }

    public function test_date_field_resolve_with_already_string_value(): void
    {
        $field = Date::make('Birth Date')->format('Y-m-d');
        $resource = (object) ['birth_date' => '1990-05-15'];

        $field->resolve($resource);

        // Should format the string date
        $this->assertEquals('1990-05-15', $field->value);
    }

    public function test_date_field_meta_comprehensive_coverage(): void
    {
        $field = Date::make('Birth Date')
            ->format('d/m/Y')
            ->storageFormat('Y-m-d')
            ->min('1900-01-01')
            ->max('2023-12-31')
            ->showPicker(false)
            ->pickerFormat('d-m-Y')
            ->pickerDisplayFormat('DD-MM-YYYY')
            ->firstDayOfWeek(1);

        $meta = $field->meta();

        $this->assertArrayHasKey('displayFormat', $meta);
        $this->assertArrayHasKey('storageFormat', $meta);
        $this->assertArrayHasKey('minDate', $meta);
        $this->assertArrayHasKey('maxDate', $meta);
        $this->assertArrayHasKey('showPicker', $meta);
        $this->assertArrayHasKey('pickerFormat', $meta);
        $this->assertArrayHasKey('pickerDisplayFormat', $meta);
        $this->assertArrayHasKey('firstDayOfWeek', $meta);

        $this->assertEquals('d/m/Y', $meta['displayFormat']);
        $this->assertEquals('Y-m-d', $meta['storageFormat']);
        $this->assertEquals('1900-01-01', $meta['minDate']);
        $this->assertEquals('2023-12-31', $meta['maxDate']);
        $this->assertFalse($meta['showPicker']);
        $this->assertEquals('d-m-Y', $meta['pickerFormat']);
        $this->assertEquals('DD-MM-YYYY', $meta['pickerDisplayFormat']);
        $this->assertEquals(1, $meta['firstDayOfWeek']);
    }

    public function test_date_field_meta_with_default_values(): void
    {
        $field = Date::make('Birth Date');

        $meta = $field->meta();

        $this->assertEquals('Y-m-d', $meta['displayFormat']);
        $this->assertEquals('Y-m-d', $meta['storageFormat']);
        $this->assertNull($meta['minDate']);
        $this->assertNull($meta['maxDate']);
        $this->assertTrue($meta['showPicker']);
        $this->assertNull($meta['pickerFormat']);
        $this->assertNull($meta['pickerDisplayFormat']);
        $this->assertEquals(0, $meta['firstDayOfWeek']);
    }

    public function test_date_field_fill_with_different_storage_format(): void
    {
        $field = Date::make('Birth Date')
            ->format('Y-m-d')
            ->storageFormat('Y/m/d');

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['birth_date' => '1990-05-15']);

        $field->fill($request, $model);

        $this->assertEquals('1990/05/15', $model->birth_date);
    }

    public function test_date_field_fill_with_invalid_date_format(): void
    {
        $field = Date::make('Birth Date')->format('Y-m-d');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['birth_date' => 'invalid-date']);

        $field->fill($request, $model);

        // Should store as-is when parsing fails
        $this->assertEquals('invalid-date', $model->birth_date);
    }

    public function test_date_field_picker_format_configuration(): void
    {
        $field = Date::make('Birth Date')->pickerFormat('d-m-Y');

        $this->assertEquals('d-m-Y', $field->pickerFormat);
    }

    public function test_date_field_picker_display_format_configuration(): void
    {
        $field = Date::make('Birth Date')->pickerDisplayFormat('DD-MM-YYYY');

        $this->assertEquals('DD-MM-YYYY', $field->pickerDisplayFormat);
    }

    public function test_date_field_first_day_of_week_configuration(): void
    {
        $field = Date::make('Birth Date')->firstDayOfWeek(1);

        $this->assertEquals(1, $field->firstDayOfWeek);
    }

    public function test_date_field_first_day_of_week_default(): void
    {
        $field = Date::make('Birth Date');

        $this->assertEquals(0, $field->firstDayOfWeek);
    }

    public function test_date_field_nova_api_method_chaining(): void
    {
        $field = Date::make('Birth Date')
            ->pickerFormat('d-m-Y')
            ->pickerDisplayFormat('DD-MM-YYYY')
            ->firstDayOfWeek(1);

        $this->assertEquals('d-m-Y', $field->pickerFormat);
        $this->assertEquals('DD-MM-YYYY', $field->pickerDisplayFormat);
        $this->assertEquals(1, $field->firstDayOfWeek);
    }

    public function test_date_field_comprehensive_method_coverage(): void
    {
        $field = Date::make('Birth Date');

        // Test that all public methods exist and can be called
        $this->assertTrue(method_exists($field, 'format'));
        $this->assertTrue(method_exists($field, 'storageFormat'));
        $this->assertTrue(method_exists($field, 'min'));
        $this->assertTrue(method_exists($field, 'max'));
        $this->assertTrue(method_exists($field, 'showPicker'));
        $this->assertTrue(method_exists($field, 'pickerFormat'));
        $this->assertTrue(method_exists($field, 'pickerDisplayFormat'));
        $this->assertTrue(method_exists($field, 'firstDayOfWeek'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'fill'));
        $this->assertTrue(method_exists($field, 'meta'));

        // Test method chaining
        $result = $field->format('d/m/Y')
                       ->storageFormat('Y-m-d')
                       ->min('1900-01-01')
                       ->max('2023-12-31')
                       ->showPicker(false)
                       ->pickerFormat('d-m-Y')
                       ->pickerDisplayFormat('DD-MM-YYYY')
                       ->firstDayOfWeek(1);

        $this->assertInstanceOf(Date::class, $result);
        $this->assertEquals('d/m/Y', $field->displayFormat);
        $this->assertEquals('Y-m-d', $field->storageFormat);
        $this->assertEquals('1900-01-01', $field->minDate);
        $this->assertEquals('2023-12-31', $field->maxDate);
        $this->assertFalse($field->showPicker);
        $this->assertEquals('d-m-Y', $field->pickerFormat);
        $this->assertEquals('DD-MM-YYYY', $field->pickerDisplayFormat);
        $this->assertEquals(1, $field->firstDayOfWeek);
    }
}
