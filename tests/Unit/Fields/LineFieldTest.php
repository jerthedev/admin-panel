<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Line;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Line Field Unit Tests
 *
 * Tests for Line field class with 100% Nova API compatibility.
 * Tests all Nova Line field features including make(), asSmall(),
 * asHeading(), asSubText(), and display functionality.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class LineFieldTest extends TestCase
{
    /** @test */
    public function it_creates_line_field_with_nova_syntax(): void
    {
        $field = Line::make('User Status');

        $this->assertEquals('User Status', $field->name);
        $this->assertEquals('user_status', $field->attribute);
        $this->assertEquals('LineField', $field->component);
    }

    /** @test */
    public function it_creates_line_field_with_custom_attribute(): void
    {
        $field = Line::make('Display Name', 'custom_attribute');

        $this->assertEquals('Display Name', $field->name);
        $this->assertEquals('custom_attribute', $field->attribute);
    }

    /** @test */
    public function it_creates_line_field_with_resolve_callback(): void
    {
        $callback = fn($resource) => 'Custom Value';
        $field = Line::make('Status', null, $callback);

        $this->assertEquals('Status', $field->name);
        $this->assertEquals('status', $field->attribute);
        $this->assertEquals($callback, $field->resolveCallback);
    }

    /** @test */
    public function it_sets_as_small_formatting(): void
    {
        $field = Line::make('Small Text')->asSmall();

        $this->assertTrue($field->asSmall);
        $this->assertFalse($field->asHeading);
        $this->assertFalse($field->asSubText);
    }

    /** @test */
    public function it_sets_as_heading_formatting(): void
    {
        $field = Line::make('Heading Text')->asHeading();

        $this->assertTrue($field->asHeading);
        $this->assertFalse($field->asSmall);
        $this->assertFalse($field->asSubText);
    }

    /** @test */
    public function it_sets_as_sub_text_formatting(): void
    {
        $field = Line::make('Sub Text')->asSubText();

        $this->assertTrue($field->asSubText);
        $this->assertFalse($field->asSmall);
        $this->assertFalse($field->asHeading);
    }

    /** @test */
    public function it_resets_other_formatting_when_setting_new_format(): void
    {
        $field = Line::make('Text')
            ->asSmall()
            ->asHeading();

        $this->assertTrue($field->asHeading);
        $this->assertFalse($field->asSmall);
        $this->assertFalse($field->asSubText);

        $field->asSubText();

        $this->assertTrue($field->asSubText);
        $this->assertFalse($field->asSmall);
        $this->assertFalse($field->asHeading);
    }

    /** @test */
    public function it_is_readonly_by_default(): void
    {
        $field = Line::make('Status');

        $this->assertTrue($field->readonly);
    }

    /** @test */
    public function it_does_not_fill_model_data(): void
    {
        $field = Line::make('Status');
        $request = new Request(['status' => 'some value']);
        $model = new \stdClass();

        // Line fields don't store data, so fill should be a no-op
        $field->fill($request, $model);

        // Model should not have the attribute set
        $this->assertFalse(property_exists($model, 'status'));
    }

    /** @test */
    public function it_resolves_value_from_resource(): void
    {
        $resource = (object) ['status' => 'Active'];
        $field = Line::make('Status');

        $field->resolveForDisplay($resource);

        $this->assertEquals('Active', $field->value);
    }

    /** @test */
    public function it_resolves_value_with_custom_attribute(): void
    {
        $resource = (object) ['custom_field' => 'Custom Value'];
        $field = Line::make('Display Name', 'custom_field');

        $field->resolveForDisplay($resource);

        $this->assertEquals('Custom Value', $field->value);
    }

    /** @test */
    public function it_resolves_value_with_callback(): void
    {
        $resource = (object) ['name' => 'John', 'surname' => 'Doe'];
        $field = Line::make('Full Name', null, function ($resource) {
            return $resource->name . ' ' . $resource->surname;
        });

        $field->resolveForDisplay($resource);

        $this->assertEquals('John Doe', $field->value);
    }

    /** @test */
    public function it_falls_back_to_field_name_when_no_value(): void
    {
        $resource = (object) [];
        $field = Line::make('Default Text');

        $field->resolveForDisplay($resource);

        $this->assertEquals('Default Text', $field->value);
    }

    /** @test */
    public function it_includes_formatting_in_meta(): void
    {
        $field = Line::make('Status')->asSmall();
        $meta = $field->meta();

        $this->assertTrue($meta['asSmall']);
        $this->assertFalse($meta['asHeading']);
        $this->assertFalse($meta['asSubText']);
        $this->assertTrue($meta['isLine']);
    }

    /** @test */
    public function it_includes_all_formatting_options_in_meta(): void
    {
        $field = Line::make('Heading')->asHeading();
        $meta = $field->meta();

        $this->assertFalse($meta['asSmall']);
        $this->assertTrue($meta['asHeading']);
        $this->assertFalse($meta['asSubText']);
        $this->assertTrue($meta['isLine']);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_line_field(): void
    {
        $field = Line::make('Status');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Line::class, $field->asSmall());
        $this->assertInstanceOf(Line::class, $field->asHeading());
        $this->assertInstanceOf(Line::class, $field->asSubText());
        
        // Test component name matches Nova
        $this->assertEquals('LineField', $field->component);
        
        // Test default values match Nova
        $freshField = Line::make('Fresh');
        $this->assertFalse($freshField->asSmall);
        $this->assertFalse($freshField->asHeading);
        $this->assertFalse($freshField->asSubText);
        $this->assertTrue($freshField->readonly);
    }

    /** @test */
    public function it_handles_complex_nova_configuration(): void
    {
        $field = Line::make('User Status')
            ->asHeading()
            ->help('This shows the user status')
            ->showOnIndex()
            ->showOnDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating();

        $this->assertTrue($field->asHeading);
        $this->assertEquals('This shows the user status', $field->helpText);
        $this->assertTrue($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    /** @test */
    public function it_chains_methods_fluently(): void
    {
        $field = Line::make('Status')
            ->asSmall()
            ->help('Small status text')
            ->showOnIndex();

        $this->assertTrue($field->asSmall);
        $this->assertEquals('Small status text', $field->helpText);
        $this->assertTrue($field->showOnIndex);
    }

    /** @test */
    public function it_handles_nested_resource_attributes(): void
    {
        $resource = (object) [
            'user' => (object) ['profile' => (object) ['status' => 'Premium']]
        ];
        
        $field = Line::make('User Status', 'user.profile.status');
        $field->resolveForDisplay($resource);

        $this->assertEquals('Premium', $field->value);
    }

    /** @test */
    public function it_serializes_to_json_correctly(): void
    {
        $field = Line::make('Status')
            ->asHeading()
            ->help('Status information');

        $json = $field->jsonSerialize();

        $this->assertEquals('Status', $json['name']);
        $this->assertEquals('status', $json['attribute']);
        $this->assertEquals('LineField', $json['component']);
        $this->assertTrue($json['asHeading']);
        $this->assertFalse($json['asSmall']);
        $this->assertFalse($json['asSubText']);
        $this->assertTrue($json['isLine']);
        $this->assertEquals('Status information', $json['helpText']);
    }
}
