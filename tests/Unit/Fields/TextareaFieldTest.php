<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Textarea;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Textarea Field Unit Tests
 *
 * Tests for Textarea field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TextareaFieldTest extends TestCase
{
    public function test_textarea_field_creation(): void
    {
        $field = Textarea::make('Description');

        $this->assertEquals('Description', $field->name);
        $this->assertEquals('description', $field->attribute);
        $this->assertEquals('TextareaField', $field->component);
    }

    public function test_textarea_field_with_custom_attribute(): void
    {
        $field = Textarea::make('Bio', 'biography');

        $this->assertEquals('Bio', $field->name);
        $this->assertEquals('biography', $field->attribute);
    }

    public function test_textarea_field_default_rows(): void
    {
        $field = Textarea::make('Description');

        $this->assertEquals(4, $field->rows);
    }

    public function test_textarea_field_rows_configuration(): void
    {
        $field = Textarea::make('Description')->rows(8);

        $this->assertEquals(8, $field->rows);
    }

    public function test_textarea_field_max_length(): void
    {
        $field = Textarea::make('Description')->maxLength(500);

        $this->assertEquals(500, $field->maxLength);
        $this->assertTrue($field->showCharacterCount);
    }

    public function test_textarea_field_auto_resize(): void
    {
        $field = Textarea::make('Description')->autoResize();

        $this->assertTrue($field->autoResize);
    }

    public function test_textarea_field_auto_resize_false(): void
    {
        $field = Textarea::make('Description')->autoResize(false);

        $this->assertFalse($field->autoResize);
    }

    public function test_textarea_field_show_character_count(): void
    {
        $field = Textarea::make('Description')->showCharacterCount();

        $this->assertTrue($field->showCharacterCount);
    }

    public function test_textarea_field_show_character_count_false(): void
    {
        $field = Textarea::make('Description')->showCharacterCount(false);

        $this->assertFalse($field->showCharacterCount);
    }

    public function test_textarea_field_always_show(): void
    {
        $field = Textarea::make('Description')->alwaysShow();

        $this->assertTrue($field->alwaysShow);
    }

    public function test_textarea_field_always_show_false(): void
    {
        $field = Textarea::make('Description')->alwaysShow(false);

        $this->assertFalse($field->alwaysShow);
    }

    public function test_textarea_field_fill_normalizes_line_endings(): void
    {
        $field = Textarea::make('Description');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['description' => "  Line 1\r\nLine 2\rLine 3\n  "]);

        $field->fill($request, $model);

        $this->assertEquals("Line 1\nLine 2\nLine 3", $model->description);
    }

    public function test_textarea_field_fill_with_callback(): void
    {
        $field = Textarea::make('Description')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = strtoupper($request->input($attribute));
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['description' => 'test description']);

        $field->fill($request, $model);

        $this->assertEquals('TEST DESCRIPTION', $model->description);
    }

    public function test_textarea_field_meta_includes_all_properties(): void
    {
        $field = Textarea::make('Description')
            ->rows(6)
            ->maxLength(1000)
            ->autoResize()
            ->alwaysShow();

        $meta = $field->meta();

        $this->assertArrayHasKey('rows', $meta);
        $this->assertArrayHasKey('maxLength', $meta);
        $this->assertArrayHasKey('autoResize', $meta);
        $this->assertArrayHasKey('showCharacterCount', $meta);
        $this->assertArrayHasKey('alwaysShow', $meta);
        $this->assertEquals(6, $meta['rows']);
        $this->assertEquals(1000, $meta['maxLength']);
        $this->assertTrue($meta['autoResize']);
        $this->assertTrue($meta['showCharacterCount']);
        $this->assertTrue($meta['alwaysShow']);
    }

    public function test_textarea_field_json_serialization(): void
    {
        $field = Textarea::make('Bio')
            ->rows(5)
            ->maxLength(500)
            ->autoResize()
            ->alwaysShow()
            ->required()
            ->nullable();

        $json = $field->jsonSerialize();

        $this->assertEquals('Bio', $json['name']);
        $this->assertEquals('bio', $json['attribute']);
        $this->assertEquals('TextareaField', $json['component']);
        $this->assertEquals(5, $json['rows']);
        $this->assertEquals(500, $json['maxLength']);
        $this->assertTrue($json['autoResize']);
        $this->assertTrue($json['showCharacterCount']);
        $this->assertTrue($json['alwaysShow']);
        $this->assertContains('required', $json['rules']);
        $this->assertTrue($json['nullable']);
    }
}
