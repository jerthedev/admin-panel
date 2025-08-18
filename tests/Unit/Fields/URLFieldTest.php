<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\URL;
use JTD\AdminPanel\Tests\TestCase;

/**
 * URL Field Unit Tests.
 *
 * Tests for URL field class with Nova API compatibility.
 * Tests basic field creation, displayUsing callback, and computed values.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class URLFieldTest extends TestCase
{
    public function test_url_field_creation(): void
    {
        $field = URL::make('Website');

        $this->assertEquals('Website', $field->name);
        $this->assertEquals('website', $field->attribute);
        $this->assertEquals('URLField', $field->component);
    }

    public function test_url_field_with_custom_attribute(): void
    {
        $field = URL::make('Company Website', 'company_url');

        $this->assertEquals('Company Website', $field->name);
        $this->assertEquals('company_url', $field->attribute);
    }

    public function test_url_field_with_resolve_callback(): void
    {
        $field = URL::make('GitHub URL', function ($resource) {
            return 'https://github.com/'.$resource->username;
        });

        $this->assertEquals('GitHub URL', $field->name);
        $this->assertEquals('github_url', $field->attribute);
        $this->assertNotNull($field->resolveCallback);
    }

    public function test_url_field_display_using_callback(): void
    {
        $field = URL::make('GitHub URL')->displayUsing(function ($value) {
            return parse_url($value, PHP_URL_HOST);
        });

        $resource = (object) ['github_url' => 'https://github.com/laravel/nova'];
        $displayValue = $field->resolveValue($resource);

        $this->assertEquals('github.com', $displayValue);
    }

    public function test_url_field_display_using_callback_with_resource_access(): void
    {
        $field = URL::make('Profile URL')->displayUsing(function ($value, $resource) {
            return "Visit {$resource->name}'s profile";
        });

        $resource = (object) [
            'profile_url' => 'https://example.com/profile/john',
            'name' => 'John Doe',
        ];
        $displayValue = $field->resolveValue($resource);

        $this->assertEquals("Visit John Doe's profile", $displayValue);
    }

    public function test_url_field_computed_value_with_closure(): void
    {
        $field = URL::make('GitHub URL', function ($resource) {
            return 'https://github.com/'.$resource->username;
        });

        $resource = (object) ['username' => 'laravel'];
        $field->resolve($resource);

        $this->assertEquals('https://github.com/laravel', $field->value);
    }

    public function test_url_field_computed_value_with_display_callback(): void
    {
        $field = URL::make('GitHub URL', function ($resource) {
            return 'https://github.com/'.$resource->username;
        })->displayUsing(function ($value) {
            return 'Visit GitHub Profile';
        });

        $resource = (object) ['username' => 'laravel'];
        $displayValue = $field->resolveValue($resource);

        $this->assertEquals('Visit GitHub Profile', $displayValue);
    }

    public function test_url_field_basic_value_resolution(): void
    {
        $field = URL::make('Website');
        $resource = (object) ['website' => 'https://example.com'];

        $field->resolve($resource);

        $this->assertEquals('https://example.com', $field->value);
    }

    public function test_url_field_fill_basic_functionality(): void
    {
        $field = URL::make('Website');
        $model = new \stdClass;
        $request = new \Illuminate\Http\Request(['website' => 'https://example.com']);

        $field->fill($request, $model);

        $this->assertEquals('https://example.com', $model->website);
    }

    public function test_url_field_fill_handles_empty_values(): void
    {
        $field = URL::make('Website');
        $model = new \stdClass;
        $request = new \Illuminate\Http\Request(['website' => '']);

        $field->fill($request, $model);

        $this->assertNull($model->website);
    }

    public function test_url_field_fill_with_callback(): void
    {
        $field = URL::make('Website')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'https://custom.com';
        });
        $model = new \stdClass;
        $request = new \Illuminate\Http\Request(['website' => 'example.com']);

        $field->fill($request, $model);

        $this->assertEquals('https://custom.com', $model->website);
    }

    public function test_url_field_inherits_base_field_functionality(): void
    {
        $field = URL::make('Website')
            ->rules('required', 'url')
            ->help('Enter a valid URL')
            ->placeholder('https://example.com')
            ->nullable();

        $this->assertEquals(['required', 'url'], $field->rules);
        $this->assertEquals('Enter a valid URL', $field->helpText);
        $this->assertEquals('https://example.com', $field->placeholder);
        $this->assertTrue($field->nullable);
    }

    public function test_url_field_serialization(): void
    {
        $field = URL::make('Website')
            ->displayUsing(function ($value) {
                return parse_url($value, PHP_URL_HOST);
            });

        $resource = (object) ['website' => 'https://example.com'];
        $field->resolve($resource); // Need to resolve first to set the value
        $serialized = $field->jsonSerialize();

        $this->assertEquals('Website', $serialized['name']);
        $this->assertEquals('website', $serialized['attribute']);
        $this->assertEquals('URLField', $serialized['component']);
        $this->assertEquals('https://example.com', $serialized['value']);
    }
}
