<?php

namespace JTD\AdminPanel\Tests\E2E\Fields;

use App\Fields\Currency;
use Illuminate\Http\Request;
use JTD\AdminPanel\Tests\TestCase;

/**
 * End-to-end tests for Currency field covering the complete flow:
 * PHP Field -> JSON Serialization -> Client Processing -> Form Submission -> PHP Fill
 */
class CurrencyFieldE2ETest extends TestCase
{
    public function test_currency_field_complete_flow_major_units(): void
    {
        // 1. Create Currency field (PHP)
        $field = Currency::make('Product Price')
            ->currency('USD')
            ->locale('en-US')
            ->min(0)
            ->max(999.99)
            ->step(0.01);

        // 2. Simulate resource with data
        $resource = (object) ['product_price' => 123.45];
        $field->resolve($resource);

        // 3. Serialize for client (simulates API response)
        $serialized = $field->jsonSerialize();
        
        // Verify serialization structure
        $this->assertEquals('Product Price', $serialized['name']);
        $this->assertEquals('product_price', $serialized['attribute']);
        $this->assertEquals('CurrencyField', $serialized['component']);
        $this->assertEquals(123.45, $serialized['value']);
        
        $meta = $serialized['meta'];
        $this->assertEquals('USD', $meta['currency']);
        $this->assertEquals('en-US', $meta['locale']);
        $this->assertEquals('$', $meta['symbol']);
        $this->assertEquals(0, $meta['minValue']);
        $this->assertEquals(999.99, $meta['maxValue']);
        $this->assertEquals(0.01, $meta['step']);
        $this->assertFalse($meta['asMinorUnits']);

        // 4. Simulate form submission (client -> server)
        $newField = Currency::make('Product Price')
            ->currency('USD')
            ->locale('en-US')
            ->min(0)
            ->max(999.99)
            ->step(0.01);

        $model = new \stdClass();
        $request = new Request(['product_price' => '456.78']);
        
        // 5. Fill model with submitted data
        $newField->fill($request, $model);
        
        // 6. Verify final result
        $this->assertEquals(456.78, $model->product_price);
    }

    public function test_currency_field_complete_flow_minor_units(): void
    {
        // 1. Create Currency field with minor units (PHP)
        $field = Currency::make('Price')
            ->currency('EUR')
            ->locale('de-DE')
            ->asMinorUnits();

        // 2. Simulate resource with data stored as cents
        $resource = (object) ['price' => 12345]; // 123.45 EUR in cents
        $field->resolve($resource);

        // 3. Serialize for client
        $serialized = $field->jsonSerialize();
        
        // Value should be converted to major units for display
        $this->assertEquals(123.45, $serialized['value']);
        
        $meta = $serialized['meta'];
        $this->assertEquals('EUR', $meta['currency']);
        $this->assertEquals('de-DE', $meta['locale']);
        $this->assertEquals('€', $meta['symbol']);
        $this->assertEquals(1, $meta['step']); // Step is 1 for minor units
        $this->assertTrue($meta['asMinorUnits']);

        // 4. Simulate form submission with major units
        $newField = Currency::make('Price')
            ->currency('EUR')
            ->locale('de-DE')
            ->asMinorUnits();

        $model = new \stdClass();
        $request = new Request(['price' => '99.99']); // User enters 99.99 EUR
        
        // 5. Fill should convert to minor units for storage
        $newField->fill($request, $model);
        
        // 6. Verify stored as cents
        $this->assertEquals(9999.0, $model->price);
    }

    public function test_currency_field_e2e_with_formatted_input(): void
    {
        // Test that formatted currency input is properly cleaned and processed
        $field = Currency::make('Amount')
            ->currency('GBP')
            ->locale('en-GB');

        // Simulate form submission with formatted input
        $model = new \stdClass();
        
        // Test various formatted inputs
        $testCases = [
            '£123.45' => 123.45,
            '£ 1,234.56' => 1234.56,
            '-£50.00' => -50.0,
            '1234.56' => 1234.56,
            '0' => 0.0,
        ];

        foreach ($testCases as $input => $expected) {
            $request = new Request(['amount' => $input]);
            $field->fill($request, $model);
            $this->assertEquals($expected, $model->amount, "Failed for input: {$input}");
        }
    }

    public function test_currency_field_e2e_validation_constraints(): void
    {
        // Test that min/max constraints are properly serialized and can be used for validation
        $field = Currency::make('Budget')
            ->currency('CAD')
            ->min(100)
            ->max(5000)
            ->step(25);

        $serialized = $field->jsonSerialize();
        $meta = $serialized['meta'];

        // Verify constraints are in meta for client-side validation
        $this->assertEquals(100, $meta['minValue']);
        $this->assertEquals(5000, $meta['maxValue']);
        $this->assertEquals(25, $meta['step']);

        // Test filling within constraints
        $model = new \stdClass();
        $request = new Request(['budget' => '2500']);
        $field->fill($request, $model);
        $this->assertEquals(2500.0, $model->budget);
    }

    public function test_currency_field_e2e_null_and_empty_handling(): void
    {
        $field = Currency::make('Optional Price')
            ->currency('USD')
            ->asMinorUnits();

        // Test null value resolve
        $resource = (object) ['optional_price' => null];
        $field->resolve($resource);
        $serialized = $field->jsonSerialize();
        $this->assertNull($serialized['value']);

        // Test null value fill
        $model = new \stdClass();
        $request = new Request(['optional_price' => null]);
        $field->fill($request, $model);
        $this->assertNull($model->optional_price);

        // Test empty string fill
        $request2 = new Request(['optional_price' => '']);
        $field->fill($request2, $model);
        $this->assertNull($model->optional_price);
    }

    public function test_currency_field_e2e_symbol_generation(): void
    {
        // Test that symbols are correctly generated for different currencies
        $currencies = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'CHF' => 'CHF',
            'XYZ' => 'XYZ', // Unknown currency should fallback to code
        ];

        foreach ($currencies as $currency => $expectedSymbol) {
            $field = Currency::make('Price')->currency($currency);
            $serialized = $field->jsonSerialize();
            $meta = $serialized['meta'];
            
            $this->assertEquals($expectedSymbol, $meta['symbol'], 
                "Symbol mismatch for currency: {$currency}");
        }
    }

    public function test_currency_field_e2e_locale_handling(): void
    {
        // Test that locale is properly passed through the serialization
        $locales = ['en-US', 'en-GB', 'fr-FR', 'de-DE', 'ja-JP'];

        foreach ($locales as $locale) {
            $field = Currency::make('Price')
                ->currency('USD')
                ->locale($locale);

            $serialized = $field->jsonSerialize();
            $meta = $serialized['meta'];
            
            $this->assertEquals($locale, $meta['locale'], 
                "Locale not preserved: {$locale}");
        }
    }

    public function test_currency_field_e2e_step_behavior(): void
    {
        // Test step behavior with different unit modes
        
        // Major units - custom step preserved
        $majorField = Currency::make('Price')->step(0.25);
        $serialized = $majorField->jsonSerialize();
        $this->assertEquals(0.25, $serialized['meta']['step']);

        // Minor units - step should be 1
        $minorField = Currency::make('Price')->asMinorUnits();
        $serialized = $minorField->jsonSerialize();
        $this->assertEquals(1, $serialized['meta']['step']);

        // Switch back to major units - step should reset to 0.01
        $switchedField = Currency::make('Price')->asMinorUnits()->asMajorUnits();
        $serialized = $switchedField->jsonSerialize();
        $this->assertEquals(0.01, $serialized['meta']['step']);
    }

    public function test_currency_field_e2e_complete_crud_simulation(): void
    {
        // Simulate a complete CRUD operation
        
        // CREATE: New record with currency field
        $createField = Currency::make('Price')->currency('USD')->asMinorUnits();
        $createModel = new \stdClass();
        $createRequest = new Request(['price' => '19.99']);
        $createField->fill($createRequest, $createModel);
        $this->assertEquals(1999.0, $createModel->price); // Stored as cents

        // READ: Display existing record
        $readField = Currency::make('Price')->currency('USD')->asMinorUnits();
        $readResource = (object) ['price' => 1999]; // From database
        $readField->resolve($readResource);
        $readSerialized = $readField->jsonSerialize();
        $this->assertEquals(19.99, $readSerialized['value']); // Displayed as dollars

        // UPDATE: Modify existing record
        $updateField = Currency::make('Price')->currency('USD')->asMinorUnits();
        $updateModel = new \stdClass();
        $updateRequest = new Request(['price' => '24.99']);
        $updateField->fill($updateRequest, $updateModel);
        $this->assertEquals(2499.0, $updateModel->price); // Updated and stored as cents
    }
}
