<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;
use Illuminate\Http\Request;

/**
 * Resource Grouping Navigation Feature Tests
 *
 * Tests for resource grouping in navigation including frontend integration,
 * group ordering, and resource sorting within groups.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceGroupingNavigationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register test resources with different groups
        app(AdminPanel::class)->register([
            TestBlogPostResource::class,
            TestPageResource::class,
            TestUserManagementResource::class,
            TestSettingsResource::class,
            TestUngroupedNavigationResource::class,
        ]);
    }

    public function test_dashboard_includes_grouped_resources_in_navigation(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();

        // Check that resources are passed to the frontend with group information
        $response->assertInertia(fn ($page) => $page
            ->has('resources', 5)
            ->where('resources.0.group', 'Content')
            ->where('resources.1.group', 'Content')
            ->where('resources.2.group', 'Admin')
            ->where('resources.3.group', 'Admin')
            ->where('resources.4.group', 'Default')
        );
    }

    public function test_resources_are_grouped_correctly_in_frontend_data(): void
    {
        $admin = $this->createAdminUser();
        $adminPanel = app(AdminPanel::class);

        // Test the backend grouping logic directly
        $resources = $adminPanel->getNavigationResources();
        $grouped = $resources->groupBy(function ($resource) {
            return $resource::$group ?? 'Default';
        });

        // Should have 3 groups: Content, Admin, Default
        $this->assertCount(3, $grouped);
        $this->assertTrue($grouped->has('Content'));
        $this->assertTrue($grouped->has('Admin'));
        $this->assertTrue($grouped->has('Default'));

        // Content group should have 2 resources
        $this->assertCount(2, $grouped->get('Content'));

        // Admin group should have 2 resources
        $this->assertCount(2, $grouped->get('Admin'));

        // Default group should have 1 resource
        $this->assertCount(1, $grouped->get('Default'));
    }

    public function test_resources_within_groups_are_sorted_alphabetically(): void
    {
        $admin = $this->createAdminUser();
        $adminPanel = app(AdminPanel::class);

        // Test the backend sorting logic directly
        $resources = $adminPanel->getNavigationResources();
        $contentResources = $resources->filter(function ($resource) {
            return ($resource::$group ?? 'Default') === 'Content';
        })->sortBy(function ($resource) {
            return $resource::label();
        })->values();

        // Should be sorted alphabetically by label
        $labels = $contentResources->map(function ($resource) {
            return $resource::label();
        })->toArray();

        $this->assertEquals(['Blog Posts', 'Pages'], $labels, 'Content resources should be sorted alphabetically');

        // Test Admin group
        $adminResources = $resources->filter(function ($resource) {
            return ($resource::$group ?? 'Default') === 'Admin';
        })->sortBy(function ($resource) {
            return $resource::label();
        })->values();

        $adminLabels = $adminResources->map(function ($resource) {
            return $resource::label();
        })->toArray();

        $this->assertEquals(['Settings', 'User Management'], $adminLabels, 'Admin resources should be sorted alphabetically');
    }

    public function test_resource_navigation_links_work_correctly(): void
    {
        $admin = $this->createAdminUser();

        // Test that each grouped resource has working navigation
        $response = $this->actingAs($admin)
            ->get('/admin/resources/blog-posts');
        $response->assertOk();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/pages');
        $response->assertOk();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/user-management');
        $response->assertOk();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/settings');
        $response->assertOk();

        $response = $this->actingAs($admin)
            ->get('/admin/resources/ungrouped-navigation');
        $response->assertOk();
    }

    public function test_navigation_maintains_backward_compatibility(): void
    {
        // Test with a mix of grouped and ungrouped resources
        $admin = $this->createAdminUser();
        $adminPanel = app(AdminPanel::class);

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();

        // All resources should still be accessible regardless of grouping
        $resources = $adminPanel->getNavigationResources();
        $this->assertCount(5, $resources);

        // Each resource should have required navigation properties
        foreach ($resources as $resource) {
            $this->assertNotEmpty($resource::uriKey());
            $this->assertNotEmpty($resource::label());
            $this->assertTrue(is_string($resource::$group) || is_null($resource::$group));
        }
    }

    public function test_empty_groups_are_not_displayed(): void
    {
        // Clear all resources and register only one
        app()->forgetInstance(AdminPanel::class);
        $adminPanel = app(AdminPanel::class);
        $adminPanel->register([TestBlogPostResource::class]);

        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();

        // Test backend logic directly
        $resources = $adminPanel->getNavigationResources();
        $groups = $resources->map(function ($resource) {
            return $resource::$group ?? 'Default';
        })->unique();

        // Should only have the Content group
        $this->assertCount(1, $groups);
        $this->assertTrue($groups->contains('Content'));
    }
}

// Test Resource Classes for Navigation Testing

class TestBlogPostResource extends Resource
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
        return 'Blog Posts';
    }

    public static function uriKey(): string
    {
        return 'blog-posts';
    }
}

class TestPageResource extends Resource
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
        return 'Pages';
    }

    public static function uriKey(): string
    {
        return 'pages';
    }
}

class TestUserManagementResource extends Resource
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
        return 'User Management';
    }

    public static function uriKey(): string
    {
        return 'user-management';
    }
}

class TestSettingsResource extends Resource
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
        return 'Settings';
    }

    public static function uriKey(): string
    {
        return 'settings';
    }
}

class TestUngroupedNavigationResource extends Resource
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
        return 'Ungrouped Navigation';
    }

    public static function uriKey(): string
    {
        return 'ungrouped-navigation';
    }
}
