<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Fields;

use JTD\AdminPanel\Fields\Badge;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Badge Field Unit Tests
 *
 * Tests for Badge field class with 100% Nova API compatibility.
 * Tests all Nova Badge field features including map, types, addTypes,
 * withIcons, icons, label, and labels methods.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class BadgeFieldTest extends TestCase
{
    /** @test */
    public function it_creates_badge_field_with_nova_syntax(): void
    {
        $field = Badge::make('Status');

        $this->assertEquals('Status', $field->name);
        $this->assertEquals('status', $field->attribute);
        $this->assertEquals('BadgeField', $field->component);
    }

    /** @test */
    public function it_creates_badge_field_with_custom_attribute(): void
    {
        $field = Badge::make('User Status', 'user_status');

        $this->assertEquals('User Status', $field->name);
        $this->assertEquals('user_status', $field->attribute);
    }

    /** @test */
    public function it_has_correct_default_properties(): void
    {
        $field = Badge::make('Status');

        // Test built-in types
        $expectedBuiltInTypes = [
            'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        ];
        $this->assertEquals($expectedBuiltInTypes, $field->builtInTypes);
        
        $this->assertEquals([], $field->valueMap);
        $this->assertEquals([], $field->customTypes);
        $this->assertFalse($field->withIcons);
        $this->assertEquals([], $field->iconMap);
        $this->assertNull($field->labelCallback);
        $this->assertEquals([], $field->labelMap);
    }

    /** @test */
    public function it_supports_nova_map_method(): void
    {
        $valueMap = [
            'draft' => 'danger',
            'published' => 'success',
        ];

        $field = Badge::make('Status')->map($valueMap);

        $this->assertEquals($valueMap, $field->valueMap);
    }

    /** @test */
    public function it_supports_nova_types_method(): void
    {
        $customTypes = [
            'draft' => 'font-medium text-gray-600',
            'published' => ['font-bold', 'text-green-600'],
        ];

        $field = Badge::make('Status')->types($customTypes);

        $this->assertEquals($customTypes, $field->customTypes);
    }

    /** @test */
    public function it_supports_nova_add_types_method(): void
    {
        $initialTypes = [
            'draft' => 'custom classes',
        ];
        
        $additionalTypes = [
            'archived' => 'text-gray-500',
        ];

        $field = Badge::make('Status')
            ->types($initialTypes)
            ->addTypes($additionalTypes);

        $expectedTypes = array_merge($initialTypes, $additionalTypes);
        $this->assertEquals($expectedTypes, $field->customTypes);
    }

    /** @test */
    public function it_supports_nova_with_icons_method(): void
    {
        $field = Badge::make('Status')->withIcons();

        $this->assertTrue($field->withIcons);
    }

    /** @test */
    public function it_supports_nova_icons_method(): void
    {
        $iconMap = [
            'danger' => 'exclamation-circle',
            'success' => 'check-circle',
        ];

        $field = Badge::make('Status')->icons($iconMap);

        $this->assertEquals($iconMap, $field->iconMap);
    }

    /** @test */
    public function it_supports_nova_label_method(): void
    {
        $labelCallback = function ($value) {
            return strtoupper($value);
        };

        $field = Badge::make('Status')->label($labelCallback);

        $this->assertEquals($labelCallback, $field->labelCallback);
    }

    /** @test */
    public function it_supports_nova_labels_method(): void
    {
        $labelMap = [
            'draft' => 'Draft',
            'published' => 'Published',
        ];

        $field = Badge::make('Status')->labels($labelMap);

        $this->assertEquals($labelMap, $field->labelMap);
    }

    /** @test */
    public function it_resolves_badge_type_correctly(): void
    {
        $field = Badge::make('Status')->map([
            'draft' => 'danger',
            'published' => 'success',
        ]);

        $this->assertEquals('danger', $field->resolveBadgeType('draft'));
        $this->assertEquals('success', $field->resolveBadgeType('published'));
        $this->assertEquals('info', $field->resolveBadgeType('unknown')); // Default
    }

    /** @test */
    public function it_resolves_badge_classes_with_built_in_types(): void
    {
        $field = Badge::make('Status');

        $this->assertEquals(
            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            $field->resolveBadgeClasses('info')
        );
        $this->assertEquals(
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            $field->resolveBadgeClasses('success')
        );
    }

    /** @test */
    public function it_resolves_badge_classes_with_custom_types(): void
    {
        $customTypes = [
            'draft' => 'font-medium text-gray-600',
            'published' => 'font-bold text-green-600',
        ];

        $field = Badge::make('Status')->types($customTypes);

        $this->assertEquals('font-medium text-gray-600', $field->resolveBadgeClasses('draft'));
        $this->assertEquals('font-bold text-green-600', $field->resolveBadgeClasses('published'));
        
        // Should fall back to built-in for unknown custom types
        $this->assertEquals(
            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            $field->resolveBadgeClasses('info')
        );
    }

    /** @test */
    public function it_resolves_icons_correctly(): void
    {
        $iconMap = [
            'danger' => 'exclamation-circle',
            'success' => 'check-circle',
        ];

        $field = Badge::make('Status')->icons($iconMap);

        $this->assertEquals('exclamation-circle', $field->resolveIcon('danger'));
        $this->assertEquals('check-circle', $field->resolveIcon('success'));
        $this->assertNull($field->resolveIcon('unknown'));
    }

    /** @test */
    public function it_resolves_labels_with_callback(): void
    {
        $field = Badge::make('Status')->label(function ($value) {
            return strtoupper($value);
        });

        $this->assertEquals('DRAFT', $field->resolveLabel('draft'));
        $this->assertEquals('PUBLISHED', $field->resolveLabel('published'));
    }

    /** @test */
    public function it_resolves_labels_with_mapping(): void
    {
        $labelMap = [
            'draft' => 'Draft',
            'published' => 'Published',
        ];

        $field = Badge::make('Status')->labels($labelMap);

        $this->assertEquals('Draft', $field->resolveLabel('draft'));
        $this->assertEquals('Published', $field->resolveLabel('published'));
        $this->assertEquals('unknown', $field->resolveLabel('unknown')); // Default to value
    }

    /** @test */
    public function it_resolves_labels_with_default_behavior(): void
    {
        $field = Badge::make('Status');

        $this->assertEquals('draft', $field->resolveLabel('draft'));
        $this->assertEquals('published', $field->resolveLabel('published'));
    }

    /** @test */
    public function it_serializes_all_nova_properties_in_meta(): void
    {
        $valueMap = ['draft' => 'danger', 'published' => 'success'];
        $customTypes = ['draft' => 'custom-class'];
        $iconMap = ['danger' => 'exclamation'];
        $labelMap = ['draft' => 'Draft'];

        $field = Badge::make('Status')
            ->map($valueMap)
            ->types($customTypes)
            ->withIcons()
            ->icons($iconMap)
            ->labels($labelMap);

        $meta = $field->meta();

        $this->assertArrayHasKey('builtInTypes', $meta);
        $this->assertEquals($valueMap, $meta['valueMap']);
        $this->assertEquals($customTypes, $meta['customTypes']);
        $this->assertTrue($meta['withIcons']);
        $this->assertEquals($iconMap, $meta['iconMap']);
        $this->assertEquals($labelMap, $meta['labelMap']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Badge::make('Status')
            ->map(['draft' => 'danger', 'published' => 'success'])
            ->types(['draft' => 'custom-class'])
            ->addTypes(['archived' => 'gray-class'])
            ->withIcons()
            ->icons(['danger' => 'exclamation'])
            ->labels(['draft' => 'Draft']);

        $this->assertInstanceOf(Badge::class, $field);
        $this->assertEquals(['draft' => 'danger', 'published' => 'success'], $field->valueMap);
        $this->assertEquals(['draft' => 'custom-class', 'archived' => 'gray-class'], $field->customTypes);
        $this->assertTrue($field->withIcons);
        $this->assertEquals(['danger' => 'exclamation'], $field->iconMap);
        $this->assertEquals(['draft' => 'Draft'], $field->labelMap);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_badge_field(): void
    {
        $field = Badge::make('Status');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Badge::class, $field->map([]));
        $this->assertInstanceOf(Badge::class, $field->types([]));
        $this->assertInstanceOf(Badge::class, $field->addTypes([]));
        $this->assertInstanceOf(Badge::class, $field->withIcons());
        $this->assertInstanceOf(Badge::class, $field->icons([]));
        $this->assertInstanceOf(Badge::class, $field->label(fn($v) => $v));
        $this->assertInstanceOf(Badge::class, $field->labels([]));
        
        // Test component name matches Nova
        $this->assertEquals('BadgeField', $field->component);
        
        // Test built-in badge types match Nova
        $this->assertArrayHasKey('info', $field->builtInTypes);
        $this->assertArrayHasKey('success', $field->builtInTypes);
        $this->assertArrayHasKey('danger', $field->builtInTypes);
        $this->assertArrayHasKey('warning', $field->builtInTypes);
    }

    /** @test */
    public function it_handles_complex_nova_configuration(): void
    {
        $field = Badge::make('Post Status')
            ->map([
                'draft' => 'danger',
                'published' => 'success',
                'archived' => 'warning',
            ])
            ->types([
                'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
                'success' => 'bg-green-50 text-green-700 ring-green-600/20',
            ])
            ->addTypes([
                'warning' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20',
            ])
            ->withIcons()
            ->icons([
                'danger' => 'exclamation-triangle',
                'success' => 'check-circle',
                'warning' => 'exclamation-circle',
            ])
            ->labels([
                'draft' => 'Draft Post',
                'published' => 'Published Post',
                'archived' => 'Archived Post',
            ]);

        // Test all configurations are set correctly
        $this->assertEquals('danger', $field->resolveBadgeType('draft'));
        $this->assertEquals('bg-red-50 text-red-700 ring-red-600/20', $field->resolveBadgeClasses('danger'));
        $this->assertEquals('exclamation-triangle', $field->resolveIcon('danger'));
        $this->assertEquals('Draft Post', $field->resolveLabel('draft'));
        
        $this->assertTrue($field->withIcons);
        $this->assertCount(3, $field->valueMap);
        $this->assertCount(3, $field->customTypes);
        $this->assertCount(3, $field->iconMap);
        $this->assertCount(3, $field->labelMap);
    }
}
