<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Text Field Unit Tests.
 *
 * Tests for Text field class with 100% Nova API compatibility.
 * Tests all Nova Text Field features: suggestions, maxlength, enforceMaxlength,
 * copyable, asHtml, asEncodedHtml, and withMeta.
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

    public function test_text_field_enforce_maxlength_with_maxlength(): void
    {
        $field = Text::make('Name')
            ->maxlength(50)
            ->enforceMaxlength();

        $this->assertEquals(50, $field->maxlength);
        $this->assertTrue($field->enforceMaxlength);
    }

    public function test_text_field_suggestions(): void
    {
        $suggestions = ['John', 'Jane', 'Bob'];
        $field = Text::make('Name')->suggestions($suggestions);

        $this->assertEquals($suggestions, $field->suggestions);
    }

    public function test_text_field_maxlength(): void
    {
        $field = Text::make('Name')->maxlength(100);

        $this->assertEquals(100, $field->maxlength);
    }

    public function test_text_field_copyable(): void
    {
        $field = Text::make('Name')->copyable();

        $this->assertTrue($field->copyable);
    }

    public function test_text_field_copyable_false(): void
    {
        $field = Text::make('Name')->copyable(false);

        $this->assertFalse($field->copyable);
    }

    public function test_text_field_as_html(): void
    {
        $field = Text::make('Content')->asHtml();

        $this->assertTrue($field->asHtml);
    }

    public function test_text_field_as_html_false(): void
    {
        $field = Text::make('Content')->asHtml(false);

        $this->assertFalse($field->asHtml);
    }

    public function test_text_field_as_encoded_html(): void
    {
        $field = Text::make('Content')->asEncodedHtml();

        $this->assertTrue($field->asEncodedHtml);
    }

    public function test_text_field_as_encoded_html_false(): void
    {
        $field = Text::make('Content')->asEncodedHtml(false);

        $this->assertFalse($field->asEncodedHtml);
    }

    public function test_text_field_fill_method_trims_whitespace(): void
    {
        $field = Text::make('Name');
        $model = new \stdClass;
        $request = new \Illuminate\Http\Request(['name' => '  John Doe  ']);

        $field->fill($request, $model);

        $this->assertEquals('John Doe', $model->name);
    }

    public function test_text_field_with_meta(): void
    {
        $field = Text::make('Name')->withMeta(['extraAttributes' => ['data-test' => 'value']]);

        $meta = $field->meta();
        $this->assertArrayHasKey('extraAttributes', $meta);
        $this->assertEquals(['data-test' => 'value'], $meta['extraAttributes']);
    }

    public function test_text_field_fill_method_with_callback(): void
    {
        $field = Text::make('Name')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = strtoupper($request->input($attribute));
        });
        $model = new \stdClass;
        $request = new \Illuminate\Http\Request(['name' => 'john doe']);

        $field->fill($request, $model);

        $this->assertEquals('JOHN DOE', $model->name);
    }

    public function test_text_field_meta_includes_all_properties(): void
    {
        $field = Text::make('Name')
            ->suggestions(['John', 'Jane'])
            ->maxlength(100)
            ->enforceMaxlength()
            ->asEncodedHtml();

        $meta = $field->meta();

        $this->assertArrayHasKey('suggestions', $meta);
        $this->assertArrayHasKey('maxlength', $meta);
        $this->assertArrayHasKey('enforceMaxlength', $meta);
        $this->assertArrayHasKey('asEncodedHtml', $meta);
        $this->assertEquals(['John', 'Jane'], $meta['suggestions']);
        $this->assertEquals(100, $meta['maxlength']);
        $this->assertTrue($meta['enforceMaxlength']);
        $this->assertTrue($meta['asEncodedHtml']);
    }

    public function test_text_field_advanced_json_serialization(): void
    {
        $field = Text::make('Name')
            ->maxlength(100)
            ->enforceMaxlength()
            ->suggestions(['John', 'Jane', 'Bob'])
            ->copyable()
            ->asHtml()
            ->asEncodedHtml()
            ->textAlign('center');

        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('maxlength', $json);
        $this->assertArrayHasKey('enforceMaxlength', $json);
        $this->assertArrayHasKey('suggestions', $json);
        $this->assertArrayHasKey('copyable', $json);
        $this->assertArrayHasKey('asHtml', $json);
        $this->assertArrayHasKey('asEncodedHtml', $json);
        $this->assertArrayHasKey('textAlign', $json);
        $this->assertEquals(100, $json['maxlength']);
        $this->assertTrue($json['enforceMaxlength']);
        $this->assertEquals(['John', 'Jane', 'Bob'], $json['suggestions']);
        $this->assertTrue($json['copyable']);
        $this->assertTrue($json['asHtml']);
        $this->assertTrue($json['asEncodedHtml']);
        $this->assertEquals('center', $json['textAlign']);
    }

    public function test_text_field_nova_api_compatibility(): void
    {
        // Test all Nova Text Field API methods work correctly
        $field = Text::make('Title')
            ->suggestions(['Article', 'Tutorial', 'Guide'])
            ->maxlength(255)
            ->enforceMaxlength()
            ->copyable()
            ->asHtml()
            ->asEncodedHtml()
            ->withMeta(['extraAttributes' => ['data-test' => 'nova-compatible']]);

        // Verify all properties are set correctly
        $this->assertEquals(['Article', 'Tutorial', 'Guide'], $field->suggestions);
        $this->assertEquals(255, $field->maxlength);
        $this->assertTrue($field->enforceMaxlength);
        $this->assertTrue($field->copyable);
        $this->assertTrue($field->asHtml);
        $this->assertTrue($field->asEncodedHtml);

        // Verify meta includes all Nova-compatible properties
        $meta = $field->meta();
        $this->assertArrayHasKey('suggestions', $meta);
        $this->assertArrayHasKey('maxlength', $meta);
        $this->assertArrayHasKey('enforceMaxlength', $meta);
        $this->assertArrayHasKey('asEncodedHtml', $meta);
        $this->assertArrayHasKey('extraAttributes', $meta);
        $this->assertEquals(['data-test' => 'nova-compatible'], $meta['extraAttributes']);
    }
}
