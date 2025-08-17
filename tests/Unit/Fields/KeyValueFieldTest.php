<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\KeyValue;
use JTD\AdminPanel\Tests\TestCase;

/**
 * KeyValue Field Unit Tests
 *
 * Tests for KeyValue field class with 100% Nova API compatibility.
 * Tests all Nova KeyValue field features including keyLabel, valueLabel, and actionText methods.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class KeyValueFieldTest extends TestCase
{
    /** @test */
    public function it_creates_keyvalue_field_with_nova_syntax(): void
    {
        $field = KeyValue::make('Meta');

        $this->assertEquals('Meta', $field->name);
        $this->assertEquals('meta', $field->attribute);
        $this->assertEquals('KeyValueField', $field->component);
    }

    /** @test */
    public function it_creates_keyvalue_field_with_custom_attribute(): void
    {
        $field = KeyValue::make('Settings', 'user_settings');

        $this->assertEquals('Settings', $field->name);
        $this->assertEquals('user_settings', $field->attribute);
        $this->assertEquals('KeyValueField', $field->component);
    }

    /** @test */
    public function it_has_default_labels_and_action_text(): void
    {
        $field = KeyValue::make('Meta');

        $this->assertEquals('Key', $field->keyLabel);
        $this->assertEquals('Value', $field->valueLabel);
        $this->assertEquals('Add row', $field->actionText);
    }

    /** @test */
    public function it_can_customize_key_label(): void
    {
        $field = KeyValue::make('Meta')->keyLabel('Property');

        $this->assertEquals('Property', $field->keyLabel);
    }

    /** @test */
    public function it_can_customize_value_label(): void
    {
        $field = KeyValue::make('Meta')->valueLabel('Content');

        $this->assertEquals('Content', $field->valueLabel);
    }

    /** @test */
    public function it_can_customize_action_text(): void
    {
        $field = KeyValue::make('Meta')->actionText('Add new item');

        $this->assertEquals('Add new item', $field->actionText);
    }

    /** @test */
    public function it_can_chain_customization_methods(): void
    {
        $field = KeyValue::make('Meta')
            ->keyLabel('Property')
            ->valueLabel('Content')
            ->actionText('Add new item');

        $this->assertEquals('Property', $field->keyLabel);
        $this->assertEquals('Content', $field->valueLabel);
        $this->assertEquals('Add new item', $field->actionText);
    }

    /** @test */
    public function it_includes_labels_in_meta(): void
    {
        $field = KeyValue::make('Meta')
            ->keyLabel('Property')
            ->valueLabel('Content')
            ->actionText('Add new item');

        $meta = $field->meta();

        $this->assertEquals('Property', $meta['keyLabel']);
        $this->assertEquals('Content', $meta['valueLabel']);
        $this->assertEquals('Add new item', $meta['actionText']);
    }

    /** @test */
    public function it_resolves_associative_array_to_key_value_pairs(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [
            'meta' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => '30',
            ],
        ];

        $field->resolve($model);

        $expected = [
            ['key' => 'name', 'value' => 'John Doe'],
            ['key' => 'email', 'value' => 'john@example.com'],
            ['key' => 'age', 'value' => '30'],
        ];

        $this->assertEquals($expected, $field->value);
    }

    /** @test */
    public function it_resolves_empty_array_when_value_is_null(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) ['meta' => null];

        $field->resolve($model);

        $this->assertEquals([], $field->value);
    }

    /** @test */
    public function it_resolves_empty_array_when_value_is_not_array(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) ['meta' => 'not an array'];

        $field->resolve($model);

        $this->assertEquals([], $field->value);
    }

    /** @test */
    public function it_fills_model_from_key_value_pairs(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];
        $request = new Request([
            'meta' => [
                ['key' => 'name', 'value' => 'John Doe'],
                ['key' => 'email', 'value' => 'john@example.com'],
                ['key' => 'age', 'value' => '30'],
            ],
        ]);

        $field->fill($request, $model);

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => '30',
        ];

        $this->assertEquals($expected, $model->meta);
    }

    /** @test */
    public function it_ignores_empty_keys_when_filling(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];
        $request = new Request([
            'meta' => [
                ['key' => 'name', 'value' => 'John Doe'],
                ['key' => '', 'value' => 'should be ignored'],
                ['key' => '   ', 'value' => 'should also be ignored'],
                ['key' => 'email', 'value' => 'john@example.com'],
            ],
        ]);

        $field->fill($request, $model);

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->assertEquals($expected, $model->meta);
    }

    /** @test */
    public function it_handles_malformed_input_gracefully(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];
        $request = new Request([
            'meta' => [
                ['key' => 'name', 'value' => 'John Doe'],
                ['key' => 'incomplete'], // Missing value
                'invalid_structure',
                ['value' => 'missing key'], // Missing key
                ['key' => 'email', 'value' => 'john@example.com'],
            ],
        ]);

        $field->fill($request, $model);

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->assertEquals($expected, $model->meta);
    }

    /** @test */
    public function it_handles_non_array_input_when_filling(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];
        $request = new Request(['meta' => 'not an array']);

        $field->fill($request, $model);

        $this->assertEquals([], $model->meta);
    }

    /** @test */
    public function it_handles_missing_input_when_filling(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];
        $request = new Request([]);

        $field->fill($request, $model);

        $this->assertEquals([], $model->meta);
    }

    /** @test */
    public function it_uses_fill_callback_when_provided(): void
    {
        $callbackCalled = false;
        $field = KeyValue::make('Meta')->fillUsing(function ($request, $model, $attribute) use (&$callbackCalled) {
            $callbackCalled = true;
            $model->{$attribute} = ['custom' => 'value'];
        });

        $model = (object) [];
        $request = new Request(['meta' => [['key' => 'test', 'value' => 'data']]]);

        $field->fill($request, $model);

        $this->assertTrue($callbackCalled);
        $this->assertEquals(['custom' => 'value'], $model->meta);
    }

    /** @test */
    public function it_provides_display_value(): void
    {
        $field = KeyValue::make('Meta');
        $field->value = [
            ['key' => 'name', 'value' => 'John Doe'],
            ['key' => 'email', 'value' => 'john@example.com'],
        ];

        $displayValue = $field->getDisplayValue();

        $expected = [
            ['key' => 'name', 'value' => 'John Doe'],
            ['key' => 'email', 'value' => 'john@example.com'],
        ];

        $this->assertEquals($expected, $displayValue);
    }

    /** @test */
    public function it_checks_if_has_display_values(): void
    {
        $field = KeyValue::make('Meta');

        // Empty value
        $field->value = [];
        $this->assertFalse($field->hasDisplayValues());

        // With values
        $field->value = [['key' => 'name', 'value' => 'John Doe']];
        $this->assertTrue($field->hasDisplayValues());
    }

    /** @test */
    public function it_serializes_to_json_with_nova_compatibility(): void
    {
        $field = KeyValue::make('Meta')
            ->keyLabel('Property')
            ->valueLabel('Content')
            ->actionText('Add new item')
            ->help('Enter key-value pairs')
            ->rules('json');

        $json = $field->jsonSerialize();

        $this->assertEquals('KeyValueField', $json['component']);
        $this->assertEquals('Meta', $json['name']);
        $this->assertEquals('meta', $json['attribute']);
        $this->assertEquals('Enter key-value pairs', $json['helpText']);
        $this->assertEquals(['json'], $json['rules']);
        $this->assertEquals('Property', $json['keyLabel']);
        $this->assertEquals('Content', $json['valueLabel']);
        $this->assertEquals('Add new item', $json['actionText']);
    }

    /** @test */
    public function it_supports_fill_attribute_from_request_method(): void
    {
        $field = KeyValue::make('Meta');
        $model = (object) [];
        $request = new Request([
            'meta' => [
                ['key' => 'name', 'value' => 'John Doe'],
                ['key' => 'email', 'value' => 'john@example.com'],
            ],
        ]);

        $field->fillAttributeFromRequest($request, 'meta', $model, 'meta');

        $expected = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        $this->assertEquals($expected, $model->meta);
    }
}
