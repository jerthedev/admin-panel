<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\URL;
use JTD\AdminPanel\Tests\TestCase;

/**
 * URL Field Unit Tests
 *
 * Tests for URL field class including validation, visibility,
 * and value handling.
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

    public function test_url_field_default_properties(): void
    {
        $field = URL::make('Website');

        $this->assertFalse($field->clickable);
        $this->assertEquals('_self', $field->target);
        $this->assertNull($field->linkText);
        $this->assertFalse($field->showFavicon);
        $this->assertEquals('https', $field->protocol);
        $this->assertTrue($field->validateUrl);
        $this->assertFalse($field->normalizeProtocol);
        $this->assertFalse($field->showPreview);
        $this->assertNull($field->maxLength);
    }

    public function test_url_field_clickable(): void
    {
        $field = URL::make('Website')->clickable();

        $this->assertTrue($field->clickable);
    }

    public function test_url_field_clickable_false(): void
    {
        $field = URL::make('Website')->clickable(false);

        $this->assertFalse($field->clickable);
    }

    public function test_url_field_target(): void
    {
        $field = URL::make('Website')->target('_blank');

        $this->assertEquals('_blank', $field->target);
    }

    public function test_url_field_link_text(): void
    {
        $field = URL::make('Website')->linkText('Visit Site');

        $this->assertEquals('Visit Site', $field->linkText);
    }

    public function test_url_field_link_text_callback(): void
    {
        $field = URL::make('Website')->linkTextUsing(function ($url) {
            return 'Custom: ' . $url;
        });

        $this->assertNotNull($field->linkTextCallback);
    }

    public function test_url_field_show_favicon(): void
    {
        $field = URL::make('Website')->showFavicon();

        $this->assertTrue($field->showFavicon);
    }

    public function test_url_field_show_favicon_false(): void
    {
        $field = URL::make('Website')->showFavicon(false);

        $this->assertFalse($field->showFavicon);
    }

    public function test_url_field_protocol(): void
    {
        $field = URL::make('Website')->protocol('http');

        $this->assertEquals('http', $field->protocol);
    }

    public function test_url_field_validate_url(): void
    {
        $field = URL::make('Website')->validateUrl();

        $this->assertTrue($field->validateUrl);
    }

    public function test_url_field_validate_url_false(): void
    {
        $field = URL::make('Website')->validateUrl(false);

        $this->assertFalse($field->validateUrl);
    }

    public function test_url_field_normalize_protocol(): void
    {
        $field = URL::make('Website')->normalizeProtocol();

        $this->assertTrue($field->normalizeProtocol);
    }

    public function test_url_field_normalize_protocol_false(): void
    {
        $field = URL::make('Website')->normalizeProtocol(false);

        $this->assertFalse($field->normalizeProtocol);
    }

    public function test_url_field_show_preview(): void
    {
        $field = URL::make('Website')->showPreview();

        $this->assertTrue($field->showPreview);
    }

    public function test_url_field_show_preview_false(): void
    {
        $field = URL::make('Website')->showPreview(false);

        $this->assertFalse($field->showPreview);
    }

    public function test_url_field_max_length(): void
    {
        $field = URL::make('Website')->maxLength(255);

        $this->assertEquals(255, $field->maxLength);
    }

    public function test_url_field_normalization_through_fill(): void
    {
        $field = URL::make('Website')->normalizeProtocol();
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['website' => 'example.com']);

        $field->fill($request, $model);

        $this->assertEquals('https://example.com', $model->website);
    }

    public function test_url_field_normalization_preserves_existing_protocol(): void
    {
        $field = URL::make('Website')->normalizeProtocol();
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['website' => 'http://example.com']);

        $field->fill($request, $model);

        $this->assertEquals('http://example.com', $model->website);
    }

    public function test_url_field_normalization_with_custom_protocol(): void
    {
        $field = URL::make('Website')->protocol('http')->normalizeProtocol();
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['website' => 'example.com']);

        $field->fill($request, $model);

        $this->assertEquals('http://example.com', $model->website);
    }

    public function test_url_field_get_link_text_with_callback(): void
    {
        $field = URL::make('Website')->linkTextUsing(function ($url) {
            return 'Visit: ' . $url;
        });

        $linkText = $field->getLinkText('https://example.com');

        $this->assertEquals('Visit: https://example.com', $linkText);
    }

    public function test_url_field_get_link_text_with_static_text(): void
    {
        $field = URL::make('Website')->linkText('Visit Site');

        $linkText = $field->getLinkText('https://example.com');

        $this->assertEquals('Visit Site', $linkText);
    }

    public function test_url_field_get_link_text_default_to_domain(): void
    {
        $field = URL::make('Website');

        $linkText = $field->getLinkText('https://example.com/path');

        $this->assertEquals('example.com', $linkText);
    }

    public function test_url_field_get_link_text_returns_null_for_empty_url(): void
    {
        $field = URL::make('Website');

        $linkText = $field->getLinkText(null);

        $this->assertNull($linkText);
    }

    public function test_url_field_fill_normalizes_url_when_enabled(): void
    {
        $field = URL::make('Website')->normalizeProtocol();
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['website' => 'example.com']);

        $field->fill($request, $model);

        $this->assertEquals('https://example.com', $model->website);
    }

    public function test_url_field_fill_preserves_url_when_normalization_disabled(): void
    {
        $field = URL::make('Website');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['website' => 'example.com']);

        $field->fill($request, $model);

        $this->assertEquals('example.com', $model->website);
    }

    public function test_url_field_fill_handles_empty_values(): void
    {
        $field = URL::make('Website');
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['website' => '']);

        $field->fill($request, $model);

        $this->assertNull($model->website);
    }

    public function test_url_field_fill_with_callback(): void
    {
        $field = URL::make('Website')->fillUsing(function ($request, $model, $attribute) {
            $model->{$attribute} = 'https://custom.com';
        });
        $model = new \stdClass();
        $request = new \Illuminate\Http\Request(['website' => 'example.com']);

        $field->fill($request, $model);

        $this->assertEquals('https://custom.com', $model->website);
    }

    public function test_url_field_resolve_normalizes_value(): void
    {
        $field = URL::make('Website')->normalizeProtocol();
        $resource = (object) ['website' => 'example.com'];

        $field->resolve($resource);

        $this->assertEquals('https://example.com', $field->value);
    }

    public function test_url_field_meta_includes_all_properties(): void
    {
        $field = URL::make('Website')
            ->clickable()
            ->target('_blank')
            ->linkText('Visit')
            ->showFavicon()
            ->protocol('http')
            ->validateUrl(false)
            ->normalizeProtocol()
            ->showPreview()
            ->maxLength(500);

        $meta = $field->meta();

        $this->assertArrayHasKey('clickable', $meta);
        $this->assertArrayHasKey('target', $meta);
        $this->assertArrayHasKey('linkText', $meta);
        $this->assertArrayHasKey('showFavicon', $meta);
        $this->assertArrayHasKey('protocol', $meta);
        $this->assertArrayHasKey('validateUrl', $meta);
        $this->assertArrayHasKey('normalizeProtocol', $meta);
        $this->assertArrayHasKey('showPreview', $meta);
        $this->assertArrayHasKey('maxLength', $meta);
        $this->assertTrue($meta['clickable']);
        $this->assertEquals('_blank', $meta['target']);
        $this->assertEquals('Visit', $meta['linkText']);
        $this->assertTrue($meta['showFavicon']);
        $this->assertEquals('http', $meta['protocol']);
        $this->assertFalse($meta['validateUrl']);
        $this->assertTrue($meta['normalizeProtocol']);
        $this->assertTrue($meta['showPreview']);
        $this->assertEquals(500, $meta['maxLength']);
    }

    public function test_url_field_get_favicon_url_with_https(): void
    {
        $field = URL::make('Website');

        $faviconUrl = $field->getFaviconUrl('https://example.com/path');

        $this->assertEquals('https://example.com/favicon.ico', $faviconUrl);
    }

    public function test_url_field_get_favicon_url_with_http(): void
    {
        $field = URL::make('Website');

        $faviconUrl = $field->getFaviconUrl('http://example.com');

        $this->assertEquals('http://example.com/favicon.ico', $faviconUrl);
    }

    public function test_url_field_get_favicon_url_without_scheme(): void
    {
        $field = URL::make('Website');

        $faviconUrl = $field->getFaviconUrl('example.com');

        // URLs without scheme can't be parsed properly, so returns null
        $this->assertNull($faviconUrl);
    }

    public function test_url_field_get_favicon_url_with_null(): void
    {
        $field = URL::make('Website');

        $faviconUrl = $field->getFaviconUrl(null);

        $this->assertNull($faviconUrl);
    }

    public function test_url_field_get_favicon_url_with_empty_string(): void
    {
        $field = URL::make('Website');

        $faviconUrl = $field->getFaviconUrl('');

        $this->assertNull($faviconUrl);
    }

    public function test_url_field_get_favicon_url_with_invalid_url(): void
    {
        $field = URL::make('Website');

        $faviconUrl = $field->getFaviconUrl('invalid-url');

        $this->assertNull($faviconUrl);
    }

    public function test_url_field_get_favicon_url_uses_field_value(): void
    {
        $field = URL::make('Website');
        $field->value = 'https://example.com';

        $faviconUrl = $field->getFaviconUrl();

        $this->assertEquals('https://example.com/favicon.ico', $faviconUrl);
    }

    public function test_url_field_get_favicon_url_with_subdomain(): void
    {
        $field = URL::make('Website');

        $faviconUrl = $field->getFaviconUrl('https://blog.example.com/article');

        $this->assertEquals('https://blog.example.com/favicon.ico', $faviconUrl);
    }
}
