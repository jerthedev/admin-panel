<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Tests\TestCase;

class NumberFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_number_field_with_nova_syntax(): void
    {
        $field = Number::make('Age');

        $this->assertEquals('Age', $field->name);
        $this->assertEquals('age', $field->attribute);
        $this->assertEquals('NumberField', $field->component);
    }

    /** @test */
    public function it_creates_number_field_with_custom_attribute(): void
    {
        $field = Number::make('User Age', 'user_age');

        $this->assertEquals('User Age', $field->name);
        $this->assertEquals('user_age', $field->attribute);
        $this->assertEquals('NumberField', $field->component);
    }

    /** @test */
    public function it_resolves_and_fills_integer_values(): void
    {
        $user = new \stdClass;
        $user->age = 25;

        $field = Number::make('Age', 'age');
        $field->resolve($user);
        $this->assertEquals(25, $field->value);

        $request = new Request(['age' => '30']);
        $field->fill($request, $user);
        $this->assertEquals(30, $user->age);
        $this->assertIsInt($user->age);
    }

    /** @test */
    public function it_resolves_and_fills_float_values_with_decimal_step(): void
    {
        $user = new \stdClass;
        $user->rating = 4.5;

        $field = Number::make('Rating', 'rating')->step(0.1);
        $field->resolve($user);
        $this->assertEquals(4.5, $field->value);

        $request = new Request(['rating' => '3.8']);
        $field->fill($request, $user);
        $this->assertEquals(3.8, $user->rating);
        $this->assertIsFloat($user->rating);
    }

    /** @test */
    public function it_handles_min_max_configuration(): void
    {
        $field = Number::make('Age')
            ->min(18)
            ->max(120);

        $this->assertEquals(18, $field->min);
        $this->assertEquals(120, $field->max);
    }

    /** @test */
    public function it_handles_step_configuration(): void
    {
        $field = Number::make('Price')->step(0.01);

        $this->assertEquals(0.01, $field->step);
    }

    /** @test */
    public function it_handles_null_values(): void
    {
        $user = new \stdClass;

        $field = Number::make('Age', 'age');
        $request = new Request(['age' => null]);
        $field->fill($request, $user);

        $this->assertNull($user->age);
    }

    /** @test */
    public function it_handles_empty_string_values(): void
    {
        $user = new \stdClass;

        $field = Number::make('Age', 'age');
        $request = new Request(['age' => '']);
        $field->fill($request, $user);

        $this->assertNull($user->age);
    }

    /** @test */
    public function it_serializes_for_frontend_with_number_meta(): void
    {
        $field = Number::make('Price')
            ->min(0)
            ->max(9999.99)
            ->step(0.01)
            ->help('Enter price in USD')
            ->rules('required', 'numeric');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Price', $serialized['name']);
        $this->assertEquals('price', $serialized['attribute']);
        $this->assertEquals('NumberField', $serialized['component']);
        $this->assertEquals('Enter price in USD', $serialized['helpText']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('numeric', $serialized['rules']);
        $this->assertEquals(0, $serialized['min']);
        $this->assertEquals(9999.99, $serialized['max']);
        $this->assertEquals(0.01, $serialized['step']);
    }

    /** @test */
    public function it_supports_method_chaining(): void
    {
        $field = Number::make('Quantity')
            ->min(1)
            ->max(100)
            ->step(1)
            ->required()
            ->sortable()
            ->searchable();

        $this->assertInstanceOf(Number::class, $field);
        $this->assertEquals(1, $field->min);
        $this->assertEquals(100, $field->max);
        $this->assertEquals(1, $field->step);
        $this->assertContains('required', $field->rules);
        $this->assertTrue($field->sortable);
        $this->assertTrue($field->searchable);
    }

    /** @test */
    public function it_handles_negative_values(): void
    {
        $user = new \stdClass;
        $field = Number::make('Temperature', 'temperature')->min(-100);

        $request = new Request(['temperature' => '-15']);
        $field->fill($request, $user);

        $this->assertEquals(-15, $user->temperature);
        $this->assertIsInt($user->temperature);
    }

    /** @test */
    public function it_handles_zero_values(): void
    {
        $user = new \stdClass;
        $field = Number::make('Count', 'count');

        $request = new Request(['count' => '0']);
        $field->fill($request, $user);

        $this->assertEquals(0, $user->count);
        $this->assertIsInt($user->count);
    }

    /** @test */
    public function it_handles_large_numbers(): void
    {
        $user = new \stdClass;
        $field = Number::make('Population', 'population');

        $request = new Request(['population' => '1000000']);
        $field->fill($request, $user);

        $this->assertEquals(1000000, $user->population);
        $this->assertIsInt($user->population);
    }

    /** @test */
    public function it_handles_decimal_precision(): void
    {
        $user = new \stdClass;
        $field = Number::make('Precision', 'precision')->step(0.001);

        $request = new Request(['precision' => '1.234']);
        $field->fill($request, $user);

        $this->assertEquals(1.234, $user->precision);
        $this->assertIsFloat($user->precision);
    }

    /** @test */
    public function it_integrates_with_laravel_validation(): void
    {
        $field = Number::make('Age')
            ->min(18)
            ->max(120)
            ->rules('required', 'integer', 'between:18,120');

        $rules = $field->rules;

        $this->assertContains('required', $rules);
        $this->assertContains('integer', $rules);
        $this->assertContains('between:18,120', $rules);

        // Min/max properties should be set for frontend validation
        $this->assertEquals(18, $field->min);
        $this->assertEquals(120, $field->max);
    }

    /** @test */
    public function it_supports_nullable_numbers(): void
    {
        $field = Number::make('OptionalAge', 'optional_age')->nullable();

        $this->assertTrue($field->nullable);

        $user = new \stdClass;
        $request = new Request(['optional_age' => '']);
        $field->fill($request, $user);

        $this->assertNull($user->optional_age);
    }

    /** @test */
    public function it_handles_custom_fill_callback(): void
    {
        $field = Number::make('Age');
        $field->fillCallback = function ($request, $model, $attribute) {
            $model->$attribute = $request->input($attribute) * 2;
        };

        $user = new \stdClass;
        $request = new Request(['age' => '25']);
        $field->fill($request, $user);

        $this->assertEquals(50, $user->age);
    }

    /** @test */
    public function it_preserves_numeric_types_based_on_step(): void
    {
        $user = new \stdClass;

        // Integer step should produce integer
        $intField = Number::make('Count', 'count')->step(1);
        $request = new Request(['count' => '42']);
        $intField->fill($request, $user);
        $this->assertIsInt($user->count);

        // Decimal step should produce float
        $floatField = Number::make('Price', 'price')->step(0.01);
        $request = new Request(['price' => '19']);
        $floatField->fill($request, $user);
        $this->assertIsFloat($user->price);
    }

    /** @test */
    public function it_nova_api_compatibility_complete(): void
    {
        // Test complete Nova Number field API compatibility
        $field = Number::make('Price', 'price')
            ->min(0)
            ->max(9999.99)
            ->step(0.01);

        // All Nova methods should return the field instance for chaining
        $this->assertInstanceOf(Number::class, $field);
        $this->assertEquals(0, $field->min);
        $this->assertEquals(9999.99, $field->max);
        $this->assertEquals(0.01, $field->step);

        // Should work with Nova-style usage
        $user = new \stdClass;
        $request = new Request(['price' => '19.99']);
        $field->fill($request, $user);

        $this->assertEquals(19.99, $user->price);
        $this->assertIsFloat($user->price);
    }
}
