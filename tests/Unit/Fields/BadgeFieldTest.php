<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Badge;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Badge Field Unit Tests
 *
 * Tests for Badge field class including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class BadgeFieldTest extends TestCase
{
    public function test_badge_field_creation(): void
    {
        $field = Badge::make('Status');

        $this->assertEquals('Status', $field->name);
        $this->assertEquals('status', $field->attribute);
        $this->assertEquals('BadgeField', $field->component);
    }

    public function test_badge_field_with_custom_attribute(): void
    {
        $field = Badge::make('User Status', 'user_status');

        $this->assertEquals('User Status', $field->name);
        $this->assertEquals('user_status', $field->attribute);
    }

    public function test_badge_field_default_properties(): void
    {
        $field = Badge::make('Status');

        $this->assertEquals([], $field->colorMap);
        $this->assertEquals('secondary', $field->defaultColor);
        $this->assertFalse($field->showIcons);
        $this->assertEquals([], $field->iconMap);
        $this->assertEquals('solid', $field->style);
        $this->assertEquals('medium', $field->size);
    }

    public function test_badge_field_color_map_configuration(): void
    {
        $colorMap = [
            'active' => 'success',
            'inactive' => 'danger',
            'pending' => 'warning',
        ];

        $field = Badge::make('Status')->map($colorMap);

        $this->assertEquals($colorMap, $field->colorMap);
    }

    public function test_badge_field_default_color_configuration(): void
    {
        $field = Badge::make('Status')->defaultColor('primary');

        $this->assertEquals('primary', $field->defaultColor);
    }

    public function test_badge_field_with_icons_configuration(): void
    {
        $field = Badge::make('Status')->withIcons();

        $this->assertTrue($field->showIcons);
    }

    public function test_badge_field_with_icons_false(): void
    {
        $field = Badge::make('Status')->withIcons(false);

        $this->assertFalse($field->showIcons);
    }

    public function test_badge_field_icon_map_configuration(): void
    {
        $iconMap = [
            'active' => 'check-circle',
            'inactive' => 'x-circle',
            'pending' => 'clock',
        ];

        $field = Badge::make('Status')->iconMap($iconMap);

        $this->assertEquals($iconMap, $field->iconMap);
    }

    public function test_badge_field_style_configuration(): void
    {
        $field = Badge::make('Status')->style('outline');

        $this->assertEquals('outline', $field->style);
    }

    public function test_badge_field_size_configuration(): void
    {
        $field = Badge::make('Status')->size('large');

        $this->assertEquals('large', $field->size);
    }

    public function test_badge_field_resolve_color_with_mapping(): void
    {
        $colorMap = [
            'active' => 'success',
            'inactive' => 'danger',
        ];

        $field = Badge::make('Status')->map($colorMap);

        $this->assertEquals('success', $field->resolveColor('active'));
        $this->assertEquals('danger', $field->resolveColor('inactive'));
    }

    public function test_badge_field_resolve_color_with_default(): void
    {
        $colorMap = [
            'active' => 'success',
        ];

        $field = Badge::make('Status')
            ->map($colorMap)
            ->defaultColor('warning');

        $this->assertEquals('warning', $field->resolveColor('unknown'));
    }

    public function test_badge_field_resolve_icon_with_mapping(): void
    {
        $iconMap = [
            'active' => 'check-circle',
            'inactive' => 'x-circle',
        ];

        $field = Badge::make('Status')->iconMap($iconMap);

        $this->assertEquals('check-circle', $field->resolveIcon('active'));
        $this->assertEquals('x-circle', $field->resolveIcon('inactive'));
    }

    public function test_badge_field_resolve_icon_with_no_mapping(): void
    {
        $iconMap = [
            'active' => 'check-circle',
        ];

        $field = Badge::make('Status')->iconMap($iconMap);

        $this->assertNull($field->resolveIcon('unknown'));
    }

    public function test_badge_field_resolve_icon_empty_map(): void
    {
        $field = Badge::make('Status');

        $this->assertNull($field->resolveIcon('active'));
    }

    public function test_badge_field_meta_includes_all_properties(): void
    {
        $colorMap = ['active' => 'success', 'inactive' => 'danger'];
        $iconMap = ['active' => 'check', 'inactive' => 'x'];

        $field = Badge::make('Status')
            ->map($colorMap)
            ->defaultColor('primary')
            ->withIcons()
            ->iconMap($iconMap)
            ->style('outline')
            ->size('large');

        $meta = $field->meta();

        $this->assertArrayHasKey('colorMap', $meta);
        $this->assertArrayHasKey('defaultColor', $meta);
        $this->assertArrayHasKey('showIcons', $meta);
        $this->assertArrayHasKey('iconMap', $meta);
        $this->assertArrayHasKey('style', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertEquals($colorMap, $meta['colorMap']);
        $this->assertEquals('primary', $meta['defaultColor']);
        $this->assertTrue($meta['showIcons']);
        $this->assertEquals($iconMap, $meta['iconMap']);
        $this->assertEquals('outline', $meta['style']);
        $this->assertEquals('large', $meta['size']);
    }

    public function test_badge_field_json_serialization(): void
    {
        $colorMap = ['published' => 'success', 'draft' => 'warning'];
        $iconMap = ['published' => 'check', 'draft' => 'edit'];

        $field = Badge::make('Post Status')
            ->map($colorMap)
            ->defaultColor('secondary')
            ->withIcons()
            ->iconMap($iconMap)
            ->style('pill')
            ->size('small')
            ->help('Post publication status');

        $json = $field->jsonSerialize();

        $this->assertEquals('Post Status', $json['name']);
        $this->assertEquals('post_status', $json['attribute']);
        $this->assertEquals('BadgeField', $json['component']);
        $this->assertEquals($colorMap, $json['colorMap']);
        $this->assertEquals('secondary', $json['defaultColor']);
        $this->assertTrue($json['showIcons']);
        $this->assertEquals($iconMap, $json['iconMap']);
        $this->assertEquals('pill', $json['style']);
        $this->assertEquals('small', $json['size']);
        $this->assertEquals('Post publication status', $json['helpText']);
    }

    public function test_badge_field_complex_configuration(): void
    {
        $field = Badge::make('Order Status')
            ->map([
                'pending' => 'warning',
                'processing' => 'info',
                'shipped' => 'primary',
                'delivered' => 'success',
                'cancelled' => 'danger',
            ])
            ->iconMap([
                'pending' => 'clock',
                'processing' => 'cog',
                'shipped' => 'truck',
                'delivered' => 'check-circle',
                'cancelled' => 'x-circle',
            ])
            ->withIcons()
            ->style('outline')
            ->size('large')
            ->defaultColor('muted');

        // Test color resolution
        $this->assertEquals('warning', $field->resolveColor('pending'));
        $this->assertEquals('success', $field->resolveColor('delivered'));
        $this->assertEquals('muted', $field->resolveColor('unknown'));

        // Test icon resolution
        $this->assertEquals('clock', $field->resolveIcon('pending'));
        $this->assertEquals('check-circle', $field->resolveIcon('delivered'));
        $this->assertNull($field->resolveIcon('unknown'));

        // Test configuration
        $this->assertTrue($field->showIcons);
        $this->assertEquals('outline', $field->style);
        $this->assertEquals('large', $field->size);
    }

    public function test_badge_field_inheritance_from_field(): void
    {
        $field = Badge::make('Status');

        // Test that Badge field inherits all base Field functionality
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'sortable'));
    }

    public function test_badge_field_with_validation_rules(): void
    {
        $field = Badge::make('Status')
            ->rules('in:active,inactive,pending');

        $this->assertEquals(['in:active,inactive,pending'], $field->rules);
    }

    public function test_badge_field_resolve_preserves_value(): void
    {
        $field = Badge::make('Status');
        $resource = (object) ['status' => 'active'];

        $field->resolve($resource);

        $this->assertEquals('active', $field->value);
    }
}
