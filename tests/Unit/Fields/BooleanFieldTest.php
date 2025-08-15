<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Boolean Field Unit Tests
 *
 * Tests for Boolean field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class BooleanFieldTest extends TestCase
{
    public function test_boolean_field_component(): void
    {
        $field = Boolean::make('Active');

        $this->assertEquals('BooleanField', $field->component);
    }

    public function test_boolean_field_values(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('yes')
            ->falseValue('no');

        $this->assertEquals('yes', $field->trueValue);
        $this->assertEquals('no', $field->falseValue);
    }

    public function test_boolean_field_creation(): void
    {
        $field = Boolean::make('Active');

        $this->assertEquals('Active', $field->name);
        $this->assertEquals('active', $field->attribute);
        $this->assertEquals('BooleanField', $field->component);
    }

    public function test_boolean_field_with_custom_attribute(): void
    {
        $field = Boolean::make('Is Active', 'is_active');

        $this->assertEquals('Is Active', $field->name);
        $this->assertEquals('is_active', $field->attribute);
    }

    public function test_boolean_field_default_values(): void
    {
        $field = Boolean::make('Active');

        $this->assertEquals(true, $field->trueValue);
        $this->assertEquals(false, $field->falseValue);
    }

    public function test_boolean_field_true_value_configuration(): void
    {
        $field = Boolean::make('Active')->trueValue('yes');

        $this->assertEquals('yes', $field->trueValue);
    }

    public function test_boolean_field_false_value_configuration(): void
    {
        $field = Boolean::make('Active')->falseValue('no');

        $this->assertEquals('no', $field->falseValue);
    }

    public function test_boolean_field_fill_converts_to_boolean(): void
    {
        $field = Boolean::make('Active');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['active' => '1']);

        $field->fill($request, $model);

        $this->assertTrue($model->active);
    }

    public function test_boolean_field_fill_handles_false_value(): void
    {
        $field = Boolean::make('Active');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['active' => '0']);

        $field->fill($request, $model);

        $this->assertFalse($model->active);
    }

    public function test_boolean_field_fill_with_custom_values(): void
    {
        $field = Boolean::make('Published')
            ->trueValue('yes')
            ->falseValue('no');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['published' => 'yes']);

        $field->fill($request, $model);

        $this->assertEquals('yes', $model->published);
    }

    public function test_boolean_field_fill_handles_empty_value(): void
    {
        $field = Boolean::make('Active');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['active' => '']);

        $field->fill($request, $model);

        $this->assertFalse($model->active);
    }

    public function test_boolean_field_fill_with_callback(): void
    {
        $field = Boolean::make('Active')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'custom-value';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['active' => '1']);

        $field->fill($request, $model);

        $this->assertEquals('custom-value', $model->active);
    }

    public function test_boolean_field_resolve_with_custom_values(): void
    {
        $field = Boolean::make('Published')
            ->trueValue('yes')
            ->falseValue('no');
        $resource = (object) ['published' => 'yes'];

        $field->resolve($resource);

        // Boolean field resolve converts to array with value and display
        $this->assertIsArray($field->value);
        $this->assertTrue($field->value['value']); // Should be true since 'yes' == trueValue
        $this->assertEquals('Yes', $field->value['display']); // Default trueText
    }

    public function test_boolean_field_meta_includes_all_properties(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('yes')
            ->falseValue('no');

        $meta = $field->meta();

        $this->assertArrayHasKey('trueValue', $meta);
        $this->assertArrayHasKey('falseValue', $meta);
        $this->assertEquals('yes', $meta['trueValue']);
        $this->assertEquals('no', $meta['falseValue']);
    }

    public function test_boolean_field_values_method(): void
    {
        $field = Boolean::make('Active')->values('enabled', 'disabled');

        $this->assertEquals('enabled', $field->trueValue);
        $this->assertEquals('disabled', $field->falseValue);
    }

    public function test_boolean_field_labels_method(): void
    {
        $field = Boolean::make('Active')->labels('Enabled', 'Disabled');

        $this->assertEquals('Enabled', $field->trueText);
        $this->assertEquals('Disabled', $field->falseText);
    }

    public function test_boolean_field_as_toggle_method(): void
    {
        $field = Boolean::make('Active')->asToggle();

        $this->assertTrue($field->asToggle);
    }

    public function test_boolean_field_as_toggle_false(): void
    {
        $field = Boolean::make('Active')->asToggle(false);

        $this->assertFalse($field->asToggle);
    }

    public function test_boolean_field_as_checkbox_method(): void
    {
        $field = Boolean::make('Active')->asCheckbox();

        $this->assertFalse($field->asToggle);
    }

    public function test_boolean_field_display_as_switch(): void
    {
        $field = Boolean::make('Active')->displayAsSwitch();

        $this->assertEquals('switch', $field->displayMode);
        $this->assertTrue($field->asToggle);
    }

    public function test_boolean_field_display_as_button(): void
    {
        $field = Boolean::make('Active')->displayAsButton();

        $this->assertEquals('button', $field->displayMode);
        $this->assertFalse($field->asToggle);
    }

    public function test_boolean_field_display_as_checkbox(): void
    {
        $field = Boolean::make('Active')->displayAsCheckbox();

        $this->assertEquals('checkbox', $field->displayMode);
        $this->assertFalse($field->asToggle);
    }

    public function test_boolean_field_color_method(): void
    {
        $field = Boolean::make('Active')->color('success');

        $this->assertEquals('success', $field->color);
    }

    public function test_boolean_field_size_method(): void
    {
        $field = Boolean::make('Active')->size('large');

        $this->assertEquals('large', $field->size);
    }

    public function test_boolean_field_resolve_display_value(): void
    {
        $field = Boolean::make('Active')->labels('Enabled', 'Disabled');

        $this->assertEquals('Enabled', $field->resolveDisplayValue(true));
        $this->assertEquals('Disabled', $field->resolveDisplayValue(false));
    }

    public function test_boolean_field_comprehensive_meta_properties(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('yes')
            ->falseValue('no')
            ->labels('Enabled', 'Disabled')
            ->displayAsSwitch()
            ->color('success')
            ->size('large');

        $meta = $field->meta();

        $this->assertArrayHasKey('trueValue', $meta);
        $this->assertArrayHasKey('falseValue', $meta);
        $this->assertArrayHasKey('trueText', $meta);
        $this->assertArrayHasKey('falseText', $meta);
        $this->assertArrayHasKey('asToggle', $meta);
        $this->assertArrayHasKey('displayMode', $meta);
        $this->assertArrayHasKey('color', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertEquals('yes', $meta['trueValue']);
        $this->assertEquals('no', $meta['falseValue']);
        $this->assertEquals('Enabled', $meta['trueText']);
        $this->assertEquals('Disabled', $meta['falseText']);
        $this->assertTrue($meta['asToggle']);
        $this->assertEquals('switch', $meta['displayMode']);
        $this->assertEquals('success', $meta['color']);
        $this->assertEquals('large', $meta['size']);
    }

    public function test_boolean_field_json_serialization(): void
    {
        $field = Boolean::make('Is Published')
            ->trueValue('active')
            ->falseValue('inactive')
            ->required()
            ->help('Toggle publication status');

        $json = $field->jsonSerialize();

        $this->assertEquals('Is Published', $json['name']);
        $this->assertEquals('is_published', $json['attribute']);
        $this->assertEquals('BooleanField', $json['component']);
        $this->assertEquals('active', $json['trueValue']);
        $this->assertEquals('inactive', $json['falseValue']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Toggle publication status', $json['helpText']);
    }
}
