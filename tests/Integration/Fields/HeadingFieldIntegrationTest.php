<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Heading;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Heading Field Integration Test
 *
 * Tests the complete integration between PHP Heading field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 *
 * Focuses on field configuration and behavior rather than
 * database operations, testing the Nova API integration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class HeadingFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users for form context
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
    }

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
    public function it_supports_nova_as_html_method(): void
    {
        $field = Heading::make('<p class="text-red-500">* All fields are required.</p>')
            ->asHtml();

        $this->assertTrue($field->asHtml);
        $this->assertEquals('<p class="text-red-500">* All fields are required.</p>', $field->name);
    }

    /** @test */
    public function it_supports_nova_as_html_with_false_parameter(): void
    {
        $field = Heading::make('Meta')
            ->asHtml(false);

        $this->assertFalse($field->asHtml);
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
    public function it_can_override_visibility_settings(): void
    {
        $field = Heading::make('Meta')
            ->showOnIndex()
            ->hideFromDetail();

        $this->assertTrue($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
    }

    /** @test */
    public function it_is_nullable_and_readonly_by_default(): void
    {
        $field = Heading::make('Meta');

        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
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
    public function it_serializes_correctly_for_json_api(): void
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

    /** @test */
    public function it_integrates_with_form_context(): void
    {
        $user = User::find(1);
        $field = Heading::make('User Information');

        // Heading fields should work in form context without affecting model data
        $field->resolveForDisplay($user);

        $this->assertEquals('User Information', $field->value);
        // User model should remain unchanged
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    /** @test */
    public function it_handles_complex_html_scenarios(): void
    {
        $complexHtml = '
            <div class="border-l-4 border-blue-500 bg-blue-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Important Notice</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Please review all information carefully before submitting.</p>
                        </div>
                    </div>
                </div>
            </div>
        ';

        $field = Heading::make($complexHtml)->asHtml();

        $this->assertEquals($complexHtml, $field->name);
        $this->assertTrue($field->asHtml);

        $json = $field->jsonSerialize();
        $this->assertEquals($complexHtml, $json['name']);
        $this->assertTrue($json['asHtml']);
    }

    /** @test */
    public function it_maintains_nova_api_compatibility(): void
    {
        // Test that all Nova Heading field methods are available and work correctly
        $field = Heading::make('Meta Section')
            ->asHtml()
            ->help('This is a heading field')
            ->showOnIndex()
            ->hideFromDetail()
            ->hideWhenCreating()
            ->hideWhenUpdating();

        // Verify Nova API methods work
        $this->assertEquals('Meta Section', $field->name);
        $this->assertTrue($field->asHtml);
        $this->assertEquals('This is a heading field', $field->helpText);
        $this->assertTrue($field->showOnIndex);
        $this->assertFalse($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);

        // Verify serialization includes all necessary data
        $json = $field->jsonSerialize();
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('attribute', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('asHtml', $json);
        $this->assertArrayHasKey('isHeading', $json);
        $this->assertArrayHasKey('showOnIndex', $json);
        $this->assertArrayHasKey('showOnDetail', $json);
        $this->assertArrayHasKey('showOnCreation', $json);
        $this->assertArrayHasKey('showOnUpdate', $json);
    }
}
