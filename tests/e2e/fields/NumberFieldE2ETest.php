<?php

declare(strict_types=1);

namespace E2E\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Tests\TestCase;

class NumberFieldE2ETest extends TestCase
{
    /** @test */
    public function it_serializes_and_fills_like_nova_in_end_to_end_flow(): void
    {
        // Simulate backend field creation like Nova
        $field = Number::make('Price', 'price')
            ->min(0)
            ->max(9999.99)
            ->step(0.01)
            ->help('Enter price in USD')
            ->rules('required', 'numeric');

        $serialized = $field->jsonSerialize();

        // Verify Nova-compatible serialization
        $this->assertEquals('NumberField', $serialized['component']);
        $this->assertEquals('Price', $serialized['name']);
        $this->assertEquals('price', $serialized['attribute']);
        $this->assertEquals('Enter price in USD', $serialized['helpText']);
        $this->assertEquals(0, $serialized['min']);
        $this->assertEquals(9999.99, $serialized['max']);
        $this->assertEquals(0.01, $serialized['step']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('numeric', $serialized['rules']);

        // Simulate a client update with decimal value
        $request = new Request(['price' => '19.99']);
        $model = new \stdClass;
        $field->fill($request, $model);

        // Verify value is properly converted to float
        $this->assertEquals(19.99, $model->price);
        $this->assertIsFloat($model->price);
    }

    /** @test */
    public function it_handles_integer_values_end_to_end(): void
    {
        $field = Number::make('Quantity', 'quantity')
            ->min(1)
            ->max(100)
            ->step(1);

        // Test integer step produces integer values
        $request = new Request(['quantity' => '42']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(42, $model->quantity);
        $this->assertIsInt($model->quantity);

        // Verify serialization
        $serialized = $field->jsonSerialize();
        $this->assertEquals(1, $serialized['min']);
        $this->assertEquals(100, $serialized['max']);
        $this->assertEquals(1, $serialized['step']);
    }

    /** @test */
    public function it_handles_decimal_values_end_to_end(): void
    {
        $field = Number::make('Rating', 'rating')
            ->min(0)
            ->max(5)
            ->step(0.1);

        // Test decimal step produces float values
        $request = new Request(['rating' => '4.7']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(4.7, $model->rating);
        $this->assertIsFloat($model->rating);

        // Test precision handling
        $request = new Request(['rating' => '3.14159']);
        $field->fill($request, $model);

        $this->assertEquals(3.14159, $model->rating);
        $this->assertIsFloat($model->rating);
    }

    /** @test */
    public function it_handles_edge_cases_end_to_end(): void
    {
        $field = Number::make('Value', 'value');

        // Test zero value
        $request = new Request(['value' => '0']);
        $model = new \stdClass;
        $field->fill($request, $model);
        $this->assertEquals(0, $model->value);

        // Test negative value
        $field = Number::make('Temperature', 'temperature')->min(-100);
        $request = new Request(['temperature' => '-15']);
        $model = new \stdClass;
        $field->fill($request, $model);
        $this->assertEquals(-15, $model->temperature);

        // Test null value
        $field = Number::make('Value', 'value');
        $request = new Request(['value' => null]);
        $model = new \stdClass;
        $field->fill($request, $model);
        $this->assertNull($model->value);

        // Test empty string
        $request = new Request(['value' => '']);
        $model = new \stdClass;
        $field->fill($request, $model);
        $this->assertNull($model->value);
    }

    /** @test */
    public function it_integrates_with_laravel_validation_end_to_end(): void
    {
        $field = Number::make('Age', 'age')
            ->min(18)
            ->max(120)
            ->rules('required', 'integer', 'between:18,120');

        $serialized = $field->jsonSerialize();

        // Verify validation rules are properly serialized
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('integer', $serialized['rules']);
        $this->assertContains('between:18,120', $serialized['rules']);

        // Test filling with valid age
        $request = new Request(['age' => '25']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(25, $model->age);
        $this->assertIsInt($model->age);
    }

    /** @test */
    public function it_handles_nullable_numbers_end_to_end(): void
    {
        $field = Number::make('OptionalCount', 'optional_count')->nullable();

        $serialized = $field->jsonSerialize();
        $this->assertTrue($serialized['nullable']);

        // Test with null value
        $request = new Request(['optional_count' => null]);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertNull($model->optional_count);

        // Test with empty string
        $request = new Request(['optional_count' => '']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertNull($model->optional_count);

        // Test with valid value
        $request = new Request(['optional_count' => '42']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(42, $model->optional_count);
    }

    /** @test */
    public function it_resolves_values_for_display_end_to_end(): void
    {
        $model = new \stdClass;
        $model->price = 19.99;

        $field = Number::make('Price', 'price');
        $field->resolve($model);

        // Verify value is resolved correctly for display
        $this->assertEquals(19.99, $field->value);

        $serialized = $field->jsonSerialize();
        $this->assertEquals(19.99, $serialized['value']);
    }

    /** @test */
    public function it_supports_method_chaining_end_to_end(): void
    {
        $field = Number::make('Product Price', 'product_price')
            ->min(0.01)
            ->max(99999.99)
            ->step(0.01)
            ->nullable()
            ->sortable()
            ->searchable()
            ->help('Enter product price in USD')
            ->placeholder('0.00')
            ->rules('numeric', 'min:0.01');

        $serialized = $field->jsonSerialize();

        // Verify all chained methods are applied
        $this->assertEquals('Product Price', $serialized['name']);
        $this->assertEquals('product_price', $serialized['attribute']);
        $this->assertEquals(0.01, $serialized['min']);
        $this->assertEquals(99999.99, $serialized['max']);
        $this->assertEquals(0.01, $serialized['step']);
        $this->assertTrue($serialized['nullable']);
        $this->assertTrue($serialized['sortable']);
        $this->assertTrue($serialized['searchable']);
        $this->assertEquals('Enter product price in USD', $serialized['helpText']);
        $this->assertEquals('0.00', $serialized['placeholder']);
        $this->assertContains('numeric', $serialized['rules']);
        $this->assertContains('min:0.01', $serialized['rules']);
    }

    /** @test */
    public function it_maintains_nova_compatibility_end_to_end(): void
    {
        // Test basic Nova syntax
        $field1 = Number::make('Age');
        $this->assertEquals('Age', $field1->name);
        $this->assertEquals('age', $field1->attribute);

        // Test Nova syntax with custom attribute
        $field2 = Number::make('User Age', 'user_age');
        $this->assertEquals('User Age', $field2->name);
        $this->assertEquals('user_age', $field2->attribute);

        // Test Nova min/max/step methods
        $field3 = Number::make('Price')->min(0)->max(1000)->step(0.01);
        $this->assertEquals(0, $field3->min);
        $this->assertEquals(1000, $field3->max);
        $this->assertEquals(0.01, $field3->step);

        // Verify all return field instance for chaining
        $this->assertInstanceOf(Number::class, $field3);
    }

    /** @test */
    public function it_handles_large_numbers_end_to_end(): void
    {
        $field = Number::make('Population', 'population');

        // Test large integer
        $request = new Request(['population' => '1000000']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(1000000, $model->population);
        $this->assertIsInt($model->population);

        // Test large decimal
        $field = Number::make('Revenue', 'revenue')->step(0.01);
        $request = new Request(['revenue' => '1234567.89']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(1234567.89, $model->revenue);
        $this->assertIsFloat($model->revenue);
    }

    /** @test */
    public function it_handles_precision_edge_cases_end_to_end(): void
    {
        $field = Number::make('Precision', 'precision')->step(0.001);

        // Test high precision decimal
        $request = new Request(['precision' => '1.23456789']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(1.23456789, $model->precision);
        $this->assertIsFloat($model->precision);

        // Test very small decimal
        $request = new Request(['precision' => '0.001']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(0.001, $model->precision);
        $this->assertIsFloat($model->precision);
    }

    /** @test */
    public function it_supports_custom_fill_callback_end_to_end(): void
    {
        $field = Number::make('DoubledValue', 'doubled_value');
        $field->fillCallback = function ($request, $model, $attribute) {
            $value = $request->input($attribute);
            $model->$attribute = $value ? ((float) $value) * 2 : null;
        };

        $request = new Request(['doubled_value' => '10']);
        $model = new \stdClass;
        $field->fill($request, $model);

        $this->assertEquals(20.0, $model->doubled_value);
    }

    /** @test */
    public function it_complete_nova_api_compatibility_end_to_end(): void
    {
        // Test complete Nova Number field API compatibility in real-world scenario
        $field = Number::make('Order Total', 'order_total')
            ->min(0.01)
            ->max(99999.99)
            ->step(0.01);

        // Simulate complete form submission flow
        $request = new Request(['order_total' => '149.99']);
        $order = new \stdClass;
        $field->fill($request, $order);

        // Verify Nova-compatible behavior
        $this->assertEquals(149.99, $order->order_total);
        $this->assertIsFloat($order->order_total);

        // Verify serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('NumberField', $serialized['component']);
        $this->assertEquals('Order Total', $serialized['name']);
        $this->assertEquals('order_total', $serialized['attribute']);
        $this->assertEquals(0.01, $serialized['min']);
        $this->assertEquals(99999.99, $serialized['max']);
        $this->assertEquals(0.01, $serialized['step']);

        // Test resolve for display
        $field->resolve($order);
        $this->assertEquals(149.99, $field->value);
    }
}
