<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Http\Controllers\PageController;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Page Route Tests
 *
 * Tests for page route generation and controller handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PageRouteTest extends TestCase
{
    public function test_page_controller_exists(): void
    {
        $this->assertTrue(class_exists(PageController::class));
    }

    public function test_page_route_name_generation(): void
    {
        $this->assertEquals('admin-panel.pages.testroute', TestRoutePage::routeName());
        $this->assertEquals('admin-panel.pages.dashboardtest', DashboardTestPage::routeName());
    }

    public function test_page_uri_path_generation(): void
    {
        $this->assertEquals('pages/testroute', TestRoutePage::uriPath());
        $this->assertEquals('pages/dashboardtest', DashboardTestPage::uriPath());
    }

    public function test_admin_panel_can_register_page_routes(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestRoutePage::class]);

        $pageRoutes = $adminPanel->getPageRoutes();

        $this->assertCount(1, $pageRoutes);
        $route = $pageRoutes->first();
        $this->assertEquals('admin-panel.pages.testroute', $route['name']);
        $this->assertEquals('pages/testroute', $route['uri']);
        $this->assertEquals(TestRoutePage::class, $route['class']);
    }

    public function test_page_controller_can_find_page_by_route_name(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestRoutePage::class]);

        $controller = new PageController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findPageByRouteName');
        $method->setAccessible(true);

        $foundPage = $method->invoke($controller, $adminPanel, 'admin-panel.pages.testroute');

        $this->assertEquals(TestRoutePage::class, $foundPage);
    }

    public function test_page_controller_returns_null_for_unknown_route(): void
    {
        $adminPanel = app(AdminPanel::class);

        $controller = new PageController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('findPageByRouteName');
        $method->setAccessible(true);

        $foundPage = $method->invoke($controller, $adminPanel, 'admin-panel.pages.nonexistent');

        $this->assertNull($foundPage);
    }
}

/**
 * Test Page for Route Testing
 */
class TestRoutePage extends Page
{
    public static ?string $component = 'TestRouteComponent';
    public static ?string $group = 'Test Group';
    public static ?string $title = 'Test Route Page';
    public static ?string $icon = 'test-icon';

    public function fields(Request $request): array
    {
        return [
            Text::make('Name'),
        ];
    }
}

/**
 * Dashboard Test Page for Route Testing
 */
class DashboardTestPage extends Page
{
    public static ?string $component = 'DashboardComponent';
    public static ?string $title = 'Dashboard';

    public function fields(Request $request): array
    {
        return [];
    }
}
