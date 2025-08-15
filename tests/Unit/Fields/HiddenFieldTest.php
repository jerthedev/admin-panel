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
}
