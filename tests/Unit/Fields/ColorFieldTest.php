<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Color;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Color Field Unit Tests (Nova-compatible)
 *
 * Ensures Color field exposes the correct component and behaves like a
 * simple HTML5 color input with base Field features.
 */
class ColorFieldTest extends TestCase
{
    public function test_color_field_creation(): void
    {
        $field = Color::make('Theme Color');

        $this->assertEquals('Theme Color', $field->name);
        $this->assertEquals('theme_color', $field->attribute);
        $this->assertEquals('ColorField', $field->component);
    }

    public function test_color_field_with_custom_attribute(): void
    {
        $field = Color::make('Brand Color', 'brand_color');

        $this->assertEquals('Brand Color', $field->name);
        $this->assertEquals('brand_color', $field->attribute);
    }

    public function test_color_field_json_serialization_contains_base_props_only(): void
    {
        $field = Color::make('Brand Color')->required()->help('Pick a color');

        $json = $field->jsonSerialize();

        $this->assertEquals('Brand Color', $json['name']);
        $this->assertEquals('brand_color', $json['attribute']);
        $this->assertEquals('ColorField', $json['component']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Pick a color', $json['helpText']);

        // Ensure no non-Nova properties are present
        $this->assertArrayNotHasKey('format', $json);
        $this->assertArrayNotHasKey('withAlpha', $json);
        $this->assertArrayNotHasKey('palette', $json);
        $this->assertArrayNotHasKey('showPreview', $json);
        $this->assertArrayNotHasKey('swatches', $json);
    }

    public function test_color_field_resolve_and_fill(): void
    {
        $field = Color::make('Color');

        // Resolve from resource
        $resource = (object) ['color' => '#FF0000'];
        $field->resolve($resource);
        $this->assertEquals('#FF0000', $field->value);

        // Fill into model from request
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['color' => '#00FF00']);
        $field->fill($request, $model);
        $this->assertEquals('#00FF00', $model->color);
    }

    public function test_color_field_inherits_base_field_features(): void
    {
        $field = Color::make('Color')
            ->rules('string')
            ->nullable()
            ->readonly()
            ->help('Help text')
            ->placeholder('Placeholder');

        $this->assertEquals(['string'], $field->rules);
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
        $this->assertEquals('Help text', $field->helpText);
        $this->assertEquals('Placeholder', $field->placeholder);
    }
}
