<?php

declare(strict_types=1);

namespace E2E\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\URL;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

class URLFieldE2ETest extends TestCase
{
    /** @test */
    public function it_serializes_and_fills_like_nova_in_end_to_end_flow(): void
    {
        // Simulate backend field creation like Nova
        $field = URL::make('Website URL', 'website_url')
            ->help('Enter your website URL')
            ->rules('required', 'url')
            ->placeholder('https://example.com');

        $serialized = $field->jsonSerialize();

        // Verify Nova-compatible serialization
        $this->assertEquals('URLField', $serialized['component']);
        $this->assertEquals('Website URL', $serialized['name']);
        $this->assertEquals('website_url', $serialized['attribute']);
        $this->assertEquals('Enter your website URL', $serialized['helpText']);
        $this->assertEquals('https://example.com', $serialized['placeholder']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('url', $serialized['rules']);

        // Simulate a client update
        $request = new Request(['website_url' => 'https://example.com']);
        $model = (object) ['website_url' => null];
        $field->fill($request, $model);

        // Verify URL is stored correctly
        $this->assertEquals('https://example.com', $model->website_url);
    }

    /** @test */
    public function it_handles_complex_url_scenarios_end_to_end(): void
    {
        $field = URL::make('Website');

        // Test complex URL formats
        $complexUrls = [
            'https://sub.domain.co.uk/path?query=value&other=test#anchor',
            'http://localhost:3000/api/v1/users',
            'https://192.168.1.1:8080/admin',
            'ftp://files.example.com/documents',
            'https://münchen.de/path',
            'https://example.com:443/secure/path?token=abc123&redirect=true#section1'
        ];

        foreach ($complexUrls as $url) {
            $model = (object) ['website' => null];
            $request = new Request(['website' => $url]);
            $field->fill($request, $model);

            $this->assertEquals($url, $model->website, "Failed to handle URL: {$url}");
        }
    }

    /** @test */
    public function it_supports_nova_computed_values_end_to_end(): void
    {
        // Simulate Nova-style computed field
        $field = URL::make('GitHub URL', function ($resource) {
            return 'https://github.com/' . $resource->username;
        });

        $model = (object) ['username' => 'laravel'];
        $field->resolve($model);

        $this->assertEquals('https://github.com/laravel', $field->value);

        // Verify serialization includes computed value
        $serialized = $field->jsonSerialize();
        $this->assertEquals('https://github.com/laravel', $serialized['value']);
    }

    /** @test */
    public function it_supports_nova_display_using_end_to_end(): void
    {
        $field = URL::make('Website', 'website')->displayUsing(function ($value) {
            return parse_url($value, PHP_URL_HOST);
        });

        $model = (object) ['website' => 'https://github.com/laravel/nova'];
        $displayValue = $field->resolveValue($model);

        $this->assertEquals('github.com', $displayValue);
    }

    /** @test */
    public function it_handles_empty_and_null_values_end_to_end(): void
    {
        $field = URL::make('Website');

        // Test empty string conversion to null
        $model = (object) ['website' => 'existing-value'];
        $request = new Request(['website' => '']);
        $field->fill($request, $model);

        $this->assertNull($model->website);

        // Test null value handling
        $model = (object) ['website' => null];
        $field->resolve($model);

        $this->assertNull($field->value);
    }

    /** @test */
    public function it_integrates_with_laravel_validation_end_to_end(): void
    {
        $field = URL::make('Website')
            ->rules('required', 'url', 'active_url', 'max:255')
            ->nullable(false);

        $serialized = $field->jsonSerialize();

        // Verify validation rules are properly serialized
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('url', $serialized['rules']);
        $this->assertContains('active_url', $serialized['rules']);
        $this->assertContains('max:255', $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
    }

    /** @test */
    public function it_supports_custom_fill_callback_end_to_end(): void
    {
        $field = URL::make('Website')->fillUsing(function ($request, $model, $attribute) {
            $value = $request->input($attribute);
            // Custom logic: always ensure https protocol
            if ($value && !str_starts_with($value, 'http')) {
                $value = 'https://' . $value;
            }
            $model->{$attribute} = $value;
        });

        $model = (object) ['website' => null];
        $request = new Request(['website' => 'example.com']);
        $field->fill($request, $model);

        $this->assertEquals('https://example.com', $model->website);
    }

    /** @test */
    public function it_maintains_nova_field_api_compatibility_end_to_end(): void
    {
        // Test full Nova field API compatibility
        $field = URL::make('Company Website', 'company_url')
            ->rules('required', 'url')
            ->help('Enter your company website')
            ->placeholder('https://company.com')
            ->nullable()
            ->sortable()
            ->searchable()
            ->copyable()
            ->displayUsing(function ($value) {
                return $value ? parse_url($value, PHP_URL_HOST) : 'No website';
            });

        // Test serialization includes all Nova field properties
        $serialized = $field->jsonSerialize();

        $this->assertEquals('Company Website', $serialized['name']);
        $this->assertEquals('company_url', $serialized['attribute']);
        $this->assertEquals('URLField', $serialized['component']);
        $this->assertEquals('Enter your company website', $serialized['helpText']);
        $this->assertEquals('https://company.com', $serialized['placeholder']);
        $this->assertTrue($serialized['nullable']);
        $this->assertTrue($serialized['sortable']);
        $this->assertTrue($serialized['searchable']);
        $this->assertTrue($serialized['copyable']);
        $this->assertContains('required', $serialized['rules']);
        $this->assertContains('url', $serialized['rules']);

        // Test field resolution and display
        $model = (object) ['company_url' => 'https://laravel.com/docs'];
        $displayValue = $field->resolveValue($model);
        $this->assertEquals('laravel.com', $displayValue);

        // Test field filling
        $request = new Request(['company_url' => 'https://nova.laravel.com']);
        $field->fill($request, $model);
        $this->assertEquals('https://nova.laravel.com', $model->company_url);
    }

    /** @test */
    public function it_handles_international_domains_end_to_end(): void
    {
        $field = URL::make('Website');

        $internationalUrls = [
            'https://münchen.de',
            'https://пример.рф',
            'https://例え.テスト',
            'https://مثال.إختبار'
        ];

        foreach ($internationalUrls as $url) {
            $model = (object) ['website' => null];
            $request = new Request(['website' => $url]);
            $field->fill($request, $model);

            $this->assertEquals($url, $model->website, "Failed to handle international URL: {$url}");
        }
    }

    /** @test */
    public function it_supports_method_chaining_like_nova_end_to_end(): void
    {
        // Test that all Nova field methods can be chained
        $field = URL::make('Website')
            ->rules('required', 'url')
            ->help('Enter a valid URL')
            ->placeholder('https://example.com')
            ->nullable()
            ->sortable()
            ->searchable()
            ->copyable()
            ->displayUsing(function ($value) {
                return parse_url($value, PHP_URL_HOST);
            })
            ->fillUsing(function ($request, $model, $attribute) {
                $value = $request->input($attribute);
                if ($value && !str_starts_with($value, 'http')) {
                    $value = 'https://' . $value;
                }
                $model->{$attribute} = $value;
            });

        // Verify the field is properly configured
        $this->assertInstanceOf(URL::class, $field);
        $this->assertEquals(['required', 'url'], $field->rules);
        $this->assertEquals('Enter a valid URL', $field->helpText);
        $this->assertEquals('https://example.com', $field->placeholder);
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->sortable);
        $this->assertTrue($field->searchable);
        $this->assertTrue($field->copyable);
        $this->assertNotNull($field->displayCallback);
        $this->assertNotNull($field->fillCallback);

        // Test the configured field works end-to-end
        $model = (object) ['website' => 'https://github.com/laravel'];
        $displayValue = $field->resolveValue($model);
        $this->assertEquals('github.com', $displayValue);

        $request = new Request(['website' => 'example.com']);
        $field->fill($request, $model);
        $this->assertEquals('https://example.com', $model->website);
    }

    /** @test */
    public function it_handles_edge_cases_end_to_end(): void
    {
        $field = URL::make('Website');

        // Test very long URLs
        $longUrl = 'https://example.com/' . str_repeat('a', 1000);
        $model = (object) ['website' => null];
        $request = new Request(['website' => $longUrl]);
        $field->fill($request, $model);
        $this->assertEquals($longUrl, $model->website);

        // Test URLs with special characters
        $specialUrl = 'https://example.com/path?query=value&other=test#anchor';
        $request = new Request(['website' => $specialUrl]);
        $field->fill($request, $model);
        $this->assertEquals($specialUrl, $model->website);

        // Test localhost URLs
        $localhostUrl = 'http://localhost:3000/api/v1/users';
        $request = new Request(['website' => $localhostUrl]);
        $field->fill($request, $model);
        $this->assertEquals($localhostUrl, $model->website);

        // Test IP address URLs
        $ipUrl = 'https://192.168.1.1:8080/admin';
        $request = new Request(['website' => $ipUrl]);
        $field->fill($request, $model);
        $this->assertEquals($ipUrl, $model->website);
    }
}
