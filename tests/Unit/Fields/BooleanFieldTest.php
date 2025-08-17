<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Boolean Field Unit Tests
 *
 * Tests for Boolean field class with 100% Nova API compatibility.
 * Tests all Nova Boolean field features including trueValue and falseValue methods.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class BooleanFieldTest extends TestCase
{
    /** @test */
    public function it_creates_boolean_field_with_nova_syntax(): void
    {
        $field = Boolean::make('Active');

        $this->assertEquals('Active', $field->name);
        $this->assertEquals('active', $field->attribute);
        $this->assertEquals('BooleanField', $field->component);
    }

    /** @test */
    public function it_creates_boolean_field_with_custom_attribute(): void
    {
        $field = Boolean::make('Is Published', 'is_published');

        $this->assertEquals('Is Published', $field->name);
        $this->assertEquals('is_published', $field->attribute);
    }

    /** @test */
    public function it_has_correct_default_properties(): void
    {
        $field = Boolean::make('Active');

        $this->assertTrue($field->trueValue);
        $this->assertFalse($field->falseValue);
    }

    /** @test */
    public function it_supports_nova_true_value_method(): void
    {
        $field = Boolean::make('Active')->trueValue('On');

        $this->assertEquals('On', $field->trueValue);
        $this->assertFalse($field->falseValue); // Should remain default
    }

    /** @test */
    public function it_supports_nova_false_value_method(): void
    {
        $field = Boolean::make('Active')->falseValue('Off');

        $this->assertTrue($field->trueValue); // Should remain default
        $this->assertEquals('Off', $field->falseValue);
    }

    /** @test */
    public function it_supports_both_true_and_false_values(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off');

        $this->assertEquals('On', $field->trueValue);
        $this->assertEquals('Off', $field->falseValue);
    }

    /** @test */
    public function it_supports_numeric_true_false_values(): void
    {
        $field = Boolean::make('Active')
            ->trueValue(1)
            ->falseValue(0);

        $this->assertEquals(1, $field->trueValue);
        $this->assertEquals(0, $field->falseValue);
    }

    /** @test */
    public function it_supports_string_true_false_values(): void
    {
        $field = Boolean::make('Status')
            ->trueValue('yes')
            ->falseValue('no');

        $this->assertEquals('yes', $field->trueValue);
        $this->assertEquals('no', $field->falseValue);
    }

    /** @test */
    public function it_supports_mixed_type_true_false_values(): void
    {
        $field = Boolean::make('Status')
            ->trueValue('active')
            ->falseValue(0);

        $this->assertEquals('active', $field->trueValue);
        $this->assertEquals(0, $field->falseValue);
    }

    /** @test */
    public function it_serializes_nova_properties_in_meta(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off');

        $meta = $field->meta();

        $this->assertEquals('On', $meta['trueValue']);
        $this->assertEquals('Off', $meta['falseValue']);
    }

    /** @test */
    public function it_serializes_default_values_in_meta(): void
    {
        $field = Boolean::make('Active');

        $meta = $field->meta();

        $this->assertTrue($meta['trueValue']);
        $this->assertFalse($meta['falseValue']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off')
            ->nullable()
            ->help('Toggle the active status');

        $this->assertInstanceOf(Boolean::class, $field);
        $this->assertEquals('On', $field->trueValue);
        $this->assertEquals('Off', $field->falseValue);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Toggle the active status', $field->helpText);
    }

    /** @test */
    public function it_inherits_all_field_methods(): void
    {
        $field = Boolean::make('Active');

        // Test that Boolean field inherits all base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'jsonSerialize'));

        // Test Nova-specific Boolean methods
        $this->assertTrue(method_exists($field, 'trueValue'));
        $this->assertTrue(method_exists($field, 'falseValue'));
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_boolean_field(): void
    {
        $field = Boolean::make('Active');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Boolean::class, $field->trueValue('On'));
        $this->assertInstanceOf(Boolean::class, $field->falseValue('Off'));

        // Test component name matches Nova
        $this->assertEquals('BooleanField', $field->component);

        // Test default values match Nova (create fresh field)
        $freshField = Boolean::make('Fresh');
        $this->assertTrue($freshField->trueValue);
        $this->assertFalse($freshField->falseValue);
    }

    /** @test */
    public function it_handles_complex_nova_configuration(): void
    {
        $field = Boolean::make('User Status')
            ->trueValue('active')
            ->falseValue('inactive')
            ->nullable()
            ->help('Toggle user active status')
            ->rules('required');

        // Test all configurations are set correctly
        $this->assertEquals('active', $field->trueValue);
        $this->assertEquals('inactive', $field->falseValue);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Toggle user active status', $field->helpText);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_serializes_boolean_field_for_frontend(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off')
            ->help('Toggle the active status');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Active', $serialized['name']);
        $this->assertEquals('active', $serialized['attribute']);
        $this->assertEquals('BooleanField', $serialized['component']);
        $this->assertEquals('Toggle the active status', $serialized['helpText']);

        // Check meta properties
        $this->assertEquals('On', $serialized['trueValue']);
        $this->assertEquals('Off', $serialized['falseValue']);
    }

    /** @test */
    public function it_handles_boolean_field_with_validation_rules(): void
    {
        $field = Boolean::make('Active')
            ->trueValue(1)
            ->falseValue(0)
            ->rules('required', 'boolean')
            ->nullable(false);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('boolean', $field->rules);
        $this->assertFalse($field->nullable);

        // Test field serialization includes validation rules
        $serialized = $field->jsonSerialize();
        $this->assertEquals(['required', 'boolean'], $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
    }

    /** @test */
    public function it_supports_nova_examples_from_documentation(): void
    {
        // Example from Nova docs: Boolean::make('Active')
        $field1 = Boolean::make('Active');
        $this->assertEquals('Active', $field1->name);
        $this->assertEquals('active', $field1->attribute);
        $this->assertTrue($field1->trueValue);
        $this->assertFalse($field1->falseValue);

        // Example from Nova docs: Boolean::make('Active')->trueValue('On')->falseValue('Off')
        $field2 = Boolean::make('Active')
            ->trueValue('On')
            ->falseValue('Off');

        $this->assertEquals('On', $field2->trueValue);
        $this->assertEquals('Off', $field2->falseValue);
    }

    /** @test */
    public function it_handles_edge_cases_with_null_and_empty_values(): void
    {
        // Test with null values
        $field1 = Boolean::make('Active')
            ->trueValue(null)
            ->falseValue('inactive');

        $this->assertNull($field1->trueValue);
        $this->assertEquals('inactive', $field1->falseValue);

        // Test with empty string values
        $field2 = Boolean::make('Active')
            ->trueValue('')
            ->falseValue('no');

        $this->assertEquals('', $field2->trueValue);
        $this->assertEquals('no', $field2->falseValue);
    }

    /** @test */
    public function it_maintains_backward_compatibility_with_standard_boolean_values(): void
    {
        $field = Boolean::make('Active');

        // Should work with standard true/false values
        $this->assertTrue($field->trueValue);
        $this->assertFalse($field->falseValue);

        // Meta should serialize correctly
        $meta = $field->meta();
        $this->assertTrue($meta['trueValue']);
        $this->assertFalse($meta['falseValue']);
    }

    /** @test */
    public function it_handles_type_coercion_correctly(): void
    {
        // Test that values are stored exactly as provided (no type coercion)
        $field = Boolean::make('Status')
            ->trueValue('1')  // String '1'
            ->falseValue(0);  // Integer 0

        $this->assertSame('1', $field->trueValue);
        $this->assertSame(0, $field->falseValue);

        // Verify types are preserved in meta
        $meta = $field->meta();
        $this->assertSame('1', $meta['trueValue']);
        $this->assertSame(0, $meta['falseValue']);
    }

    /** @test */
    public function it_works_with_all_inherited_field_functionality(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('active')
            ->falseValue('inactive')
            ->nullable()
            ->readonly()
            ->help('User active status')
            ->rules('required');

        // Test inherited functionality works
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
        $this->assertEquals('User active status', $field->helpText);
        $this->assertContains('required', $field->rules);

        // Test Nova-specific functionality still works
        $this->assertEquals('active', $field->trueValue);
        $this->assertEquals('inactive', $field->falseValue);
    }
}
