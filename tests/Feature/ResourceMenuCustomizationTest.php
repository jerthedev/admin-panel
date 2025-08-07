<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Resource Menu Customization Feature Tests
 *
 * Tests for resource menu customization including badge display,
 * icon customization, and conditional visibility in navigation.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ResourceMenuCustomizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Register test resources with different menu customizations
        app(AdminPanel::class)->register([
            TestBasicMenuResource::class,
            TestBadgeMenuResource::class,
            TestConditionalMenuResource::class,
            TestHiddenMenuResource::class,
        ]);
    }

    public function test_basic_resource_menu_customization(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        // Test that basic menu customization works
        $adminPanel = app(AdminPanel::class);
        $resources = $adminPanel->getNavigationResources();
        
        $basicResource = $resources->first(function ($resource) {
            return $resource instanceof TestBasicMenuResource;
        });
        
        $this->assertNotNull($basicResource);
        
        $menuItem = $basicResource->menu(request());
        $this->assertInstanceOf(MenuItem::class, $menuItem);
        $this->assertEquals('Basic Menu Resources', $menuItem->label);
        $this->assertEquals('custom-icon', $menuItem->icon);
    }

    public function test_resource_with_badge_menu_customization(): void
    {
        // Create some test users for badge count
        User::factory()->count(5)->create();
        
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        // Test badge functionality
        $adminPanel = app(AdminPanel::class);
        $resources = $adminPanel->getNavigationResources();
        
        $badgeResource = $resources->first(function ($resource) {
            return $resource instanceof TestBadgeMenuResource;
        });
        
        $menuItem = $badgeResource->menu(request());
        $badge = $menuItem->resolveBadge(request());
        
        // Should show count of users (5 created + 1 admin = 6)
        $this->assertEquals(6, $badge);
        $this->assertEquals('success', $menuItem->badgeType);
    }

    public function test_conditional_menu_customization(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        // Test conditional menu customization
        $adminPanel = app(AdminPanel::class);
        $resources = $adminPanel->getNavigationResources();
        
        $conditionalResource = $resources->first(function ($resource) {
            return $resource instanceof TestConditionalMenuResource;
        });
        
        $menuItem = $conditionalResource->menu(request());
        
        // Should have admin badge since user is admin
        $this->assertEquals('Admin User', $menuItem->resolveBadge(request()));
        $this->assertEquals('warning', $menuItem->badgeType);
    }

    public function test_hidden_menu_resource_not_in_navigation(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        // Test that hidden resources are filtered out
        $adminPanel = app(AdminPanel::class);
        $allResources = $adminPanel->getResources();
        $navigationResources = $adminPanel->getNavigationResources();
        
        // Hidden resource should exist in all resources
        $hiddenResource = $allResources->first(function ($resource) {
            return $resource instanceof TestHiddenMenuResource;
        });
        $this->assertNotNull($hiddenResource);
        
        // But should not be in navigation resources
        $hiddenInNavigation = $navigationResources->first(function ($resource) {
            return $resource instanceof TestHiddenMenuResource;
        });
        $this->assertNull($hiddenInNavigation);
    }

    public function test_navigation_includes_menu_customization_data(): void
    {
        User::factory()->count(3)->create();
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin');

        // Test that navigation data includes menu customization
        $adminPanel = app(AdminPanel::class);
        $navigationResources = $adminPanel->getNavigationResources();
        
        $badgeResource = $navigationResources->first(function ($resource) {
            return $resource instanceof TestBadgeMenuResource;
        });
        
        $request = Request::create('/admin');
        $menuItem = $badgeResource->menu($request);
        
        $navigationData = [
            'uriKey' => $badgeResource::uriKey(),
            'label' => $badgeResource::label(),
            'singularLabel' => $badgeResource::singularLabel(),
            'icon' => $menuItem->icon ?? $badgeResource::$icon ?? 'DocumentTextIcon',
            'group' => $badgeResource::$group ?? 'Default',
            'badge' => $menuItem->resolveBadge($request),
            'badgeType' => $menuItem->badgeType,
            'visible' => $menuItem->isVisible($request),
            'meta' => $menuItem->meta,
        ];
        
        $this->assertEquals('badge-menu-resources', $navigationData['uriKey']);
        $this->assertEquals(4, $navigationData['badge']); // 3 + 1 admin
        $this->assertEquals('success', $navigationData['badgeType']);
        $this->assertTrue($navigationData['visible']);
    }

    public function test_menu_customization_performance(): void
    {
        // Create many users to test badge closure performance
        User::factory()->count(100)->create();
        
        $admin = $this->createAdminUser();

        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->get('/admin');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertOk();
        
        // Should complete within reasonable time (less than 1 second)
        $this->assertLessThan(1.0, $executionTime, 'Menu customization should not significantly impact performance');
    }
}

// Test Resource Classes for Menu Customization Testing

class TestBasicMenuResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Test';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Basic Menu Resources';
    }

    public static function uriKey(): string
    {
        return 'basic-menu-resources';
    }

    public function menu(Request $request): MenuItem
    {
        return parent::menu($request)
            ->withIcon('custom-icon');
    }
}

class TestBadgeMenuResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Test';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Badge Menu Resources';
    }

    public static function uriKey(): string
    {
        return 'badge-menu-resources';
    }

    public function menu(Request $request): MenuItem
    {
        return parent::menu($request)
            ->withBadge(fn() => static::$model::count(), 'success');
    }
}

class TestConditionalMenuResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Test';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Conditional Menu Resources';
    }

    public static function uriKey(): string
    {
        return 'conditional-menu-resources';
    }

    public function menu(Request $request): MenuItem
    {
        return parent::menu($request)
            ->when($request->user() && $request->user()->is_admin, function ($menu) {
                return $menu->withBadge('Admin User', 'warning');
            })
            ->unless($request->user() && $request->user()->is_admin, function ($menu) {
                return $menu->withBadge('Regular User', 'primary');
            });
    }
}

class TestHiddenMenuResource extends Resource
{
    public static string $model = User::class;
    public static ?string $group = 'Test';
    public static string $title = 'name';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function label(): string
    {
        return 'Hidden Menu Resources';
    }

    public static function uriKey(): string
    {
        return 'hidden-menu-resources';
    }

    public function menu(Request $request): MenuItem
    {
        return parent::menu($request)
            ->hide();
    }

    public static function availableForNavigation(Request $request): bool
    {
        // Override to make it hidden from navigation
        return false;
    }
}
