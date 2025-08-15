<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Number Field Unit Tests
 *
 * Tests for Number field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class NumberFieldTest extends TestCase
{
    public function test_number_field_component(): void
    {
        $field = Number::make('Age');

        $this->assertEquals('NumberField', $field->component);
    }

    public function test_number_field_configuration(): void
    {
        $field = Number::make('Price')
            ->min(0)
            ->max(1000)
            ->step(0.01);

        $this->assertEquals(0, $field->min);
        $this->assertEquals(1000, $field->max);
        $this->assertEquals(0.01, $field->step);
    }

    public function test_number_field_creation(): void
    {
        $field = Number::make('Age');

        $this->assertEquals('Age', $field->name);
        $this->assertEquals('age', $field->attribute);
        $this->assertEquals('NumberField', $field->component);
    }

    public function test_number_field_with_custom_attribute(): void
    {
        $field = Number::make('User Age', 'user_age');

        $this->assertEquals('User Age', $field->name);
        $this->assertEquals('user_age', $field->attribute);
    }

    public function test_number_field_min_configuration(): void
    {
        $field = Number::make('Age')->min(18);

        $this->assertEquals(18, $field->min);
    }

    public function test_number_field_max_configuration(): void
    {
        $field = Number::make('Age')->max(100);

        $this->assertEquals(100, $field->max);
    }

    public function test_number_field_step_configuration(): void
    {
        $field = Number::make('Price')->step(0.5);

        $this->assertEquals(0.5, $field->step);
    }

    public function test_number_field_placeholder_configuration(): void
    {
        $field = Number::make('Age')->placeholder('Enter your age');

        $this->assertEquals('Enter your age', $field->placeholder);
    }

    public function test_number_field_fill_converts_to_numeric(): void
    {
        $field = Number::make('Age');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['age' => '25']);

        $field->fill($request, $model);

        $this->assertEquals(25, $model->age);
        $this->assertIsInt($model->age);
    }

    public function test_number_field_fill_handles_float(): void
    {
        $field = Number::make('Price');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['price' => '19.99']);

        $field->fill($request, $model);

        $this->assertEquals(19, $model->price); // Number field converts to int by default
        $this->assertIsInt($model->price);
    }

    public function test_number_field_fill_handles_empty_value(): void
    {
        $field = Number::make('Age');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['age' => '']);

        $field->fill($request, $model);

        $this->assertEquals('', $model->age); // Empty string is preserved
    }

    public function test_number_field_fill_with_callback(): void
    {
        $field = Number::make('Age')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 999;
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['age' => '25']);

        $field->fill($request, $model);

        $this->assertEquals(999, $model->age);
    }

    public function test_number_field_meta_includes_all_properties(): void
    {
        $field = Number::make('Price')
            ->min(0)
            ->max(1000)
            ->step(0.01)
            ->placeholder('Enter price');

        $meta = $field->meta();

        $this->assertArrayHasKey('min', $meta);
        $this->assertArrayHasKey('max', $meta);
        $this->assertArrayHasKey('step', $meta);
        $this->assertEquals(0, $meta['min']);
        $this->assertEquals(1000, $meta['max']);
        $this->assertEquals(0.01, $meta['step']);
    }

    public function test_number_field_show_buttons_configuration(): void
    {
        $field = Number::make('Age')->showButtons(false);

        $this->assertFalse($field->showButtons);
    }

    public function test_number_field_show_buttons_default(): void
    {
        $field = Number::make('Age')->showButtons();

        $this->assertTrue($field->showButtons);
    }

    public function test_number_field_decimals_configuration(): void
    {
        $field = Number::make('Price')->decimals(2);

        $this->assertEquals(2, $field->decimals);
    }

    public function test_number_field_fill_with_decimal_step(): void
    {
        $field = Number::make('Price')->step(0.01);
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['price' => '19.99']);

        $field->fill($request, $model);

        $this->assertEquals(19.99, $model->price);
        $this->assertIsFloat($model->price);
    }

    public function test_number_field_meta_includes_show_buttons_and_decimals(): void
    {
        $field = Number::make('Price')
            ->min(0)
            ->max(1000)
            ->step(0.01)
            ->showButtons(false)
            ->decimals(2);

        $meta = $field->meta();

        $this->assertArrayHasKey('showButtons', $meta);
        $this->assertArrayHasKey('decimals', $meta);
        $this->assertFalse($meta['showButtons']);
        $this->assertEquals(2, $meta['decimals']);
    }

    public function test_number_field_json_serialization(): void
    {
        $field = Number::make('Quantity')
            ->min(1)
            ->max(100)
            ->step(1)
            ->required()
            ->help('Enter quantity');

        $json = $field->jsonSerialize();

        $this->assertEquals('Quantity', $json['name']);
        $this->assertEquals('quantity', $json['attribute']);
        $this->assertEquals('NumberField', $json['component']);
        $this->assertEquals(1, $json['min']);
        $this->assertEquals(100, $json['max']);
        $this->assertEquals(1, $json['step']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Enter quantity', $json['helpText']);
    }
}
