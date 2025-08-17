<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\ID;
use JTD\AdminPanel\Tests\TestCase;

/**
 * ID Field Unit Tests.
 *
 * Tests for ID field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class IDFieldTest extends TestCase
{
    public function test_id_field_creation(): void
    {
        $field = ID::make();

        $this->assertEquals('ID', $field->name);
        $this->assertEquals('id', $field->attribute);
        $this->assertEquals('IDField', $field->component);
    }

    public function test_id_field_with_custom_name(): void
    {
        $field = ID::make('User ID');

        $this->assertEquals('User ID', $field->name);
        $this->assertEquals('id', $field->attribute); // Still defaults to 'id'
    }

    public function test_id_field_with_custom_attribute(): void
    {
        $field = ID::make('User ID', 'user_id');

        $this->assertEquals('User ID', $field->name);
        $this->assertEquals('user_id', $field->attribute);
    }

    public function test_id_field_defaults_to_id_attribute(): void
    {
        $field = ID::make('Primary Key');

        $this->assertEquals('id', $field->attribute);
    }

    public function test_id_field_is_sortable_by_default(): void
    {
        $field = ID::make();

        $this->assertTrue($field->sortable);
    }

    public function test_id_field_hidden_from_creation_by_default(): void
    {
        $field = ID::make();

        $this->assertFalse($field->showOnCreation);
    }

    public function test_id_field_shown_on_other_views_by_default(): void
    {
        $field = ID::make();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_id_field_can_be_shown_on_creation(): void
    {
        $field = ID::make()->showOnCreating();

        $this->assertTrue($field->showOnCreation);
    }

    public function test_id_field_can_be_made_non_sortable(): void
    {
        $field = ID::make()->sortable(false);

        $this->assertFalse($field->sortable);
    }

    public function test_id_field_with_resolve_callback(): void
    {
        $field = ID::make('ID', null, function ($resource, $attribute) {
            return 'ID-'.$resource->{$attribute};
        });

        $resource = (object) ['id' => 123];
        $field->resolve($resource);

        $this->assertEquals('ID-123', $field->value);
    }

    public function test_id_field_static_make_method(): void
    {
        $field = ID::make('Custom ID', 'custom_id');

        $this->assertInstanceOf(ID::class, $field);
        $this->assertEquals('Custom ID', $field->name);
        $this->assertEquals('custom_id', $field->attribute);
    }

    public function test_id_field_meta_method(): void
    {
        $field = ID::make();

        $meta = $field->meta();

        $this->assertIsArray($meta);
        // ID field doesn't add specific meta, but inherits from parent
        // The meta() method returns additional meta, not the full field data
    }

    public function test_id_field_json_serialization(): void
    {
        $field = ID::make('User ID', 'user_id')
            ->sortable()
            ->copyable()
            ->help('Unique identifier');

        $json = $field->jsonSerialize();

        $this->assertEquals('User ID', $json['name']);
        $this->assertEquals('user_id', $json['attribute']);
        $this->assertEquals('IDField', $json['component']);
        $this->assertTrue($json['sortable']);
        $this->assertTrue($json['copyable']);
        $this->assertEquals('Unique identifier', $json['helpText']);
        $this->assertFalse($json['showOnCreation']);
        $this->assertTrue($json['showOnIndex']);
        $this->assertTrue($json['showOnDetail']);
        $this->assertTrue($json['showOnUpdate']);
    }

    public function test_id_field_with_validation_rules(): void
    {
        $field = ID::make()->rules('required', 'integer', 'min:1');

        $this->assertEquals(['required', 'integer', 'min:1'], $field->rules);
    }

    public function test_id_field_with_nullable(): void
    {
        $field = ID::make()->nullable();

        $this->assertTrue($field->nullable);
    }

    public function test_id_field_with_readonly(): void
    {
        $field = ID::make()->readonly();

        $this->assertTrue($field->readonly);
    }

    public function test_id_field_inheritance_from_field(): void
    {
        $field = ID::make();

        // Test that ID field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'sortable'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'hideFromIndex'));
        $this->assertTrue(method_exists($field, 'showOnDetail'));
        $this->assertTrue(method_exists($field, 'copyable'));
        $this->assertTrue(method_exists($field, 'help'));
    }

    public function test_id_field_can_override_default_visibility(): void
    {
        $field = ID::make()
            ->hideFromIndex()
            ->hideFromDetail()
            ->showOnCreating();

        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
    }

    public function test_id_field_with_default_value(): void
    {
        $field = ID::make()->default(999);

        $this->assertEquals(999, $field->default);
    }

    public function test_id_field_resolves_value_correctly(): void
    {
        $field = ID::make();
        $resource = (object) ['id' => 42];

        $field->resolve($resource);

        $this->assertEquals(42, $field->value);
    }

    public function test_id_field_resolves_custom_attribute(): void
    {
        $field = ID::make('UUID', 'uuid');
        $resource = (object) ['uuid' => 'abc-123-def'];

        $field->resolve($resource);

        $this->assertEquals('abc-123-def', $field->value);
    }

    public function test_id_field_as_big_int_default(): void
    {
        $field = ID::make();

        $this->assertFalse($field->asBigInt);
    }

    public function test_id_field_as_big_int_enabled(): void
    {
        $field = ID::make()->asBigInt();

        $this->assertTrue($field->asBigInt);
    }

    public function test_id_field_as_big_int_disabled(): void
    {
        $field = ID::make()->asBigInt(false);

        $this->assertFalse($field->asBigInt);
    }

    public function test_id_field_as_big_int_method_returns_field(): void
    {
        $field = ID::make();
        $result = $field->asBigInt();

        $this->assertSame($field, $result);
    }

    public function test_id_field_as_big_int_in_meta(): void
    {
        $field = ID::make()->asBigInt();
        $meta = $field->meta();

        $this->assertArrayHasKey('asBigInt', $meta);
        $this->assertTrue($meta['asBigInt']);
    }

    public function test_id_field_as_big_int_false_in_meta(): void
    {
        $field = ID::make()->asBigInt(false);
        $meta = $field->meta();

        $this->assertArrayHasKey('asBigInt', $meta);
        $this->assertFalse($meta['asBigInt']);
    }

    public function test_id_field_as_big_int_in_json_serialization(): void
    {
        $field = ID::make()->asBigInt();
        $json = $field->jsonSerialize();

        // asBigInt should be directly in the JSON (merged from meta)
        $this->assertArrayHasKey('asBigInt', $json);
        $this->assertTrue($json['asBigInt']);
    }

    public function test_id_field_nova_api_compatibility(): void
    {
        // Test all Nova API methods exist and work correctly
        $field = ID::make('ID', 'id_column')->asBigInt();

        // Basic Nova API
        $this->assertEquals('ID', $field->name);
        $this->assertEquals('id_column', $field->attribute);
        $this->assertTrue($field->asBigInt);

        // Default behaviors match Nova
        $this->assertTrue($field->sortable);
        $this->assertFalse($field->showOnCreation);
        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_id_field_constructor_with_all_parameters(): void
    {
        $callback = function ($resource, $attribute) {
            return 'test-'.$resource->{$attribute};
        };

        $field = new ID('Test ID', 'test_id', $callback);

        $this->assertEquals('Test ID', $field->name);
        $this->assertEquals('test_id', $field->attribute);
        $this->assertSame($callback, $field->resolveCallback);
        $this->assertTrue($field->sortable);
        $this->assertFalse($field->showOnCreation);
    }

    public function test_id_field_make_with_all_parameters(): void
    {
        $callback = function ($resource, $attribute) {
            return 'make-'.$resource->{$attribute};
        };

        $field = ID::make('Make ID', 'make_id', $callback);

        $this->assertEquals('Make ID', $field->name);
        $this->assertEquals('make_id', $field->attribute);
        $this->assertSame($callback, $field->resolveCallback);
    }

    public function test_id_field_meta_includes_parent_meta(): void
    {
        $field = ID::make()->help('Test help')->asBigInt();
        $meta = $field->meta();

        // Should include parent meta
        $this->assertIsArray($meta);
        // Should include ID-specific meta
        $this->assertArrayHasKey('asBigInt', $meta);
        $this->assertTrue($meta['asBigInt']);
    }
}
