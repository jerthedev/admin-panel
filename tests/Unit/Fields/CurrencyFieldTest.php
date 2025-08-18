<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Currency;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Currency Field Unit Tests
 *
 * Tests for Currency field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class CurrencyFieldTest extends TestCase
{
    public function test_currency_field_creation(): void
    {
        $field = Currency::make('Price');

        $this->assertEquals('Price', $field->name);
        $this->assertEquals('price', $field->attribute);
        $this->assertEquals('CurrencyField', $field->component);
    }

    public function test_currency_field_with_custom_attribute(): void
    {
        $field = Currency::make('Product Price', 'product_price');

        $this->assertEquals('Product Price', $field->name);
        $this->assertEquals('product_price', $field->attribute);
    }

    public function test_currency_field_default_properties(): void
    {
        $field = Currency::make('Price');

        $this->assertEquals('en_US', $field->locale);
        $this->assertEquals('USD', $field->currency);
        $this->assertNull($field->symbol);
        $this->assertEquals(2, $field->precision);
        $this->assertNull($field->minValue);
        $this->assertNull($field->maxValue);
        $this->assertEquals('symbol', $field->displayFormat);
        $this->assertEquals(0.01, $field->step);
    }

    public function test_currency_field_locale_configuration(): void
    {
        $field = Currency::make('Price')->locale('de_DE');

        $this->assertEquals('de_DE', $field->locale);
    }

    public function test_currency_field_currency_configuration(): void
    {
        $field = Currency::make('Price')->currency('EUR');

        $this->assertEquals('EUR', $field->currency);
    }

    public function test_currency_field_symbol_configuration(): void
    {
        $field = Currency::make('Price')->symbol('€');

        $this->assertEquals('€', $field->symbol);
    }

    public function test_currency_field_precision_configuration(): void
    {
        $field = Currency::make('Price')->precision(0);

        $this->assertEquals(0, $field->precision);
    }

    public function test_currency_field_min_value_configuration(): void
    {
        $field = Currency::make('Price')->min(0.0);

        $this->assertEquals(0.0, $field->minValue);
    }

    public function test_currency_field_max_value_configuration(): void
    {
        $field = Currency::make('Price')->max(999.99);

        $this->assertEquals(999.99, $field->maxValue);
    }

    public function test_currency_field_display_format_configuration(): void
    {
        $field = Currency::make('Price')->displayFormat('code');

        $this->assertEquals('code', $field->displayFormat);
    }

    public function test_currency_field_step_configuration(): void
    {
        $field = Currency::make('Price')->step(0.25);

        $this->assertEquals(0.25, $field->step);
    }

    public function test_currency_field_symbol_configuration_affects_meta(): void
    {
        $field = Currency::make('Price')->currency('USD');

        $meta = $field->meta();

        $this->assertEquals('USD', $meta['currency']);
    }

    public function test_currency_field_custom_symbol_in_meta(): void
    {
        $field = Currency::make('Price')->symbol('€');

        $meta = $field->meta();

        $this->assertEquals('€', $meta['symbol']);
    }

    public function test_currency_field_precision_affects_formatting(): void
    {
        $field = Currency::make('Price')->precision(0);

        $meta = $field->meta();

        $this->assertEquals(0, $meta['precision']);
    }

    public function test_currency_field_fill_converts_to_float(): void
    {
        $field = Currency::make('Price');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['price' => '19.99']);

        $field->fill($request, $model);

        $this->assertEquals(19.99, $model->price);
        $this->assertIsFloat($model->price);
    }

    public function test_currency_field_fill_handles_integer(): void
    {
        $field = Currency::make('Price');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['price' => '20']);

        $field->fill($request, $model);

        $this->assertEquals(20.0, $model->price);
        $this->assertIsFloat($model->price);
    }

    public function test_currency_field_fill_handles_empty_value(): void
    {
        $field = Currency::make('Price');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['price' => '']);

        $field->fill($request, $model);

        $this->assertNull($model->price);
    }

    public function test_currency_field_fill_with_callback(): void
    {
        $field = Currency::make('Price')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 99.99;
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['price' => '19.99']);

        $field->fill($request, $model);

        $this->assertEquals(99.99, $model->price);
    }

    public function test_currency_field_resolve_preserves_numeric_value(): void
    {
        $field = Currency::make('Price')->currency('USD')->precision(2);
        $resource = (object) ['price' => 19.99];

        $field->resolve($resource);

        $this->assertEquals(19.99, $field->value);
    }

    public function test_currency_field_meta_includes_all_properties(): void
    {
        $field = Currency::make('Price')
            ->locale('de_DE')
            ->currency('EUR')
            ->symbol('€')
            ->precision(2)
            ->min(0.0)
            ->max(999.99)
            ->displayFormat('symbol')
            ->step(0.01);

        $meta = $field->meta();

        $this->assertArrayHasKey('locale', $meta);
        $this->assertArrayHasKey('currency', $meta);
        $this->assertArrayHasKey('symbol', $meta);
        $this->assertArrayHasKey('precision', $meta);
        $this->assertArrayHasKey('minValue', $meta);
        $this->assertArrayHasKey('maxValue', $meta);
        $this->assertArrayHasKey('displayFormat', $meta);
        $this->assertArrayHasKey('step', $meta);
        $this->assertEquals('de_DE', $meta['locale']);
        $this->assertEquals('EUR', $meta['currency']);
        $this->assertEquals('€', $meta['symbol']);
        $this->assertEquals(2, $meta['precision']);
        $this->assertEquals(0.0, $meta['minValue']);
        $this->assertEquals(999.99, $meta['maxValue']);
        $this->assertEquals('symbol', $meta['displayFormat']);
        $this->assertEquals(0.01, $meta['step']);
    }

    public function test_currency_field_get_currency_symbol_through_meta(): void
    {
        // Test getCurrencySymbol() indirectly through meta() method
        $field1 = Currency::make('Price')->currency('USD');
        $meta1 = $field1->meta();
        $this->assertEquals('$', $meta1['symbol']);

        $field2 = Currency::make('Price')->currency('EUR');
        $meta2 = $field2->meta();
        $this->assertEquals('€', $meta2['symbol']);

        $field3 = Currency::make('Price')->currency('GBP');
        $meta3 = $field3->meta();
        $this->assertEquals('£', $meta3['symbol']);

        // Test unknown currency falls back to currency code
        $field4 = Currency::make('Price')->currency('XYZ');
        $meta4 = $field4->meta();
        $this->assertEquals('XYZ', $meta4['symbol']);
    }

    public function test_currency_field_clean_currency_value_through_fill(): void
    {
        // Test cleanCurrencyValue() indirectly through fill() method
        $field = Currency::make('Price');
        $model = new \stdClass();

        // Test with currency symbols and formatting
        $request1 = new \Illuminate\Http\Request(['price' => '$19.99']);
        $field->fill($request1, $model);
        $this->assertEquals(19.99, $model->price);

        // Test with spaces and commas
        $request2 = new \Illuminate\Http\Request(['price' => '€ 1,234.56']);
        $field->fill($request2, $model);
        $this->assertEquals(1234.56, $model->price);

        // Test with negative values
        $request3 = new \Illuminate\Http\Request(['price' => '-$50.00']);
        $field->fill($request3, $model);
        $this->assertEquals(-50.0, $model->price);

        // Test with invalid currency value
        $request4 = new \Illuminate\Http\Request(['price' => 'invalid']);
        $field->fill($request4, $model);
        $this->assertNull($model->price);
    }

    public function test_currency_field_inheritance_from_field(): void
    {
        $field = Currency::make('Price');

        // Test that Currency field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'placeholder'));
    }

    public function test_currency_field_with_validation_rules(): void
    {
        $field = Currency::make('Price')
            ->rules('required', 'numeric', 'min:0');

        $this->assertEquals(['required', 'numeric', 'min:0'], $field->rules);
    }

    public function test_currency_field_complex_configuration(): void
    {
        $field = Currency::make('Product Price')
            ->locale('en_GB')
            ->currency('GBP')
            ->min(0.01)
            ->max(9999.99)
            ->step(0.01);

        // Test all configurations are set
        $this->assertEquals('en_GB', $field->locale);
        $this->assertEquals('GBP', $field->currency);
        $this->assertEquals(0.01, $field->minValue);
        $this->assertEquals(9999.99, $field->maxValue);
        $this->assertEquals(0.01, $field->step);
    }

    public function test_currency_field_json_serialization(): void
    {
        $field = Currency::make('Product Price')
            ->currency('EUR')
            ->min(0.0)
            ->max(1000.0)
            ->required()
            ->help('Enter product price');

        $json = $field->jsonSerialize();

        $this->assertEquals('Product Price', $json['name']);
        $this->assertEquals('product_price', $json['attribute']);
        $this->assertEquals('CurrencyField', $json['component']);
        $this->assertEquals('EUR', $json['currency']);
        $this->assertEquals(0.0, $json['minValue']);
        $this->assertEquals(1000.0, $json['maxValue']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Enter product price', $json['helpText']);
    }

    public function test_as_minor_units_affects_resolve_and_fill(): void
    {
        $field = Currency::make('Price')->asMinorUnits();

        // Resolve: stored as cents -> display as major units
        $resource = (object) ['price' => 12345]; // 123.45 in major units
        $field->resolve($resource);
        $this->assertEquals(123.45, $field->value);

        // Fill: incoming major units -> store as cents
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['price' => '99.99']);
        $field->fill($request, $model);
        $this->assertEquals(9999.0, $model->price);

        // Meta serialization includes asMinorUnits flag
        $meta = $field->meta();
        $this->assertArrayHasKey('asMinorUnits', $meta);
        $this->assertTrue($meta['asMinorUnits']);

        // Step should be 1 for minor units
        $this->assertEquals(1, $field->step);
    }

    public function test_as_major_units_resets_step(): void
    {
        $field = Currency::make('Price')->asMinorUnits()->asMajorUnits();

        $this->assertFalse($field->asMinorUnits);
        $this->assertEquals(0.01, $field->step);
    }

    public function test_nova_api_methods_exist(): void
    {
        $field = Currency::make('Price')
            ->currency('EUR')
            ->locale('fr')
            ->min(0)
            ->max(1000)
            ->step(0.05);

        $this->assertEquals('EUR', $field->currency);
        $this->assertEquals('fr', $field->locale);
        $this->assertEquals(0, $field->minValue);
        $this->assertEquals(1000, $field->maxValue);
        $this->assertEquals(0.05, $field->step);
    }

    public function test_meta_excludes_non_nova_properties(): void
    {
        $field = Currency::make('Price')->currency('USD');
        $meta = $field->meta();

        // Should include Nova properties
        $this->assertArrayHasKey('currency', $meta);
        $this->assertArrayHasKey('locale', $meta);
        $this->assertArrayHasKey('symbol', $meta);
        $this->assertArrayHasKey('minValue', $meta);
        $this->assertArrayHasKey('maxValue', $meta);
        $this->assertArrayHasKey('step', $meta);
        $this->assertArrayHasKey('asMinorUnits', $meta);

        // Should NOT include non-Nova properties
        $this->assertArrayNotHasKey('precision', $meta);
        $this->assertArrayNotHasKey('displayFormat', $meta);
    }

    public function test_currency_field_inherited_field_methods(): void
    {
        $field = Currency::make('Price');

        // Test core inherited methods that definitely exist
        $this->assertTrue(method_exists($field, 'sortable'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'required'));

        // Test that these methods can be called and return the field instance
        $result = $field->sortable()->nullable()->readonly();
        $this->assertInstanceOf(Currency::class, $result);

        // Test property setting
        $this->assertTrue($field->sortable);
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
    }

    public function test_currency_field_authorization_methods(): void
    {
        $field = Currency::make('Price');

        // Test authorization methods
        $this->assertTrue(method_exists($field, 'canSee'));
        $this->assertTrue(method_exists($field, 'canUpdate'));
        $this->assertTrue(method_exists($field, 'authorizedToSee'));
        $this->assertTrue(method_exists($field, 'authorizedToUpdate'));

        // Test default authorization (should be true)
        $request = new \Illuminate\Http\Request();
        $this->assertTrue($field->authorizedToSee($request));
        $this->assertTrue($field->authorizedToUpdate($request));

        // Test with callback
        $field->canSee(function () { return false; });
        $this->assertFalse($field->authorizedToSee($request));
    }

    public function test_currency_field_display_callback_functionality(): void
    {
        $field = Currency::make('Price');

        // Test display callback methods
        $this->assertTrue(method_exists($field, 'displayUsing'));
        $this->assertTrue(method_exists($field, 'resolveUsing'));
        $this->assertTrue(method_exists($field, 'fillUsing'));

        // Test display callback
        $field->displayUsing(function ($value) {
            return '$' . number_format($value, 2);
        });

        $this->assertNotNull($field->displayCallback);

        // Test resolve callback
        $field->resolveUsing(function ($resource, $attribute) {
            return $resource->{$attribute} * 1.1; // Add 10% tax
        });

        $this->assertNotNull($field->resolveCallback);
    }

    public function test_currency_field_comprehensive_method_coverage(): void
    {
        $field = Currency::make('Price');

        // Test methods that might not be covered elsewhere
        $this->assertTrue(method_exists($field, 'default'));
        $this->assertTrue(method_exists($field, 'suffix'));
        $this->assertTrue(method_exists($field, 'fullWidth'));
        $this->assertTrue(method_exists($field, 'withMeta'));

        // Test these methods work
        $field->default(0.00)
              ->suffix('USD')
              ->fullWidth()
              ->withMeta(['custom' => 'data']);

        $this->assertEquals(0.00, $field->default);
        $this->assertEquals('USD', $field->suffix);
        $this->assertTrue($field->fullWidth);
        $this->assertEquals(['custom' => 'data'], $field->meta);
    }
}
