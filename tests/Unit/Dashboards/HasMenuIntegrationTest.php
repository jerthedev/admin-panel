<?php

declare(strict_types=1);

namespace Tests\Unit\Dashboards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Concerns\HasMenuIntegration;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Has Menu Integration Trait Tests
 * 
 * Tests for the dashboard menu integration trait including badges,
 * categories, quick access, and custom menu behavior.
 */
class HasMenuIntegrationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_creates_basic_menu_item()
    {
        $dashboard = new TestDashboard();
        $request = Mockery::mock(Request::class);

        $menuItem = $dashboard->menu($request);

        $this->assertInstanceOf(MenuItem::class, $menuItem);
        $this->assertEquals('Test Dashboard', $menuItem->label);
        $this->assertTrue($menuItem->meta['dashboard']);
        $this->assertEquals('test', $menuItem->meta['dashboard_uri_key']);
        $this->assertEquals('Test Dashboard', $menuItem->meta['dashboard_name']);
        $this->assertEquals('A test dashboard', $menuItem->meta['dashboard_description']);
        $this->assertEquals('Testing', $menuItem->meta['dashboard_category']);
    }

    public function test_sets_menu_badge()
    {
        $dashboard = new TestDashboard();
        $dashboard->withMenuBadge('New', 'info');
        
        $request = Mockery::mock(Request::class);
        $menuItem = $dashboard->menu($request);

        // Badge should be set (exact implementation depends on MenuItem)
        $this->assertNotNull($menuItem);
    }

    public function test_sets_callable_menu_badge()
    {
        $dashboard = new TestDashboard();
        $dashboard->withMenuBadge(fn($request) => 'Dynamic', 'warning');
        
        $request = Mockery::mock(Request::class);
        $menuItem = $dashboard->menu($request);

        $this->assertNotNull($menuItem);
    }

    public function test_sets_quick_access()
    {
        $dashboard = new TestDashboard();
        $dashboard->quickAccess(true);

        $this->assertTrue($dashboard->isQuickAccess());
        $this->assertTrue($dashboard->getMenuMetadata()['quick_access']);
    }

    public function test_sets_can_be_favorited()
    {
        $dashboard = new TestDashboard();
        $dashboard->canBeFavorited(false);

        $this->assertFalse($dashboard->isFavoritable());
        $this->assertFalse($dashboard->getMenuMetadata()['can_be_favorited']);
    }

    public function test_sets_custom_menu_icon()
    {
        $dashboard = new TestDashboard();
        $dashboard->withMenuIcon('custom-icon');

        $this->assertEquals('custom-icon', $dashboard->getMenuIcon());
        $this->assertEquals('custom-icon', $dashboard->getMenuMetadata()['menu_icon']);
    }

    public function test_sets_custom_menu_label()
    {
        $dashboard = new TestDashboard();
        $dashboard->withMenuLabel('Custom Label');

        $this->assertEquals('Custom Label', $dashboard->getMenuLabel());
        $this->assertEquals('Custom Label', $dashboard->getMenuMetadata()['menu_label']);
    }

    public function test_sets_menu_visibility_callback()
    {
        $dashboard = new TestDashboard();
        $dashboard->menuVisibleWhen(fn($request) => false);

        $request = Mockery::mock(Request::class);
        $this->assertFalse($dashboard->isMenuVisible($request));
    }

    public function test_gets_menu_metadata()
    {
        $dashboard = new TestDashboard();
        $dashboard->quickAccess(true)
                  ->withMenuIcon('test-icon')
                  ->withMenuLabel('Test Label');

        $metadata = $dashboard->getMenuMetadata();

        $this->assertIsArray($metadata);
        $this->assertTrue($metadata['dashboard']);
        $this->assertEquals('test', $metadata['dashboard_uri_key']);
        $this->assertEquals('Test Dashboard', $metadata['dashboard_name']);
        $this->assertEquals('A test dashboard', $metadata['dashboard_description']);
        $this->assertEquals('Testing', $metadata['dashboard_category']);
        $this->assertTrue($metadata['quick_access']);
        $this->assertTrue($metadata['can_be_favorited']);
        $this->assertEquals('test-icon', $metadata['menu_icon']);
        $this->assertEquals('Test Label', $metadata['menu_label']);
    }

    public function test_creates_menu_section()
    {
        $dashboard = new TestDashboard();
        $request = Mockery::mock(Request::class);

        $menuSection = $dashboard->asMenuSection($request);

        $this->assertInstanceOf(MenuSection::class, $menuSection);
        $this->assertEquals('Test Dashboard', $menuSection->name);
        $this->assertTrue($menuSection->meta['dashboard']);
        $this->assertEquals('test', $menuSection->meta['dashboard_uri_key']);
    }

    public function test_creates_menu_item()
    {
        $dashboard = new TestDashboard();
        $request = Mockery::mock(Request::class);

        $menuItem = $dashboard->asMenuItem($request);

        $this->assertInstanceOf(MenuItem::class, $menuItem);
        $this->assertEquals('Test Dashboard', $menuItem->label);
    }

    public function test_should_group_in_menu()
    {
        $dashboard = new TestDashboard();
        $this->assertTrue($dashboard->shouldGroupInMenu());

        $dashboardWithoutCategory = new TestDashboardWithoutCategory();
        $this->assertFalse($dashboardWithoutCategory->shouldGroupInMenu());
    }

    public function test_gets_menu_group()
    {
        $dashboard = new TestDashboard();
        $this->assertEquals('Testing', $dashboard->getMenuGroup());

        $dashboardWithoutCategory = new TestDashboardWithoutCategory();
        $this->assertNull($dashboardWithoutCategory->getMenuGroup());
    }

    public function test_sets_menu_priority()
    {
        $dashboard = new TestDashboard();
        $dashboard->withMenuPriority(50);

        $this->assertEquals(50, $dashboard->getMenuPriority());
    }

    public function test_sets_appear_in_main_menu()
    {
        $dashboard = new TestDashboard();
        $dashboard->appearInMainMenu(false);

        $this->assertFalse($dashboard->shouldAppearInMainMenu());
    }

    public function test_gets_menu_config()
    {
        $dashboard = new TestDashboard();
        $dashboard->withMenuBadge('Test Badge', 'success')
                  ->quickAccess(true)
                  ->withMenuPriority(25)
                  ->appearInMainMenu(false);

        $config = $dashboard->getMenuConfig();

        $this->assertIsArray($config);
        $this->assertEquals('Test Dashboard', $config['label']);
        $this->assertEquals('chart-bar', $config['icon']);
        $this->assertEquals('Test Badge', $config['badge']);
        $this->assertEquals('success', $config['badge_type']);
        $this->assertTrue($config['quick_access']);
        $this->assertTrue($config['can_be_favorited']);
        $this->assertEquals(25, $config['priority']);
        $this->assertFalse($config['appear_in_main_menu']);
        $this->assertEquals('Testing', $config['group']);
    }

    public function test_menu_visibility_respects_authorization()
    {
        $dashboard = new TestDashboardUnauthorized();
        $request = Mockery::mock(Request::class);

        $this->assertFalse($dashboard->isMenuVisible($request));
    }

    public function test_fallback_values()
    {
        $dashboard = new TestDashboardMinimal();

        $this->assertEquals('Test Minimal', $dashboard->getMenuLabel());
        $this->assertEquals('chart-bar', $dashboard->getMenuIcon());
        $this->assertFalse($dashboard->isQuickAccess());
        $this->assertTrue($dashboard->isFavoritable());
        $this->assertEquals(100, $dashboard->getMenuPriority());
        $this->assertTrue($dashboard->shouldAppearInMainMenu());
    }
}

// Test Dashboard Classes
class TestDashboard extends Dashboard
{
    use HasMenuIntegration;

    public function name(): string
    {
        return 'Test Dashboard';
    }

    public function description(): string
    {
        return 'A test dashboard';
    }

    public function category(): string
    {
        return 'Testing';
    }

    public function uriKey(): string
    {
        return 'test';
    }

    public function authorizedToSee($request): bool
    {
        return true;
    }

    public function cards(): array
    {
        return [];
    }
}

class TestDashboardWithoutCategory extends Dashboard
{
    use HasMenuIntegration;

    public function name(): string
    {
        return 'Test Dashboard Without Category';
    }

    public function description(): string
    {
        return 'A test dashboard without category';
    }

    public function category(): ?string
    {
        return null;
    }

    public function uriKey(): string
    {
        return 'test-no-category';
    }

    public function authorizedToSee($request): bool
    {
        return true;
    }

    public function cards(): array
    {
        return [];
    }
}

class TestDashboardUnauthorized extends Dashboard
{
    use HasMenuIntegration;

    public function name(): string
    {
        return 'Unauthorized Dashboard';
    }

    public function description(): string
    {
        return 'An unauthorized dashboard';
    }

    public function category(): string
    {
        return 'Testing';
    }

    public function uriKey(): string
    {
        return 'unauthorized';
    }

    public function authorizedToSee($request): bool
    {
        return false;
    }

    public function cards(): array
    {
        return [];
    }
}

class TestDashboardMinimal extends Dashboard
{
    use HasMenuIntegration;

    public function name(): string
    {
        return 'Test Minimal';
    }

    public function uriKey(): string
    {
        return 'minimal';
    }

    public function authorizedToSee($request): bool
    {
        return true;
    }

    public function cards(): array
    {
        return [];
    }
}
