<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\URL;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

class URLFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_url_field_with_nova_syntax(): void
    {
        $field = URL::make('Website');

        $this->assertEquals('Website', $field->name);
        $this->assertEquals('website', $field->attribute);
        $this->assertEquals('URLField', $field->component);
    }

    /** @test */
    public function it_creates_url_field_with_custom_attribute(): void
    {
        $field = URL::make('Company Website', 'company_url');

        $this->assertEquals('Company Website', $field->name);
        $this->assertEquals('company_url', $field->attribute);
        $this->assertEquals('URLField', $field->component);
    }

    /** @test */
    public function it_resolves_and_fills_values(): void
    {
        $model = (object) ['website' => 'https://example.com'];

        $field = URL::make('Website', 'website');
        $field->resolve($model);
        $this->assertEquals('https://example.com', $field->value);

        $request = new Request(['website' => 'https://newsite.com']);
        $field->fill($request, $model);
        $this->assertEquals('https://newsite.com', $model->website);
    }

    /** @test */
    public function it_handles_empty_values_correctly(): void
    {
        $model = (object) ['website' => null];

        $field = URL::make('Website', 'website');
        $request = new Request(['website' => '']);
        $field->fill($request, $model);

        $this->assertNull($model->website);
    }

    /** @test */
    public function it_supports_nova_computed_values(): void
    {
        $model = (object) ['username' => 'johndoe'];

        $field = URL::make('GitHub URL', function ($resource) {
            return 'https://github.com/' . $resource->username;
        });

        $field->resolve($model);
        $this->assertEquals('https://github.com/johndoe', $field->value);
    }

    /** @test */
    public function it_supports_nova_display_using_callback(): void
    {
        $model = (object) ['website' => 'https://github.com/laravel/nova'];

        $field = URL::make('Website', 'website')->displayUsing(function ($value) {
            return parse_url($value, PHP_URL_HOST);
        });

        $displayValue = $field->resolveValue($model);
        $this->assertEquals('github.com', $displayValue);
    }

    /** @test */
    public function it_supports_display_using_with_resource_access(): void
    {
        $model = (object) [
            'name' => 'John Doe',
            'website' => 'https://example.com/profile/john'
        ];

        $field = URL::make('Profile URL', 'website')->displayUsing(function ($value, $resource) {
            return "Visit {$resource->name}'s profile";
        });

        $displayValue = $field->resolveValue($model);
        $this->assertEquals("Visit John Doe's profile", $displayValue);
    }

    /** @test */
    public function it_integrates_with_laravel_validation(): void
    {
        $field = URL::make('Website')
            ->rules('required', 'url', 'active_url');

        $rules = $field->rules;

        $this->assertContains('required', $rules);
        $this->assertContains('url', $rules);
        $this->assertContains('active_url', $rules);
    }

    /** @test */
    public function it_serializes_correctly_for_frontend(): void
    {
        $model = (object) ['website' => 'https://example.com'];

        $field = URL::make('Website', 'website')
            ->rules('required', 'url')
            ->help('Enter your website URL')
            ->placeholder('https://example.com');

        $field->resolve($model);
        $serialized = $field->jsonSerialize();

        $this->assertEquals('Website', $serialized['name']);
        $this->assertEquals('website', $serialized['attribute']);
        $this->assertEquals('URLField', $serialized['component']);
        $this->assertEquals('https://example.com', $serialized['value']);
        $this->assertEquals(['required', 'url'], $serialized['rules']);
        $this->assertEquals('Enter your website URL', $serialized['helpText']);
        $this->assertEquals('https://example.com', $serialized['placeholder']);
    }

    /** @test */
    public function it_handles_complex_url_formats(): void
    {
        $model = (object) ['website' => null];
        $field = URL::make('Website', 'website');

        $complexUrls = [
            'https://sub.domain.co.uk/path?query=value&other=test#anchor',
            'http://localhost:3000/api/v1/users',
            'https://192.168.1.1:8080/admin',
            'ftp://files.example.com/documents',
        ];

        foreach ($complexUrls as $url) {
            $request = new Request(['website' => $url]);
            $field->fill($request, $model);
            $this->assertEquals($url, $model->website);
        }
    }

    /** @test */
    public function it_works_with_fillusing_callback(): void
    {
        $model = (object) ['website' => null];

        $field = URL::make('Website', 'website')->fillUsing(function ($request, $model, $attribute) {
            $value = $request->input($attribute);
            // Custom logic: always ensure https protocol
            if ($value && !str_starts_with($value, 'http')) {
                $value = 'https://' . $value;
            }
            $model->{$attribute} = $value;
        });

        $request = new Request(['website' => 'example.com']);
        $field->fill($request, $model);

        $this->assertEquals('https://example.com', $model->website);
    }

    /** @test */
    public function it_maintains_nova_field_inheritance(): void
    {
        $field = URL::make('Website')
            ->nullable()
            ->sortable()
            ->searchable()
            ->copyable()
            ->help('Enter a valid URL')
            ->placeholder('https://example.com');

        $this->assertTrue($field->nullable);
        $this->assertTrue($field->sortable);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->copyable);
        $this->assertEquals('Enter a valid URL', $field->helpText);
        $this->assertEquals('https://example.com', $field->placeholder);
    }

    /** @test */
    public function it_handles_null_and_empty_values_in_display(): void
    {
        $model = (object) ['website' => null];

        $field = URL::make('Website', 'website')->displayUsing(function ($value) {
            return $value ? parse_url($value, PHP_URL_HOST) : 'No website';
        });

        $displayValue = $field->resolveValue($model);
        $this->assertEquals('No website', $displayValue);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = URL::make('Website')
            ->rules('required', 'url')
            ->help('Enter your website')
            ->placeholder('https://example.com')
            ->nullable()
            ->sortable()
            ->displayUsing(function ($value) {
                return parse_url($value, PHP_URL_HOST);
            });

        $this->assertInstanceOf(URL::class, $field);
        $this->assertEquals(['required', 'url'], $field->rules);
        $this->assertEquals('Enter your website', $field->helpText);
        $this->assertEquals('https://example.com', $field->placeholder);
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->sortable);
        $this->assertNotNull($field->displayCallback);
    }
}
