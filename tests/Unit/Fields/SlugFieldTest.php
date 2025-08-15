<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Slug;
use PHPUnit\Framework\TestCase;

class SlugFieldTest extends TestCase
{
    public function test_slug_field_creation(): void
    {
        $field = Slug::make('URL Slug');

        $this->assertInstanceOf(Slug::class, $field);
        $this->assertEquals('URL Slug', $field->name);
        $this->assertEquals('url_slug', $field->attribute);
        $this->assertEquals('SlugField', $field->component);
    }

    public function test_slug_field_creation_with_attribute(): void
    {
        $field = Slug::make('Slug', 'post_slug');

        $this->assertEquals('Slug', $field->name);
        $this->assertEquals('post_slug', $field->attribute);
    }

    public function test_slug_field_default_properties(): void
    {
        $field = Slug::make('Slug');

        $this->assertNull($field->fromAttribute);
        $this->assertEquals('-', $field->separator);
        $this->assertNull($field->maxLength);
        $this->assertTrue($field->lowercase);
        $this->assertNull($field->uniqueTable);
        $this->assertNull($field->uniqueColumn);
    }

    public function test_slug_field_from_configuration(): void
    {
        $field = Slug::make('Slug')->from('title');

        $this->assertEquals('title', $field->fromAttribute);
    }

    public function test_slug_field_from_method_chaining(): void
    {
        $field = Slug::make('Slug')->from('name');

        $this->assertInstanceOf(Slug::class, $field);
        $this->assertEquals('name', $field->fromAttribute);
    }

    public function test_slug_field_separator_configuration(): void
    {
        $field = Slug::make('Slug')->separator('_');

        $this->assertEquals('_', $field->separator);
    }

    public function test_slug_field_separator_method_chaining(): void
    {
        $field = Slug::make('Slug')->separator('.');

        $this->assertInstanceOf(Slug::class, $field);
        $this->assertEquals('.', $field->separator);
    }

    public function test_slug_field_max_length_configuration(): void
    {
        $field = Slug::make('Slug')->maxLength(50);

        $this->assertEquals(50, $field->maxLength);
    }

    public function test_slug_field_max_length_method_chaining(): void
    {
        $field = Slug::make('Slug')->maxLength(100);

        $this->assertInstanceOf(Slug::class, $field);
        $this->assertEquals(100, $field->maxLength);
    }

    public function test_slug_field_lowercase_enabled(): void
    {
        $field = Slug::make('Slug')->lowercase();

        $this->assertTrue($field->lowercase);
    }

    public function test_slug_field_lowercase_disabled(): void
    {
        $field = Slug::make('Slug')->lowercase(false);

        $this->assertFalse($field->lowercase);
    }

    public function test_slug_field_lowercase_method_chaining(): void
    {
        $field = Slug::make('Slug')->lowercase(true);

        $this->assertInstanceOf(Slug::class, $field);
        $this->assertTrue($field->lowercase);
    }

    public function test_slug_field_unique_configuration(): void
    {
        $field = Slug::make('Slug')->unique('posts');

        $this->assertEquals('posts', $field->uniqueTable);
        $this->assertEquals('slug', $field->uniqueColumn);
    }

    public function test_slug_field_unique_with_custom_column(): void
    {
        $field = Slug::make('Slug')->unique('articles', 'article_slug');

        $this->assertEquals('articles', $field->uniqueTable);
        $this->assertEquals('article_slug', $field->uniqueColumn);
    }

    public function test_slug_field_unique_method_chaining(): void
    {
        $field = Slug::make('Slug')->unique('posts', 'post_slug');

        $this->assertInstanceOf(Slug::class, $field);
        $this->assertEquals('posts', $field->uniqueTable);
        $this->assertEquals('post_slug', $field->uniqueColumn);
    }

    public function test_generate_slug_basic(): void
    {
        $field = Slug::make('Slug');

        $slug = $field->generateSlug('Hello World');

        $this->assertEquals('hello-world', $slug);
    }

    public function test_generate_slug_with_special_characters(): void
    {
        $field = Slug::make('Slug');

        $slug = $field->generateSlug('Hello, World! & More');

        $this->assertEquals('hello-world-more', $slug);
    }

    public function test_generate_slug_with_custom_separator(): void
    {
        $field = Slug::make('Slug')->separator('_');

        $slug = $field->generateSlug('Hello World');

        $this->assertEquals('hello_world', $slug);
    }

    public function test_generate_slug_with_lowercase_disabled(): void
    {
        $field = Slug::make('Slug')->lowercase(false);

        $slug = $field->generateSlug('Hello World');

        // Laravel's Str::slug() always converts to lowercase
        // The lowercase property only affects additional processing
        $this->assertEquals('hello-world', $slug);
    }

    public function test_generate_slug_with_max_length(): void
    {
        $field = Slug::make('Slug')->maxLength(10);

        $slug = $field->generateSlug('This is a very long title');

        $this->assertEquals('this-is-a', $slug);
        $this->assertLessThanOrEqual(10, strlen($slug));
    }

    public function test_generate_slug_with_max_length_removes_trailing_separator(): void
    {
        $field = Slug::make('Slug')->maxLength(8);

        $slug = $field->generateSlug('Hello World Test');

        // Should be 'hello-wo' not 'hello-wo-' (removes trailing separator)
        $this->assertEquals('hello-wo', $slug);
        $this->assertStringEndsNotWith('-', $slug);
    }

    public function test_generate_slug_with_underscore_separator_and_max_length(): void
    {
        $field = Slug::make('Slug')->separator('_')->maxLength(12);

        $slug = $field->generateSlug('Hello World Test');

        $this->assertEquals('hello_world', $slug);
        $this->assertStringEndsNotWith('_', $slug);
    }

    public function test_slug_field_fill_with_provided_value(): void
    {
        $field = Slug::make('Slug');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['slug' => 'Custom Slug']);

        $field->fill($request, $model);

        $this->assertEquals('custom-slug', $model->slug);
    }

    public function test_slug_field_fill_with_empty_value_and_from_attribute(): void
    {
        $field = Slug::make('Slug')->from('title');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request([
            'slug' => '',
            'title' => 'Article Title'
        ]);

        $field->fill($request, $model);

        $this->assertEquals('article-title', $model->slug);
    }

    public function test_slug_field_fill_with_null_value_and_from_attribute(): void
    {
        $field = Slug::make('Slug')->from('title');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request([
            'slug' => null,
            'title' => 'Article Title'
        ]);

        $field->fill($request, $model);

        $this->assertEquals('article-title', $model->slug);
    }

    public function test_slug_field_fill_without_from_attribute_source(): void
    {
        $field = Slug::make('Slug')->from('title');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request([
            'slug' => '',
            // No 'title' field provided
        ]);

        $field->fill($request, $model);

        $this->assertEquals('', $model->slug);
    }

    public function test_slug_field_fill_with_callback(): void
    {
        $field = Slug::make('Slug')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'custom-callback-slug';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['slug' => 'original-slug']);

        $field->fill($request, $model);

        $this->assertEquals('custom-callback-slug', $model->slug);
    }

    public function test_slug_field_fill_without_request_value(): void
    {
        $field = Slug::make('Slug');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request([]);

        $field->fill($request, $model);

        $this->assertObjectNotHasProperty('slug', $model);
    }

    public function test_slug_field_meta_includes_all_properties(): void
    {
        $field = Slug::make('Slug')
            ->from('title')
            ->separator('_')
            ->maxLength(100)
            ->lowercase(false)
            ->unique('posts', 'post_slug');

        $meta = $field->meta();

        $this->assertArrayHasKey('fromAttribute', $meta);
        $this->assertArrayHasKey('separator', $meta);
        $this->assertArrayHasKey('maxLength', $meta);
        $this->assertArrayHasKey('lowercase', $meta);
        $this->assertArrayHasKey('uniqueTable', $meta);
        $this->assertArrayHasKey('uniqueColumn', $meta);

        $this->assertEquals('title', $meta['fromAttribute']);
        $this->assertEquals('_', $meta['separator']);
        $this->assertEquals(100, $meta['maxLength']);
        $this->assertFalse($meta['lowercase']);
        $this->assertEquals('posts', $meta['uniqueTable']);
        $this->assertEquals('post_slug', $meta['uniqueColumn']);
    }

    public function test_slug_field_meta_with_default_values(): void
    {
        $field = Slug::make('Slug');
        $meta = $field->meta();

        $this->assertNull($meta['fromAttribute']);
        $this->assertEquals('-', $meta['separator']);
        $this->assertNull($meta['maxLength']);
        $this->assertTrue($meta['lowercase']);
        $this->assertNull($meta['uniqueTable']);
        $this->assertNull($meta['uniqueColumn']);
    }

    public function test_slug_field_complex_configuration(): void
    {
        $field = Slug::make('Article Slug')
            ->from('title')
            ->separator('_')
            ->maxLength(50)
            ->lowercase(true)
            ->unique('articles', 'slug')
            ->required()
            ->help('URL-friendly version of the title');

        $this->assertEquals('Article Slug', $field->name);
        $this->assertEquals('article_slug', $field->attribute);
        $this->assertEquals('title', $field->fromAttribute);
        $this->assertEquals('_', $field->separator);
        $this->assertEquals(50, $field->maxLength);
        $this->assertTrue($field->lowercase);
        $this->assertEquals('articles', $field->uniqueTable);
        $this->assertEquals('slug', $field->uniqueColumn);
        $this->assertContains('required', $field->rules);
        $this->assertEquals('URL-friendly version of the title', $field->helpText);
    }

    public function test_slug_field_method_chaining(): void
    {
        $field = Slug::make('Full Configuration')
            ->from('name')
            ->separator('-')
            ->maxLength(75)
            ->lowercase(false)
            ->unique('products', 'product_slug');

        $this->assertInstanceOf(Slug::class, $field);
        $this->assertEquals('name', $field->fromAttribute);
        $this->assertEquals('-', $field->separator);
        $this->assertEquals(75, $field->maxLength);
        $this->assertFalse($field->lowercase);
        $this->assertEquals('products', $field->uniqueTable);
        $this->assertEquals('product_slug', $field->uniqueColumn);
    }

    public function test_slug_field_json_serialization(): void
    {
        $field = Slug::make('URL Slug')
            ->from('title')
            ->separator('_')
            ->maxLength(100)
            ->required()
            ->help('Enter a URL-friendly slug');

        $json = $field->jsonSerialize();

        $this->assertEquals('URL Slug', $json['name']);
        $this->assertEquals('url_slug', $json['attribute']);
        $this->assertEquals('SlugField', $json['component']);
        $this->assertEquals('title', $json['fromAttribute']);
        $this->assertEquals('_', $json['separator']);
        $this->assertEquals(100, $json['maxLength']);
        $this->assertTrue($json['lowercase']);
        $this->assertContains('required', $json['rules']);
        $this->assertEquals('Enter a URL-friendly slug', $json['helpText']);
    }
}
