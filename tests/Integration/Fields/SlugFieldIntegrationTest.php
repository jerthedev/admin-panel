<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Slug;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Slug Field Integration Test
 *
 * Tests the complete integration between PHP Slug field class,
 * API endpoints, and frontend functionality.
 * 
 * Focuses on Nova API compatibility and field configuration
 * behavior rather than database operations.
 */
class SlugFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users (using existing User model structure)
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

    /** @test */
    public function it_creates_slug_field_with_nova_syntax(): void
    {
        $field = Slug::make('URL Slug');

        $this->assertEquals('URL Slug', $field->name);
        $this->assertEquals('url_slug', $field->attribute);
        $this->assertEquals('SlugField', $field->component);
    }

    /** @test */
    public function it_configures_slug_field_with_from_attribute(): void
    {
        $field = Slug::make('Slug')->from('title');

        $this->assertEquals('title', $field->fromAttribute);
        
        $meta = $field->meta();
        $this->assertEquals('title', $meta['fromAttribute']);
    }

    /** @test */
    public function it_configures_slug_field_with_custom_separator(): void
    {
        $field = Slug::make('Slug')->separator('_');

        $this->assertEquals('_', $field->separator);
        
        $meta = $field->meta();
        $this->assertEquals('_', $meta['separator']);
    }

    /** @test */
    public function it_configures_slug_field_with_max_length(): void
    {
        $field = Slug::make('Slug')->maxLength(50);

        $this->assertEquals(50, $field->maxLength);
        
        $meta = $field->meta();
        $this->assertEquals(50, $meta['maxLength']);
    }

    /** @test */
    public function it_configures_slug_field_with_lowercase_setting(): void
    {
        $field = Slug::make('Slug')->lowercase(false);

        $this->assertFalse($field->lowercase);
        
        $meta = $field->meta();
        $this->assertFalse($meta['lowercase']);
    }

    /** @test */
    public function it_configures_slug_field_with_unique_validation(): void
    {
        $field = Slug::make('Slug')->unique('posts', 'slug');

        $this->assertEquals('posts', $field->uniqueTable);
        $this->assertEquals('slug', $field->uniqueColumn);
        
        $meta = $field->meta();
        $this->assertEquals('posts', $meta['uniqueTable']);
        $this->assertEquals('slug', $meta['uniqueColumn']);
    }

    /** @test */
    public function it_generates_slug_from_text(): void
    {
        $field = Slug::make('Slug');

        $slug = $field->generateSlug('Hello World Test');
        $this->assertEquals('hello-world-test', $slug);
    }

    /** @test */
    public function it_generates_slug_with_custom_separator(): void
    {
        $field = Slug::make('Slug')->separator('_');

        $slug = $field->generateSlug('Hello World Test');
        $this->assertEquals('hello_world_test', $slug);
    }

    /** @test */
    public function it_generates_slug_with_max_length(): void
    {
        $field = Slug::make('Slug')->maxLength(10);

        $slug = $field->generateSlug('Hello World Test');
        $this->assertEquals('hello-worl', $slug);
    }

    /** @test */
    public function it_fills_model_with_provided_slug(): void
    {
        $field = Slug::make('Slug');
        $model = new \stdClass();
        $request = new Request(['slug' => 'Custom Slug']);

        $field->fill($request, $model);

        $this->assertEquals('custom-slug', $model->slug);
    }

    /** @test */
    public function it_fills_model_with_auto_generated_slug(): void
    {
        $field = Slug::make('Slug')->from('title');
        $model = new \stdClass();
        $request = new Request(['title' => 'My Great Article', 'slug' => '']);

        $field->fill($request, $model);

        $this->assertEquals('my-great-article', $model->slug);
    }

    /** @test */
    public function it_cleans_provided_slug_value(): void
    {
        $field = Slug::make('Slug');
        $model = new \stdClass();
        $request = new Request(['slug' => 'Messy Slug!@#$%']);

        $field->fill($request, $model);

        $this->assertEquals('messy-slug-at', $model->slug);
    }

    /** @test */
    public function it_serializes_to_json_with_all_meta_data(): void
    {
        $field = Slug::make('Article Slug')
            ->from('title')
            ->separator('_')
            ->maxLength(100)
            ->lowercase(true)
            ->unique('articles', 'slug')
            ->required()
            ->help('URL-friendly version of the title');

        $json = $field->jsonSerialize();

        $this->assertEquals('Article Slug', $json['name']);
        $this->assertEquals('article_slug', $json['attribute']);
        $this->assertEquals('SlugField', $json['component']);
        $this->assertEquals('title', $json['fromAttribute']);
        $this->assertEquals('_', $json['separator']);
        $this->assertEquals(100, $json['maxLength']);
        $this->assertTrue($json['lowercase']);
        $this->assertEquals('articles', $json['uniqueTable']);
        $this->assertEquals('slug', $json['uniqueColumn']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('URL-friendly version of the title', $json['helpText']);
    }

    /** @test */
    public function it_handles_complex_slug_generation_scenarios(): void
    {
        $field = Slug::make('Slug')->separator('_')->maxLength(20);

        // Test with special characters (@ becomes 'at')
        $slug1 = $field->generateSlug('Hello! @World# $Test%');
        $this->assertEquals('hello_at_world_test', $slug1);

        // Test with numbers
        $slug2 = $field->generateSlug('Article 123 Version 2.0');
        $this->assertEquals('article_123_version', $slug2);

        // Test with unicode characters
        $slug3 = $field->generateSlug('Café & Restaurant');
        $this->assertEquals('cafe_restaurant', $slug3);
    }

    /** @test */
    public function it_integrates_with_inertia_response_format(): void
    {
        $field = Slug::make('Post Slug')
            ->from('title')
            ->separator('-')
            ->maxLength(50)
            ->help('This will be used in the URL');

        $serialized = $field->jsonSerialize();

        // Verify all required properties for frontend integration
        $this->assertArrayHasKey('component', $serialized);
        $this->assertArrayHasKey('attribute', $serialized);
        $this->assertArrayHasKey('name', $serialized);
        $this->assertArrayHasKey('fromAttribute', $serialized);
        $this->assertArrayHasKey('separator', $serialized);
        $this->assertArrayHasKey('maxLength', $serialized);
        $this->assertArrayHasKey('lowercase', $serialized);
        $this->assertArrayHasKey('helpText', $serialized);

        // Verify values match Nova expectations
        $this->assertEquals('SlugField', $serialized['component']);
        $this->assertEquals('post_slug', $serialized['attribute']);
        $this->assertEquals('Post Slug', $serialized['name']);
        $this->assertEquals('title', $serialized['fromAttribute']);
        $this->assertEquals('-', $serialized['separator']);
        $this->assertEquals(50, $serialized['maxLength']);
        $this->assertTrue($serialized['lowercase']);
        $this->assertEquals('This will be used in the URL', $serialized['helpText']);
    }

    /** @test */
    public function it_handles_edge_cases_in_slug_generation(): void
    {
        $field = Slug::make('Slug');

        // Empty string
        $this->assertEquals('', $field->generateSlug(''));

        // Only special characters (@ becomes 'at')
        $this->assertEquals('at', $field->generateSlug('!@#$%^&*()'));

        // Only spaces
        $this->assertEquals('', $field->generateSlug('   '));

        // Mixed case with numbers
        $this->assertEquals('test-123-abc', $field->generateSlug('Test 123 ABC'));
    }

    /** @test */
    public function it_maintains_nova_api_compatibility(): void
    {
        // Test the core Nova API methods exist and work as expected
        $field = Slug::make('Slug')
            ->from('title')
            ->separator('_');

        // Verify method chaining works (Nova pattern)
        $this->assertInstanceOf(Slug::class, $field);
        $this->assertEquals('title', $field->fromAttribute);
        $this->assertEquals('_', $field->separator);

        // Verify meta() method returns expected structure
        $meta = $field->meta();
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('fromAttribute', $meta);
        $this->assertArrayHasKey('separator', $meta);
    }
}
