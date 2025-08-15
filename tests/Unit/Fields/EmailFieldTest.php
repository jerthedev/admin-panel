<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Email;
use PHPUnit\Framework\TestCase;

/**
 * Email Field Unit Tests
 *
 * Tests for Email field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class EmailFieldTest extends TestCase
{
    public function test_email_field_component(): void
    {
        $field = Email::make('Email');

        $this->assertEquals('EmailField', $field->component);
    }

    public function test_email_field_creation(): void
    {
        $field = Email::make('Email Address');

        $this->assertEquals('Email Address', $field->name);
        $this->assertEquals('email_address', $field->attribute);
        $this->assertEquals('EmailField', $field->component);
    }

    public function test_email_field_with_custom_attribute(): void
    {
        $field = Email::make('Email Address', 'email');

        $this->assertEquals('Email Address', $field->name);
        $this->assertEquals('email', $field->attribute);
    }

    public function test_email_field_has_default_email_validation(): void
    {
        $field = Email::make('Email');

        $this->assertContains('email', $field->rules);
    }

    public function test_email_field_clickable_default(): void
    {
        $field = Email::make('Email');

        $this->assertTrue($field->clickable);
    }

    public function test_email_field_clickable_configuration(): void
    {
        $field = Email::make('Email')->clickable(false);

        $this->assertFalse($field->clickable);
    }

    public function test_email_field_clickable_method_chaining(): void
    {
        $field = Email::make('Email')->clickable(true);

        $this->assertInstanceOf(Email::class, $field);
        $this->assertTrue($field->clickable);
    }

    public function test_email_field_fill_normalizes_email(): void
    {
        $field = Email::make('Email');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['email' => '  JOHN@EXAMPLE.COM  ']);

        $field->fill($request, $model);

        $this->assertEquals('john@example.com', $model->email);
    }

    public function test_email_field_fill_with_null_value(): void
    {
        $field = Email::make('Email');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['email' => null]);

        $field->fill($request, $model);

        $this->assertNull($model->email);
    }

    public function test_email_field_fill_with_non_string_value(): void
    {
        $field = Email::make('Email');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['email' => 123]);

        $field->fill($request, $model);

        $this->assertEquals(123, $model->email);
    }

    public function test_email_field_fill_with_empty_string(): void
    {
        $field = Email::make('Email');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['email' => '']);

        $field->fill($request, $model);

        $this->assertEquals('', $model->email);
    }

    public function test_email_field_fill_with_callback(): void
    {
        $field = Email::make('Email')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'custom@example.com';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['email' => 'test@example.com']);

        $field->fill($request, $model);

        $this->assertEquals('custom@example.com', $model->email);
    }

    public function test_email_field_fill_without_request_value(): void
    {
        $field = Email::make('Email');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request([]);

        $field->fill($request, $model);

        $this->assertObjectNotHasProperty('email', $model);
    }

    public function test_email_field_meta_includes_clickable(): void
    {
        $field = Email::make('Email')->clickable(false);

        $meta = $field->meta();

        $this->assertArrayHasKey('clickable', $meta);
        $this->assertFalse($meta['clickable']);
    }

    public function test_email_field_meta_with_default_clickable(): void
    {
        $field = Email::make('Email');

        $meta = $field->meta();

        $this->assertArrayHasKey('clickable', $meta);
        $this->assertTrue($meta['clickable']);
    }

    public function test_email_field_constructor_adds_email_validation(): void
    {
        $field = Email::make('Email');

        // Constructor should automatically add email validation
        $this->assertContains('email', $field->rules);
    }

    public function test_email_field_constructor_with_resolve_callback(): void
    {
        $callback = function ($resource, $attribute) {
            return strtoupper($resource->{$attribute});
        };

        $field = Email::make('Email', null, $callback);

        $this->assertEquals($callback, $field->resolveCallback);
        $this->assertContains('email', $field->rules);
    }

    public function test_email_field_additional_validation_rules(): void
    {
        $field = Email::make('Email')
            ->rules('email', 'required', 'unique:users,email');

        // When rules() is called, it replaces the existing rules
        // So we need to include all rules in the rules() call
        $this->assertContains('email', $field->rules);
        $this->assertContains('required', $field->rules);
        $this->assertContains('unique:users,email', $field->rules);
    }

    public function test_email_field_complex_configuration(): void
    {
        $field = Email::make('Contact Email')
            ->clickable(false)
            ->nullable()
            ->help('Enter your contact email address')
            ->placeholder('user@example.com');

        $this->assertEquals('Contact Email', $field->name);
        $this->assertEquals('contact_email', $field->attribute);
        $this->assertFalse($field->clickable);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Enter your contact email address', $field->helpText);
        $this->assertEquals('user@example.com', $field->placeholder);
        $this->assertContains('email', $field->rules);
    }

    public function test_email_field_json_serialization(): void
    {
        $field = Email::make('Email Address')
            ->clickable(false)
            ->required()
            ->sortable();

        $json = $field->jsonSerialize();

        $this->assertEquals('Email Address', $json['name']);
        $this->assertEquals('email_address', $json['attribute']);
        $this->assertEquals('EmailField', $json['component']);
        $this->assertFalse($json['clickable']);
        $this->assertContains('required', $json['rules']);
        $this->assertContains('email', $json['rules']);
        $this->assertTrue($json['sortable']);
    }

    public function test_email_field_comprehensive_method_coverage(): void
    {
        $field = Email::make('Email');

        // Test that all public methods exist and can be called
        $this->assertTrue(method_exists($field, 'clickable'));
        $this->assertTrue(method_exists($field, 'fill'));
        $this->assertTrue(method_exists($field, 'meta'));

        // Test method chaining
        $result = $field->clickable(true);
        $this->assertInstanceOf(Email::class, $result);
    }
}
