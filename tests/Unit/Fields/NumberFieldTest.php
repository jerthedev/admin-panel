<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Number Field Unit Tests.
 *
 * Comprehensive tests for Number field class ensuring 100% Nova API compatibility.
 * Tests all Nova Number field features: make(), min(), max(), step(), and fill().
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

    public function test_make_creates_field_with_name_and_attribute(): void
    {
        $field = Number::make('Age');

        $this->assertEquals('Age', $field->name);
        $this->assertEquals('age', $field->attribute);
    }

    public function test_make_creates_field_with_custom_attribute(): void
    {
        $field = Number::make('User Age', 'user_age');

        $this->assertEquals('User Age', $field->name);
        $this->assertEquals('user_age', $field->attribute);
    }

    public function test_make_creates_field_with_resolve_callback(): void
    {
        $callback = fn ($resource, $attribute) => $resource->$attribute * 2;
        $field = Number::make('Double Age', 'age', $callback);

        $this->assertEquals('Double Age', $field->name);
        $this->assertEquals('age', $field->attribute);
        $this->assertEquals($callback, $field->resolveCallback);
    }

    public function test_min_sets_minimum_value(): void
    {
        $field = Number::make('Age')->min(18);

        $this->assertEquals(18, $field->min);
    }

    public function test_min_returns_field_instance_for_chaining(): void
    {
        $field = Number::make('Age');
        $result = $field->min(18);

        $this->assertSame($field, $result);
    }

    public function test_max_sets_maximum_value(): void
    {
        $field = Number::make('Age')->max(120);

        $this->assertEquals(120, $field->max);
    }

    public function test_max_returns_field_instance_for_chaining(): void
    {
        $field = Number::make('Age');
        $result = $field->max(120);

        $this->assertSame($field, $result);
    }

    public function test_step_sets_step_value(): void
    {
        $field = Number::make('Price')->step(0.01);

        $this->assertEquals(0.01, $field->step);
    }

    public function test_step_returns_field_instance_for_chaining(): void
    {
        $field = Number::make('Price');
        $result = $field->step(0.01);

        $this->assertSame($field, $result);
    }

    public function test_method_chaining_works_correctly(): void
    {
        $field = Number::make('Price')
            ->min(0)
            ->max(1000)
            ->step(0.01);

        $this->assertEquals(0, $field->min);
        $this->assertEquals(1000, $field->max);
        $this->assertEquals(0.01, $field->step);
    }

    public function test_fill_with_integer_value(): void
    {
        $field = Number::make('Age');
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['age' => '25']);

        $field->fill($request, $model);

        $this->assertEquals(25, $model->age);
        $this->assertIsInt($model->age);
    }

    public function test_fill_with_float_value_when_step_is_decimal(): void
    {
        $field = Number::make('Price')->step(0.01);
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['price' => '19.99']);

        $field->fill($request, $model);

        $this->assertEquals(19.99, $model->price);
        $this->assertIsFloat($model->price);
    }

    public function test_fill_with_integer_value_when_step_is_integer(): void
    {
        $field = Number::make('Quantity')->step(1);
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['quantity' => '5']);

        $field->fill($request, $model);

        $this->assertEquals(5, $model->quantity);
        $this->assertIsInt($model->quantity);
    }

    public function test_fill_with_null_value(): void
    {
        $field = Number::make('Age');
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['age' => null]);

        $field->fill($request, $model);

        $this->assertNull($model->age);
    }

    public function test_fill_with_empty_string(): void
    {
        $field = Number::make('Age');
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['age' => '']);

        $field->fill($request, $model);

        $this->assertNull($model->age);
    }

    public function test_fill_with_missing_attribute(): void
    {
        $field = Number::make('Age');
        $model = new \stdClass;
        $request = Request::create('/', 'POST', []);

        $field->fill($request, $model);

        $this->assertFalse(property_exists($model, 'age'));
    }

    public function test_fill_with_custom_fill_callback(): void
    {
        $field = Number::make('Age');
        $field->fillCallback = function ($request, $model, $attribute) {
            $model->$attribute = $request->input($attribute) * 2;
        };

        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['age' => '25']);

        $field->fill($request, $model);

        $this->assertEquals(50, $model->age);
    }

    public function test_decimal_step_detection(): void
    {
        $field = Number::make('Price')->step(0.5);
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['price' => '10']);

        $field->fill($request, $model);

        $this->assertEquals(10.0, $model->price);
        $this->assertIsFloat($model->price);
    }

    public function test_integer_step_detection(): void
    {
        $field = Number::make('Count')->step(2);
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['count' => '10']);

        $field->fill($request, $model);

        $this->assertEquals(10, $model->count);
        $this->assertIsInt($model->count);
    }

    public function test_no_step_defaults_to_integer(): void
    {
        $field = Number::make('Age');
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['age' => '25']);

        $field->fill($request, $model);

        $this->assertEquals(25, $model->age);
        $this->assertIsInt($model->age);
    }

    public function test_negative_values_are_handled_correctly(): void
    {
        $field = Number::make('Temperature')->min(-100);
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['temperature' => '-15']);

        $field->fill($request, $model);

        $this->assertEquals(-15, $model->temperature);
    }

    public function test_zero_value_is_handled_correctly(): void
    {
        $field = Number::make('Count');
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['count' => '0']);

        $field->fill($request, $model);

        $this->assertEquals(0, $model->count);
    }

    public function test_large_numbers_are_handled_correctly(): void
    {
        $field = Number::make('Population');
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['population' => '1000000']);

        $field->fill($request, $model);

        $this->assertEquals(1000000, $model->population);
    }

    public function test_very_small_decimal_step(): void
    {
        $field = Number::make('Precision')->step(0.001);
        $model = new \stdClass;
        $request = Request::create('/', 'POST', ['precision' => '1.234']);

        $field->fill($request, $model);

        $this->assertEquals(1.234, $model->precision);
        $this->assertIsFloat($model->precision);
    }
}
