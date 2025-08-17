<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Hidden;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Hidden Field Unit Tests
 *
 * Tests for Hidden field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HiddenFieldTest extends TestCase
{
    public function test_hidden_field_creation(): void
    {
        $field = Hidden::make('Token');

        $this->assertEquals('Token', $field->name);
        $this->assertEquals('token', $field->attribute);
        $this->assertEquals('HiddenField', $field->component);
    }

    public function test_hidden_field_with_custom_attribute(): void
    {
        $field = Hidden::make('CSRF Token', 'csrf_token');

        $this->assertEquals('CSRF Token', $field->name);
        $this->assertEquals('csrf_token', $field->attribute);
    }

    public function test_hidden_field_default_visibility(): void
    {
        $field = Hidden::make('Token');

        // Hidden fields should not be shown on index or detail by default
        $this->assertFalse($field->isShownOnIndex());
        $this->assertFalse($field->isShownOnDetail());

        // But should be included in forms
        $this->assertTrue($field->isShownOnForms());
    }

    public function test_hidden_field_with_default_value(): void
    {
        $field = Hidden::make('Type')->default('user');

        $this->assertEquals('user', $field->default);
    }

    public function test_hidden_field_constructor_sets_visibility(): void
    {
        $field = Hidden::make('Token');

        $this->assertFalse($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    public function test_hidden_field_meta_includes_visibility_settings(): void
    {
        $field = Hidden::make('Token');

        $meta = $field->meta();

        $this->assertArrayHasKey('showOnIndex', $meta);
        $this->assertArrayHasKey('showOnDetail', $meta);
        $this->assertArrayHasKey('showOnCreation', $meta);
        $this->assertArrayHasKey('showOnUpdate', $meta);
        $this->assertFalse($meta['showOnIndex']);
        $this->assertFalse($meta['showOnDetail']);
        $this->assertTrue($meta['showOnCreation']);
        $this->assertTrue($meta['showOnUpdate']);
    }

    public function test_hidden_field_can_override_visibility(): void
    {
        $field = Hidden::make('Token')
            ->showOnIndex()
            ->showOnDetail();

        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
    }

    public function test_hidden_field_json_serialization(): void
    {
        $field = Hidden::make('CSRF Token', 'csrf_token')
            ->default('abc123')
            ->required();

        $json = $field->jsonSerialize();

        $this->assertEquals('CSRF Token', $json['name']);
        $this->assertEquals('csrf_token', $json['attribute']);
        $this->assertEquals('HiddenField', $json['component']);
        $this->assertEquals('abc123', $json['default']);
        $this->assertContains('required', $json['rules']);
        $this->assertFalse($json['showOnIndex']);
        $this->assertFalse($json['showOnDetail']);
        $this->assertTrue($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    public function test_hidden_field_with_callable_default(): void
    {
        $field = Hidden::make('User ID', 'user_id')
            ->default(function ($request) {
                return 123;
            });

        $this->assertIsCallable($field->default);
    }

    public function test_hidden_field_resolves_callable_default(): void
    {
        $field = Hidden::make('User ID', 'user_id')
            ->default(function ($request) {
                return 456;
            });

        $resource = (object) ['user_id' => null];
        $resolvedValue = $field->resolveValue($resource);

        $this->assertEquals(456, $resolvedValue);
    }

    public function test_hidden_field_callable_default_receives_request(): void
    {
        $requestReceived = null;
        $field = Hidden::make('User ID', 'user_id')
            ->default(function ($request) use (&$requestReceived) {
                $requestReceived = $request;
                return $request ? 'has-request' : 'no-request';
            });

        $resource = (object) ['user_id' => null];
        $resolvedValue = $field->resolveValue($resource);

        $this->assertEquals('has-request', $resolvedValue);
        $this->assertInstanceOf(\Illuminate\Http\Request::class, $requestReceived);
    }

    public function test_hidden_field_json_serialization_with_callable_default(): void
    {
        $field = Hidden::make('Token', 'token')
            ->default(function ($request) {
                return 'generated-token';
            });

        $json = $field->jsonSerialize();

        // Callable default should be resolved for JSON serialization
        $this->assertEquals('generated-token', $json['default']);
    }

    public function test_hidden_field_fill_with_callable_default(): void
    {
        $field = Hidden::make('User ID', 'user_id')
            ->default(function ($request) {
                return 789;
            });

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(); // No user_id in request

        $field->fill($request, $model);

        $this->assertEquals(789, $model->user_id);
    }

    public function test_hidden_field_fill_request_value_overrides_default(): void
    {
        $field = Hidden::make('User ID', 'user_id')
            ->default(function ($request) {
                return 999;
            });

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['user_id' => 555]);

        $field->fill($request, $model);

        // Request value should override default
        $this->assertEquals(555, $model->user_id);
    }

    public function test_hidden_field_fill_with_fill_callback(): void
    {
        $field = Hidden::make('User ID', 'user_id')
            ->fillUsing(function ($request, $model, $attribute) {
                $model->{$attribute} = 'custom-fill';
            });

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['user_id' => 123]);

        $field->fill($request, $model);

        $this->assertEquals('custom-fill', $model->user_id);
    }

    public function test_hidden_field_resolve_value_with_existing_value(): void
    {
        $field = Hidden::make('User ID', 'user_id')
            ->default(function ($request) {
                return 999;
            });

        $resource = (object) ['user_id' => 123];
        $resolvedValue = $field->resolveValue($resource);

        // Existing value should be used, not default
        $this->assertEquals(123, $resolvedValue);
    }

    public function test_hidden_field_nova_api_compatibility(): void
    {
        // Test Nova-style usage patterns

        // Basic usage
        $field1 = Hidden::make('Slug');
        $this->assertEquals('Slug', $field1->name);
        $this->assertEquals('slug', $field1->attribute);

        // With default value
        $field2 = Hidden::make('Slug')->default('random-string');
        $this->assertEquals('random-string', $field2->default);

        // With callable default (Nova style)
        $field3 = Hidden::make('User', 'user_id')->default(function ($request) {
            return $request->user()->id ?? 1;
        });
        $this->assertIsCallable($field3->default);

        // Test component name matches Nova
        $this->assertEquals('HiddenField', $field1->component);
    }

    public function test_hidden_field_resolve_default_value_non_callable(): void
    {
        $field = Hidden::make('Token', 'token')->default('static-value');

        $resource = (object) ['token' => null];
        $resolvedValue = $field->resolveValue($resource);

        $this->assertEquals('static-value', $resolvedValue);
    }

    public function test_hidden_field_fill_with_null_default(): void
    {
        $field = Hidden::make('Optional', 'optional')->default(null);

        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(); // No value in request

        $field->fill($request, $model);

        // Should not set the attribute when default is null
        $this->assertFalse(property_exists($model, 'optional'));
    }

    public function test_hidden_field_meta_method(): void
    {
        $field = Hidden::make('Token');

        $meta = $field->meta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('showOnIndex', $meta);
        $this->assertArrayHasKey('showOnDetail', $meta);
        $this->assertArrayHasKey('showOnCreation', $meta);
        $this->assertArrayHasKey('showOnUpdate', $meta);
        $this->assertFalse($meta['showOnIndex']);
        $this->assertFalse($meta['showOnDetail']);
        $this->assertTrue($meta['showOnCreation']);
        $this->assertTrue($meta['showOnUpdate']);
    }
}
