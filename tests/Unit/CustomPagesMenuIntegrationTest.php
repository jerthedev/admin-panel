<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Custom Pages Menu Integration Test
 *
 * Tests that custom pages are properly integrated into the navigation menu
 * system and appear grouped by their $group property.
 */
class CustomPagesMenuIntegrationTest extends TestCase
{
    public function test_pages_appear_in_navigation_data(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestMenuPage::class]);

        $navigationPages = $adminPanel->getNavigationPages();

        $this->assertCount(1, $navigationPages);
        $this->assertInstanceOf(TestMenuPage::class, $navigationPages->first());
    }

    public function test_pages_are_grouped_by_group_property(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([
            TestMenuPage::class,
            TestSystemPage::class,
            TestUserPage::class,
        ]);

        $navigationPages = $adminPanel->getNavigationPages();
        $request = Request::create('/');

        // Map pages to their menu data
        $menuData = $navigationPages->map(function ($page) use ($request) {
            $menuItem = $page->menu($request);
            
            return [
                'label' => $page::label(),
                'group' => $page::group() ?? 'Default',
                'icon' => $page::icon(),
                'visible' => $menuItem->isVisible($request),
            ];
        });

        // Check that we have pages from different groups
        $groups = $menuData->pluck('group')->unique();
        $this->assertContains('Testing', $groups);
        $this->assertContains('System', $groups);
        $this->assertContains('Users', $groups);
    }

    public function test_pages_have_correct_menu_properties(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestMenuPage::class]);

        $page = $adminPanel->getNavigationPages()->first();
        $request = Request::create('/');
        $menuItem = $page->menu($request);

        $this->assertEquals('Test Menu Page', $page::label());
        $this->assertEquals('Testing', $page::group());
        $this->assertEquals('test-tube', $page::icon());
        $this->assertTrue($menuItem->isVisible($request));
    }

    public function test_pages_with_no_group_default_to_default_group(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestNoGroupPage::class]);

        $page = $adminPanel->getNavigationPages()->first();
        
        $this->assertEquals('Default', $page::group() ?? 'Default');
    }

    public function test_pages_can_be_hidden_from_navigation(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestHiddenPage::class]);

        $navigationPages = $adminPanel->getNavigationPages();
        
        // Should be empty because the page is not available for navigation
        $this->assertCount(0, $navigationPages);
    }

    public function test_middleware_provides_pages_data(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestMenuPage::class]);

        $request = Request::create('/admin');
        $middleware = new \JTD\AdminPanel\Http\Middleware\HandleAdminInertiaRequests();

        // Get the shared data that would be passed to Inertia
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('share');
        $method->setAccessible(true);
        
        $sharedData = $method->invoke($middleware, $request);

        $this->assertArrayHasKey('pages', $sharedData);
        $this->assertCount(1, $sharedData['pages']);
        
        $pageData = $sharedData['pages'][0];
        $this->assertEquals('Test Menu Page', $pageData['label']);
        $this->assertEquals('Testing', $pageData['group']);
        $this->assertEquals('test-tube', $pageData['icon']);
        $this->assertTrue($pageData['visible']);
    }

    public function test_pages_are_filtered_by_visibility(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([
            TestMenuPage::class,
            TestHiddenPage::class,
        ]);

        $request = Request::create('/admin');
        $middleware = new \JTD\AdminPanel\Http\Middleware\HandleAdminInertiaRequests();

        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('share');
        $method->setAccessible(true);
        
        $sharedData = $method->invoke($middleware, $request);

        // Should only have the visible page
        $this->assertCount(1, $sharedData['pages']);
        $this->assertEquals('Test Menu Page', $sharedData['pages'][0]['label']);
    }
}

/**
 * Test page for menu integration testing.
 */
class TestMenuPage extends Page
{
    public static array $components = ['Pages/TestMenu'];
    public static ?string $title = 'Test Menu Page';
    public static ?string $group = 'Testing';
    public static ?string $icon = 'test-tube';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function actions(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return true;
    }
}

/**
 * Test page for system group.
 */
class TestSystemPage extends Page
{
    public static array $components = ['Pages/TestSystem'];
    public static ?string $title = 'Test System Page';
    public static ?string $group = 'System';
    public static ?string $icon = 'server';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function actions(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return true;
    }
}

/**
 * Test page for users group.
 */
class TestUserPage extends Page
{
    public static array $components = ['Pages/TestUser'];
    public static ?string $title = 'Test User Page';
    public static ?string $group = 'Users';
    public static ?string $icon = 'user';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function actions(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return true;
    }
}

/**
 * Test page with no group (should default to 'Default').
 */
class TestNoGroupPage extends Page
{
    public static array $components = ['Pages/TestNoGroup'];
    public static ?string $title = 'Test No Group Page';
    public static ?string $icon = 'document';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function actions(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return true;
    }
}

/**
 * Test page that should be hidden from navigation.
 */
class TestHiddenPage extends Page
{
    public static array $components = ['Pages/TestHidden'];
    public static ?string $title = 'Test Hidden Page';
    public static ?string $group = 'Hidden';
    public static ?string $icon = 'eye-slash';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function actions(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return false; // Hidden from navigation
    }
}
