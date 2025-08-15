<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Text Field Unit Tests
 *
 * Tests for Text field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TextFieldTest extends TestCase
{
    public function test_text_field_creation(): void
    {
        $field = Text::make('Name');

        $this->assertEquals('Name', $field->name);
        $this->assertEquals('name', $field->attribute);
        $this->assertEquals('TextField', $field->component);
    }

    public function test_text_field_with_custom_attribute(): void
    {
        $field = Text::make('Full Name', 'full_name');

        $this->assertEquals('Full Name', $field->name);
        $this->assertEquals('full_name', $field->attribute);
    }

    public function test_text_field_enforce_maxlength(): void
    {
        $field = Text::make('Name')->enforceMaxlength();

        $this->assertTrue($field->enforceMaxlength);
    }

    public function test_text_field_enforce_maxlength_false(): void
    {
        $field = Text::make('Name')->enforceMaxlength(false);

        $this->assertFalse($field->enforceMaxlength);
    }

    public function test_text_field_enforce_maxlength_with_max_length(): void
    {
        $field = Text::make('Name')
            ->maxLength(50)
            ->enforceMaxlength();

        $this->assertEquals(50, $field->maxLength);
        $this->assertTrue($field->enforceMaxlength);
    }

    public function test_text_field_suggestions(): void
    {
        $suggestions = ['John', 'Jane', 'Bob'];
        $field = Text::make('Name')->suggestions($suggestions);

        $this->assertEquals($suggestions, $field->suggestions);
    }

    public function test_text_field_max_length(): void
    {
        $field = Text::make('Name')->maxLength(100);

        $this->assertEquals(100, $field->maxLength);
    }

    public function test_text_field_as_password(): void
    {
        $field = Text::make('Password')->asPassword();

        $this->assertTrue($field->asPassword);
    }

    public function test_text_field_as_password_false(): void
    {
        $field = Text::make('Password')->asPassword(false);

        $this->assertFalse($field->asPassword);
    }

    public function test_text_field_fill_method_trims_whitespace(): void
    {
        $field = Text::make('Name');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['name' => '  John Doe  ']);

        $field->fill($request, $model);

        $this->assertEquals('John Doe', $model->name);
    }

    public function test_text_field_fill_method_does_not_trim_password(): void
    {
        $field = Text::make('Password')->asPassword();
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['password' => '  secret  ']);

        $field->fill($request, $model);

        $this->assertEquals('  secret  ', $model->password);
    }

    public function test_text_field_fill_method_with_callback(): void
    {
        $field = Text::make('Name')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = strtoupper($request->input($attribute));
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['name' => 'john doe']);

        $field->fill($request, $model);

        $this->assertEquals('JOHN DOE', $model->name);
    }

    public function test_text_field_meta_includes_all_properties(): void
    {
        $field = Text::make('Name')
            ->suggestions(['John', 'Jane'])
            ->maxLength(100)
            ->asPassword()
            ->enforceMaxlength();

        $meta = $field->meta();

        $this->assertArrayHasKey('suggestions', $meta);
        $this->assertArrayHasKey('maxLength', $meta);
        $this->assertArrayHasKey('asPassword', $meta);
        $this->assertArrayHasKey('enforceMaxlength', $meta);
        $this->assertEquals(['John', 'Jane'], $meta['suggestions']);
        $this->assertEquals(100, $meta['maxLength']);
        $this->assertTrue($meta['asPassword']);
        $this->assertTrue($meta['enforceMaxlength']);
    }

    public function test_text_field_advanced_json_serialization(): void
    {
        $field = Text::make('Name')
            ->maxLength(100)
            ->enforceMaxlength()
            ->suggestions(['John', 'Jane', 'Bob'])
            ->copyable()
            ->textAlign('left');

        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('maxLength', $json);
        $this->assertArrayHasKey('enforceMaxlength', $json);
        $this->assertArrayHasKey('suggestions', $json);
        $this->assertArrayHasKey('copyable', $json);
        $this->assertArrayHasKey('textAlign', $json);
        $this->assertEquals(100, $json['maxLength']);
        $this->assertTrue($json['enforceMaxlength']);
        $this->assertEquals(['John', 'Jane', 'Bob'], $json['suggestions']);
        $this->assertTrue($json['copyable']);
        $this->assertEquals('left', $json['textAlign']);
    }
}
