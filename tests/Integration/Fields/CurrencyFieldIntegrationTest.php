<?php

namespace Tests\Integration\Fields;

use App\Fields\Currency;
use Illuminate\Http\Request;
use Tests\TestCase;

class CurrencyFieldIntegrationTest extends TestCase
{
    public function test_currency_field_serializes_correctly_for_nova_api(): void
    {
        $field = Currency::make('Price')
            ->currency('EUR')
            ->locale('fr')
            ->min(0)
            ->max(1000)
            ->step(0.05)
            ->asMinorUnits();

        $serialized = $field->jsonSerialize();

        // Test Nova-compatible structure
        $this->assertEquals('Price', $serialized['name']);
        $this->assertEquals('price', $serialized['attribute']);
        $this->assertEquals('CurrencyField', $serialized['component']);

        // Test meta contains Nova properties
        $meta = $serialized['meta'] ?? [];
        $this->assertEquals('EUR', $meta['currency']);
        $this->assertEquals('fr', $meta['locale']);
        $this->assertEquals(0, $meta['minValue']);
        $this->assertEquals(1000, $meta['maxValue']);
        $this->assertEquals(1, $meta['step']); // Should be 1 for minor units
        $this->assertTrue($meta['asMinorUnits']);
        $this->assertArrayHasKey('symbol', $meta);

        // Test meta excludes non-Nova properties
        $this->assertArrayNotHasKey('precision', $meta);
        $this->assertArrayNotHasKey('displayFormat', $meta);
    }

    public function test_currency_field_resolve_and_fill_integration(): void
    {
        // Test with minor units
        $field = Currency::make('Amount')->asMinorUnits();
        
        // Create a mock resource with cents stored
        $resource = (object) ['amount' => 12345]; // 123.45 in major units
        
        // Resolve should convert to major units
        $field->resolve($resource);
        $this->assertEquals(123.45, $field->value);
        
        // Fill should convert back to minor units
        $model = new \stdClass();
        $request = new Request(['amount' => '99.99']);
        $field->fill($request, $model);
        $this->assertEquals(9999.0, $model->amount);
    }

    public function test_currency_field_major_units_integration(): void
    {
        // Test with major units (default)
        $field = Currency::make('Price')->asMajorUnits();
        
        // Create a mock resource with decimal stored
        $resource = (object) ['price' => 123.45];
        
        // Resolve should preserve value
        $field->resolve($resource);
        $this->assertEquals(123.45, $field->value);
        
        // Fill should preserve value
        $model = new \stdClass();
        $request = new Request(['price' => '99.99']);
        $field->fill($request, $model);
        $this->assertEquals(99.99, $model->price);
    }

    public function test_currency_field_handles_null_values(): void
    {
        $field = Currency::make('Price')->asMinorUnits();
        
        // Test null resolve
        $resource = (object) ['price' => null];
        $field->resolve($resource);
        $this->assertNull($field->value);
        
        // Test null fill
        $model = new \stdClass();
        $request = new Request(['price' => null]);
        $field->fill($request, $model);
        $this->assertNull($model->price);
        
        // Test empty string fill
        $request2 = new Request(['price' => '']);
        $field->fill($request2, $model);
        $this->assertNull($model->price);
    }

    public function test_currency_field_cleans_formatted_input(): void
    {
        $field = Currency::make('Price');
        
        $model = new \stdClass();
        
        // Test currency symbol removal
        $request1 = new Request(['price' => '$123.45']);
        $field->fill($request1, $model);
        $this->assertEquals(123.45, $model->price);
        
        // Test complex formatting removal
        $request2 = new Request(['price' => '€ 1,234.56']);
        $field->fill($request2, $model);
        $this->assertEquals(1234.56, $model->price);
        
        // Test negative values
        $request3 = new Request(['price' => '-$50.00']);
        $field->fill($request3, $model);
        $this->assertEquals(-50.0, $model->price);
    }

    public function test_currency_field_step_behavior_with_units(): void
    {
        // Minor units should set step to 1
        $minorField = Currency::make('Price')->asMinorUnits();
        $this->assertEquals(1, $minorField->step);
        
        // Major units should reset step to 0.01
        $majorField = Currency::make('Price')->asMinorUnits()->asMajorUnits();
        $this->assertEquals(0.01, $majorField->step);
        
        // Custom step should be preserved for major units
        $customField = Currency::make('Price')->step(0.25);
        $this->assertEquals(0.25, $customField->step);
    }

    public function test_currency_field_symbol_generation(): void
    {
        // Test common currencies
        $usdField = Currency::make('Price')->currency('USD');
        $meta = $usdField->meta();
        $this->assertEquals('$', $meta['symbol']);
        
        $eurField = Currency::make('Price')->currency('EUR');
        $meta = $eurField->meta();
        $this->assertEquals('€', $meta['symbol']);
        
        $gbpField = Currency::make('Price')->currency('GBP');
        $meta = $gbpField->meta();
        $this->assertEquals('£', $meta['symbol']);
        
        // Test fallback for unknown currency
        $unknownField = Currency::make('Price')->currency('XYZ');
        $meta = $unknownField->meta();
        $this->assertEquals('XYZ', $meta['symbol']);
    }

    public function test_currency_field_nova_api_compatibility(): void
    {
        // Test all Nova methods exist and work
        $field = Currency::make('Price')
            ->currency('CAD')
            ->locale('en_CA')
            ->min(1)
            ->max(999)
            ->step(0.25)
            ->asMinorUnits()
            ->asMajorUnits();

        // Verify all properties are set correctly
        $this->assertEquals('CAD', $field->currency);
        $this->assertEquals('en_CA', $field->locale);
        $this->assertEquals(1, $field->minValue);
        $this->assertEquals(999, $field->maxValue);
        $this->assertEquals(0.01, $field->step); // Reset by asMajorUnits
        $this->assertFalse($field->asMinorUnits); // Reset by asMajorUnits
        
        // Verify meta serialization
        $meta = $field->meta();
        $this->assertArrayHasKey('currency', $meta);
        $this->assertArrayHasKey('locale', $meta);
        $this->assertArrayHasKey('symbol', $meta);
        $this->assertArrayHasKey('minValue', $meta);
        $this->assertArrayHasKey('maxValue', $meta);
        $this->assertArrayHasKey('step', $meta);
        $this->assertArrayHasKey('asMinorUnits', $meta);
    }
}
