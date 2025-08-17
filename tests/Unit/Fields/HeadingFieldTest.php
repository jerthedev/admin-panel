<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Heading;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Heading Field Unit Tests
 *
 * Tests for Heading field class with 100% Nova API compatibility.
 * Tests all Nova Heading field features including make(), asHtml(),
 * visibility controls, and meta data.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HeadingFieldTest extends TestCase
{
    /** @test */
    public function it_creates_heading_field_with_nova_syntax(): void
    {
        $field = Heading::make('Meta');

        $this->assertEquals('Meta', $field->name);
        $this->assertEquals('meta', $field->attribute);
        $this->assertEquals('HeadingField', $field->component);
    }

    /** @test */
    public function it_creates_heading_field_with_custom_attribute(): void
    {
        $field = Heading::make('Section Header', 'section_header');

        $this->assertEquals('Section Header', $field->name);
        $this->assertEquals('section_header', $field->attribute);
    }

    /** @test */
    public function it_automatically_hides_from_index_page(): void
    {
        $field = Heading::make('Meta');

        // Heading fields are automatically hidden from the resource index page
        $this->assertFalse($field->showOnIndex);
    }

    /** @test */
    public function it_shows_on_detail_creation_and_update_by_default(): void
    {
        $field = Heading::make('Meta');

        $this->assertTrue($field->showOnDetail);
        $this->assertTrue($field->showOnCreation);
        $this->assertTrue($field->showOnUpdate);
    }

    /** @test */
    public function it_is_nullable_and_readonly_by_default(): void
    {
        $field = Heading::make('Meta');

        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
    }

    /** @test */
    public function it_supports_as_html_method(): void
    {
        $field = Heading::make('<p class="text-red-500">* All fields are required.</p>')
            ->asHtml();

        $this->assertTrue($field->asHtml);
    }

    /** @test */
    public function it_supports_as_html_with_false_parameter(): void
    {
        $field = Heading::make('Meta')
            ->asHtml(false);

        $this->assertFalse($field->asHtml);
    }

    /** @test */
    public function it_does_not_fill_model_data(): void
    {
        $field = Heading::make('Meta');
        $request = new Request(['meta' => 'some value']);
        $model = new \stdClass();

        // Heading fields don't store data, so fill should be a no-op
        $field->fill($request, $model);

        // Model should not have the attribute set
        $this->assertFalse(property_exists($model, 'meta'));
    }

    /** @test */
    public function it_resolves_display_value_as_field_name(): void
    {
        $field = Heading::make('Section Title');
        $resource = new \stdClass();

        $field->resolveForDisplay($resource);

        $this->assertEquals('Section Title', $field->value);
    }

    /** @test */
    public function it_includes_correct_meta_data(): void
    {
        $field = Heading::make('Meta')->asHtml();

        $meta = $field->meta();

        $this->assertArrayHasKey('asHtml', $meta);
        $this->assertArrayHasKey('isHeading', $meta);
        $this->assertTrue($meta['asHtml']);
        $this->assertTrue($meta['isHeading']);
    }

    /** @test */
    public function it_includes_visibility_settings_in_json_serialization(): void
    {
        $field = Heading::make('Meta');

        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('showOnIndex', $json);
        $this->assertArrayHasKey('showOnDetail', $json);
        $this->assertArrayHasKey('showOnCreation', $json);
        $this->assertArrayHasKey('showOnUpdate', $json);
        $this->assertFalse($json['showOnIndex']);
        $this->assertTrue($json['showOnDetail']);
        $this->assertTrue($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    /** @test */
    public function it_can_override_visibility_settings(): void
    {
        $field = Heading::make('Meta')
            ->showOnIndex()
            ->hideFromDetail();

        $this->assertTrue($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
    }

    /** @test */
    public function it_serializes_correctly_for_json(): void
    {
        $field = Heading::make('<h2>Important Section</h2>')
            ->asHtml()
            ->showOnIndex();

        $json = $field->jsonSerialize();

        $this->assertEquals('<h2>Important Section</h2>', $json['name']);
        $this->assertEquals('<h2>important_section</h2>', $json['attribute']);
        $this->assertEquals('HeadingField', $json['component']);
        $this->assertTrue($json['asHtml']);
        $this->assertTrue($json['isHeading']);
        $this->assertTrue($json['showOnIndex']);
        $this->assertTrue($json['nullable']);
        $this->assertTrue($json['readonly']);
    }

    /** @test */
    public function it_supports_nova_field_chaining(): void
    {
        $field = Heading::make('Meta')
            ->asHtml()
            ->showOnIndex()
            ->hideFromDetail()
            ->help('This is a section header');

        $this->assertTrue($field->asHtml);
        $this->assertTrue($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertEquals('This is a section header', $field->helpText);
    }

    /** @test */
    public function it_works_with_html_content(): void
    {
        $htmlContent = '<div class="bg-blue-100 p-4"><h3>User Information</h3><p>Please fill out all required fields.</p></div>';
        $field = Heading::make($htmlContent)->asHtml();

        $this->assertEquals($htmlContent, $field->name);
        $this->assertTrue($field->asHtml);
    }

    /** @test */
    public function it_works_with_plain_text_content(): void
    {
        $field = Heading::make('Simple Section Header');

        $this->assertEquals('Simple Section Header', $field->name);
        $this->assertFalse($field->asHtml);
    }
}
