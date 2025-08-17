<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Badge;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Badge Field Integration Test
 *
 * Tests the complete integration between PHP Badge field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 *
 * Focuses on field configuration and behavior rather than
 * database operations, testing the Nova API integration.
 */
class BadgeFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different status values
        User::factory()->create(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
        User::factory()->create(['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']);
        User::factory()->create(['id' => 3, 'name' => 'Bob Wilson', 'email' => 'bob@example.com']);
    }

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
        $field = Badge::make('Post Status', 'post_status');

        $this->assertEquals('Post Status', $field->name);
        $this->assertEquals('post_status', $field->attribute);
    }

    /** @test */
    public function it_supports_all_nova_badge_configuration_methods(): void
    {
        $field = Badge::make('Status')
            ->map(['draft' => 'danger', 'published' => 'success'])
            ->types(['danger' => 'bg-red-50 text-red-700'])
            ->addTypes(['warning' => 'bg-yellow-50 text-yellow-700'])
            ->withIcons()
            ->icons(['danger' => 'exclamation-triangle'])
            ->labels(['draft' => 'Draft Post'])
            ->nullable()
            ->help('Select the post status');

        $this->assertEquals(['draft' => 'danger', 'published' => 'success'], $field->valueMap);
        $this->assertEquals(['danger' => 'bg-red-50 text-red-700', 'warning' => 'bg-yellow-50 text-yellow-700'], $field->customTypes);
        $this->assertTrue($field->withIcons);
        $this->assertEquals(['danger' => 'exclamation-triangle'], $field->iconMap);
        $this->assertEquals(['draft' => 'Draft Post'], $field->labelMap);
    }

    /** @test */
    public function it_supports_nova_map_method(): void
    {
        $valueMap = [
            'draft' => 'danger',
            'published' => 'success',
            'archived' => 'warning',
        ];

        $field = Badge::make('Status')->map($valueMap);

        $this->assertEquals($valueMap, $field->valueMap);
        $this->assertEquals('danger', $field->resolveBadgeType('draft'));
        $this->assertEquals('success', $field->resolveBadgeType('published'));
        $this->assertEquals('warning', $field->resolveBadgeType('archived'));
    }

    /** @test */
    public function it_supports_nova_types_method(): void
    {
        $customTypes = [
            'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
            'success' => 'bg-green-50 text-green-700 ring-green-600/20',
        ];

        $field = Badge::make('Status')->types($customTypes);

        $this->assertEquals($customTypes, $field->customTypes);
        $this->assertEquals('bg-red-50 text-red-700 ring-red-600/20', $field->resolveBadgeClasses('danger'));
        $this->assertEquals('bg-green-50 text-green-700 ring-green-600/20', $field->resolveBadgeClasses('success'));
    }

    /** @test */
    public function it_supports_nova_add_types_method(): void
    {
        $initialTypes = [
            'danger' => 'bg-red-50 text-red-700',
        ];

        $additionalTypes = [
            'warning' => 'bg-yellow-50 text-yellow-700',
            'info' => 'bg-blue-50 text-blue-700',
        ];

        $field = Badge::make('Status')
            ->types($initialTypes)
            ->addTypes($additionalTypes);

        $expectedTypes = array_merge($initialTypes, $additionalTypes);
        $this->assertEquals($expectedTypes, $field->customTypes);
        $this->assertEquals('bg-red-50 text-red-700', $field->resolveBadgeClasses('danger'));
        $this->assertEquals('bg-yellow-50 text-yellow-700', $field->resolveBadgeClasses('warning'));
        $this->assertEquals('bg-blue-50 text-blue-700', $field->resolveBadgeClasses('info'));
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
            'danger' => 'exclamation-triangle',
            'success' => 'check-circle',
            'warning' => 'exclamation-circle',
        ];

        $field = Badge::make('Status')->icons($iconMap);

        $this->assertEquals($iconMap, $field->iconMap);
        $this->assertEquals('exclamation-triangle', $field->resolveIcon('danger'));
        $this->assertEquals('check-circle', $field->resolveIcon('success'));
        $this->assertEquals('exclamation-circle', $field->resolveIcon('warning'));
    }

    /** @test */
    public function it_supports_nova_label_method(): void
    {
        $labelCallback = function ($value) {
            return strtoupper($value) . ' STATUS';
        };

        $field = Badge::make('Status')->label($labelCallback);

        $this->assertEquals($labelCallback, $field->labelCallback);
        $this->assertEquals('DRAFT STATUS', $field->resolveLabel('draft'));
        $this->assertEquals('PUBLISHED STATUS', $field->resolveLabel('published'));
    }

    /** @test */
    public function it_supports_nova_labels_method(): void
    {
        $labelMap = [
            'draft' => 'Draft Article',
            'published' => 'Published Article',
            'archived' => 'Archived Article',
        ];

        $field = Badge::make('Status')->labels($labelMap);

        $this->assertEquals($labelMap, $field->labelMap);
        $this->assertEquals('Draft Article', $field->resolveLabel('draft'));
        $this->assertEquals('Published Article', $field->resolveLabel('published'));
        $this->assertEquals('Archived Article', $field->resolveLabel('archived'));
    }

    /** @test */
    public function it_resolves_badge_field_value_with_callback(): void
    {
        $user = User::find(1);
        $field = Badge::make('User Status', 'name', function ($resource, $attribute) {
            return strtolower($resource->{$attribute}) === 'john doe' ? 'active' : 'inactive';
        });

        $field->resolve($user);

        $this->assertEquals('active', $field->value);
    }

    /** @test */
    public function it_handles_badge_type_resolution(): void
    {
        $field = Badge::make('Status')->map([
            'draft' => 'danger',
            'published' => 'success',
            'archived' => 'warning',
        ]);

        // Test mapped values
        $this->assertEquals('danger', $field->resolveBadgeType('draft'));
        $this->assertEquals('success', $field->resolveBadgeType('published'));
        $this->assertEquals('warning', $field->resolveBadgeType('archived'));

        // Test unmapped value (should default to 'info')
        $this->assertEquals('info', $field->resolveBadgeType('unknown'));
    }

    /** @test */
    public function it_handles_badge_classes_resolution(): void
    {
        $field = Badge::make('Status')
            ->types([
                'danger' => 'bg-red-50 text-red-700 ring-red-600/20',
                'success' => 'bg-green-50 text-green-700 ring-green-600/20',
            ]);

        // Test custom types
        $this->assertEquals('bg-red-50 text-red-700 ring-red-600/20', $field->resolveBadgeClasses('danger'));
        $this->assertEquals('bg-green-50 text-green-700 ring-green-600/20', $field->resolveBadgeClasses('success'));

        // Test fallback to built-in types
        $expectedBuiltInWarning = 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
        $this->assertEquals($expectedBuiltInWarning, $field->resolveBadgeClasses('warning'));
    }

    /** @test */
    public function it_handles_icon_resolution(): void
    {
        $field = Badge::make('Status')->icons([
            'danger' => 'exclamation-triangle',
            'success' => 'check-circle',
        ]);

        $this->assertEquals('exclamation-triangle', $field->resolveIcon('danger'));
        $this->assertEquals('check-circle', $field->resolveIcon('success'));
        $this->assertNull($field->resolveIcon('unknown'));
    }

    /** @test */
    public function it_handles_label_resolution(): void
    {
        // Test with label mapping
        $field1 = Badge::make('Status')->labels([
            'draft' => 'Draft Post',
            'published' => 'Published Post',
        ]);

        $this->assertEquals('Draft Post', $field1->resolveLabel('draft'));
        $this->assertEquals('Published Post', $field1->resolveLabel('published'));
        $this->assertEquals('unknown', $field1->resolveLabel('unknown')); // Default to value

        // Test with label callback
        $field2 = Badge::make('Status')->label(function ($value) {
            return ucfirst($value) . ' Status';
        });

        $this->assertEquals('Draft Status', $field2->resolveLabel('draft'));
        $this->assertEquals('Published Status', $field2->resolveLabel('published'));

        // Test default behavior
        $field3 = Badge::make('Status');
        $this->assertEquals('draft', $field3->resolveLabel('draft'));
    }

    /** @test */
    public function it_serializes_badge_field_for_frontend(): void
    {
        $field = Badge::make('Status')
            ->map(['draft' => 'danger', 'published' => 'success'])
            ->types(['danger' => 'custom-danger-class'])
            ->withIcons()
            ->icons(['danger' => 'exclamation-triangle'])
            ->labels(['draft' => 'Draft Post'])
            ->help('Select the post status');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Status', $serialized['name']);
        $this->assertEquals('status', $serialized['attribute']);
        $this->assertEquals('BadgeField', $serialized['component']);
        $this->assertEquals('Select the post status', $serialized['helpText']);

        // Check meta properties
        $this->assertArrayHasKey('builtInTypes', $serialized);
        $this->assertEquals(['draft' => 'danger', 'published' => 'success'], $serialized['valueMap']);
        $this->assertEquals(['danger' => 'custom-danger-class'], $serialized['customTypes']);
        $this->assertTrue($serialized['withIcons']);
        $this->assertEquals(['danger' => 'exclamation-triangle'], $serialized['iconMap']);
        $this->assertEquals(['draft' => 'Draft Post'], $serialized['labelMap']);
    }

    /** @test */
    public function it_inherits_all_field_methods(): void
    {
        $field = Badge::make('Status');

        // Test that Badge field inherits all base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'jsonSerialize'));

        // Test Nova-specific Badge methods
        $this->assertTrue(method_exists($field, 'map'));
        $this->assertTrue(method_exists($field, 'types'));
        $this->assertTrue(method_exists($field, 'addTypes'));
        $this->assertTrue(method_exists($field, 'withIcons'));
        $this->assertTrue(method_exists($field, 'icons'));
        $this->assertTrue(method_exists($field, 'label'));
        $this->assertTrue(method_exists($field, 'labels'));
    }

    /** @test */
    public function it_handles_complex_badge_field_configuration(): void
    {
        $field = Badge::make('Post Status')
            ->map([
                'draft' => 'danger',
                'review' => 'warning',
                'published' => 'success',
                'archived' => 'info',
            ])
            ->types([
                'danger' => 'bg-red-50 text-red-700 ring-red-600/20 font-medium',
                'warning' => 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 font-medium',
                'success' => 'bg-green-50 text-green-700 ring-green-600/20 font-medium',
            ])
            ->withIcons()
            ->icons([
                'danger' => 'exclamation-triangle',
                'warning' => 'exclamation-circle',
                'success' => 'check-circle',
                'info' => 'information-circle',
            ])
            ->labels([
                'draft' => 'Draft Article',
                'review' => 'Under Review',
                'published' => 'Published Article',
                'archived' => 'Archived Article',
            ])
            ->nullable()
            ->help('Select the current status of the post');

        // Test all configurations are set correctly
        $this->assertEquals('danger', $field->resolveBadgeType('draft'));
        $this->assertEquals('bg-red-50 text-red-700 ring-red-600/20 font-medium', $field->resolveBadgeClasses('danger'));
        $this->assertEquals('exclamation-triangle', $field->resolveIcon('danger'));
        $this->assertEquals('Draft Article', $field->resolveLabel('draft'));

        $this->assertTrue($field->withIcons);
        $this->assertCount(4, $field->valueMap);
        $this->assertCount(3, $field->customTypes);
        $this->assertCount(4, $field->iconMap);
        $this->assertCount(4, $field->labelMap);

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('Post Status', $serialized['name']);
        $this->assertEquals('post_status', $serialized['attribute']);
        $this->assertEquals('Select the current status of the post', $serialized['helpText']);
        $this->assertTrue($serialized['withIcons']);
        $this->assertCount(4, $serialized['valueMap']);
        $this->assertCount(3, $serialized['customTypes']);
        $this->assertCount(4, $serialized['iconMap']);
        $this->assertCount(4, $serialized['labelMap']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Badge::make('Status')
            ->map(['draft' => 'danger', 'published' => 'success'])
            ->types(['danger' => 'custom-danger-class'])
            ->addTypes(['warning' => 'custom-warning-class'])
            ->withIcons()
            ->icons(['danger' => 'exclamation-triangle'])
            ->labels(['draft' => 'Draft'])
            ->nullable()
            ->help('Status field')
            ->rules('required');

        $this->assertInstanceOf(Badge::class, $field);
        $this->assertEquals(['draft' => 'danger', 'published' => 'success'], $field->valueMap);
        $this->assertEquals(['danger' => 'custom-danger-class', 'warning' => 'custom-warning-class'], $field->customTypes);
        $this->assertTrue($field->withIcons);
        $this->assertEquals(['danger' => 'exclamation-triangle'], $field->iconMap);
        $this->assertEquals(['draft' => 'Draft'], $field->labelMap);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_badge_field(): void
    {
        // Create a completely fresh field instance to avoid test pollution
        $field = new Badge('Status');

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

        // Test default values - create a completely fresh instance to avoid test pollution
        $freshField = new Badge('Fresh Status');
        $this->assertEquals([], $freshField->valueMap);
        $this->assertEquals([], $freshField->customTypes);
        $this->assertFalse($freshField->withIcons);
        $this->assertEquals([], $freshField->iconMap);
        $this->assertNull($freshField->labelCallback);
        $this->assertEquals([], $freshField->labelMap);
    }
}
