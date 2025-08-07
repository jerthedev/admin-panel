<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Support\ResourceDiscovery;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Resource Grouping Tests
 *
 * Tests for resource grouping functionality including group assignment,
 * navigation organization, and alphabetical sorting within groups.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceGroupingTest extends TestCase
{
    public function test_resource_has_group_property(): void
    {
        $resource = new TestGroupedResource();

        $this->assertEquals('Content', $resource::$group);
    }

    public function test_resource_with_null_group_defaults_to_default(): void
    {
        $resource = new TestUngroupedResource();

        $this->assertNull($resource::$group);
    }

    public function test_resource_discovery_groups_resources_correctly(): void
    {
        $discovery = new ResourceDiscovery();

        // Mock the getResourceInstances method to return our test resources
        $discovery = $this->createPartialMock(ResourceDiscovery::class, ['getResourceInstances']);
        $discovery->method('getResourceInstances')->willReturn(collect([
            new TestContentResource(),
            new TestAdminResource(),
            new TestUngroupedResource(),
            new TestAnotherContentResource(),
        ]));

        $groupedResources = $discovery->getGroupedResources();

        $this->assertCount(3, $groupedResources); // Content, Admin, Default
        $this->assertTrue($groupedResources->has('Content'));
        $this->assertTrue($groupedResources->has('Admin'));
        $this->assertTrue($groupedResources->has('Default'));

        $this->assertCount(2, $groupedResources->get('Content'));
        $this->assertCount(1, $groupedResources->get('Admin'));
        $this->assertCount(1, $groupedResources->get('Default'));
    }

    public function test_admin_panel_gets_navigation_resources_with_groups(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->register([
            TestContentResource::class,
            TestAdminResource::class,
            TestUngroupedResource::class,
        ]);

        $navigationResources = $adminPanel->getNavigationResources();

        $this->assertCount(3, $navigationResources);

        // Check that group information is preserved
        $contentResource = $navigationResources->first(function ($resource) {
            return $resource instanceof TestContentResource;
        });

        $this->assertEquals('Content', $contentResource::$group);
    }

    public function test_resources_are_sorted_alphabetically_within_groups(): void
    {
        $discovery = new ResourceDiscovery();

        // Create resources with names that would be out of order
        $discovery = $this->createPartialMock(ResourceDiscovery::class, ['getResourceInstances']);
        $discovery->method('getResourceInstances')->willReturn(collect([
            new TestZebraResource(), // Should be last in Content group
            new TestAppleResource(), // Should be first in Content group
            new TestBananaResource(), // Should be middle in Content group
        ]));

        $groupedResources = $discovery->getGroupedResources();
        $contentResources = $groupedResources->get('Content');

        // Resources should be sorted alphabetically by their label
        $labels = $contentResources->map(function ($resource) {
            return $resource::label();
        })->toArray();

        $this->assertEquals(['Apples', 'Bananas', 'Zebras'], $labels);
    }

    public function test_resource_metadata_includes_group_information(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->register([TestContentResource::class]);

        $resources = $adminPanel->getResources();
        $resource = $resources->first();

        // Test that the resource has the correct group
        $this->assertEquals('Content', $resource::$group);
    }

    public function test_list_resources_command_can_filter_by_group(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->register([
            TestContentResource::class,
            TestAdminResource::class,
            TestUngroupedResource::class,
        ]);

        $resources = $adminPanel->getResources();

        // Filter by Content group
        $contentResources = $resources->filter(function ($resource) {
            return ($resource::$group ?? 'Default') === 'Content';
        });

        $this->assertCount(1, $contentResources);
        $this->assertInstanceOf(TestContentResource::class, $contentResources->first());

        // Filter by Admin group
        $adminResources = $resources->filter(function ($resource) {
            return ($resource::$group ?? 'Default') === 'Admin';
        });

        $this->assertCount(1, $adminResources);
        $this->assertInstanceOf(TestAdminResource::class, $adminResources->first());

        // Filter by Default group (null group)
        $defaultResources = $resources->filter(function ($resource) {
            return ($resource::$group ?? 'Default') === 'Default';
        });

        $this->assertCount(1, $defaultResources);
        $this->assertInstanceOf(TestUngroupedResource::class, $defaultResources->first());
    }

    public function test_resource_stub_includes_group_property(): void
    {
        $stubPath = __DIR__ . '/../../src/Console/stubs/Resource.stub';
        $stubContent = file_get_contents($stubPath);

        $this->assertStringContains('public static ?string $group', $stubContent);
        $this->assertStringContains('Content', $stubContent);
        $this->assertStringContains('logical group associated with the resource', $stubContent);
    }

    public function test_backward_compatibility_with_existing_resources(): void
    {
        // Resources without group property should still work
        $resource = new TestUngroupedResource();

        $this->assertNull($resource::$group);
        $this->assertTrue($resource::availableForNavigation(request()));
        $this->assertEquals('Test Ungrouped Resources', $resource::label());
    }
}

// Test Resource Classes

class TestGroupedResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Content';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Test Grouped Resources';
    }
}

class TestUngroupedResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = null;
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Test Ungrouped Resources';
    }
}

class TestContentResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Content';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Test Content';
    }
}

class TestAdminResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Admin';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Test Admin';
    }
}

class TestAnotherContentResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Content';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Another Content';
    }
}

class TestAppleResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Content';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Apples';
    }
}

class TestBananaResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Content';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Bananas';
    }
}

class TestZebraResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Content';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Zebras';
    }
}
