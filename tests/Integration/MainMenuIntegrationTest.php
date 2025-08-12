<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use JTD\AdminPanel\Http\Middleware\HandleAdminInertiaRequests;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class MainMenuIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing menu registrations
        AdminPanel::clearMainMenu();
    }

    public function test_custom_main_menu_is_shared_via_inertia_middleware(): void
    {
        // Register a custom main menu
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Dashboard')->icon('chart-bar'),
                MenuSection::make('Users', [
                    MenuItem::make('All Users', '/users')->withBadge(5, 'info'),
                    MenuItem::make('Admins', '/users/admins'),
                ])->icon('users')->collapsible(),
            ];
        });

        // Create a request
        $request = Request::create('/admin/test');
        
        // Create middleware instance
        $middleware = new HandleAdminInertiaRequests();
        
        // Get shared data
        $sharedData = $middleware->share($request);

        // Verify custom menu is included
        $this->assertArrayHasKey('customMainMenu', $sharedData);
        $this->assertNotNull($sharedData['customMainMenu']);
        
        $customMenu = $sharedData['customMainMenu'];
        $this->assertIsArray($customMenu);
        $this->assertCount(2, $customMenu);

        // Verify Dashboard section
        $dashboard = $customMenu[0];
        $this->assertEquals('Dashboard', $dashboard['name']);
        $this->assertEquals('chart-bar', $dashboard['icon']);
        $this->assertEmpty($dashboard['items']);

        // Verify Users section
        $users = $customMenu[1];
        $this->assertEquals('Users', $users['name']);
        $this->assertEquals('users', $users['icon']);
        $this->assertTrue($users['collapsible']);
        $this->assertCount(2, $users['items']);

        // Verify user items
        $allUsers = $users['items'][0];
        $this->assertEquals('All Users', $allUsers['label']);
        $this->assertEquals('/users', $allUsers['url']);
        $this->assertEquals(5, $allUsers['badge']);
        $this->assertEquals('info', $allUsers['badgeType']);

        $admins = $users['items'][1];
        $this->assertEquals('Admins', $admins['label']);
        $this->assertEquals('/users/admins', $admins['url']);
    }

    public function test_custom_main_menu_is_null_when_no_menu_registered(): void
    {
        // Ensure no custom menu is registered
        $this->assertFalse(AdminPanel::hasCustomMainMenu());

        // Create a request
        $request = Request::create('/admin/test');
        
        // Create middleware instance
        $middleware = new HandleAdminInertiaRequests();
        
        // Get shared data
        $sharedData = $middleware->share($request);

        // Verify custom menu is null
        $this->assertArrayHasKey('customMainMenu', $sharedData);
        $this->assertNull($sharedData['customMainMenu']);
    }

    public function test_custom_main_menu_respects_authorization(): void
    {
        // Register a custom main menu with authorization
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Public')->icon('globe'),
                MenuSection::make('Admin')
                    ->icon('shield')
                    ->canSee(fn() => $request->user() && $request->user()->is_admin),
            ];
        });

        // Test without admin user
        $request = Request::create('/admin/test');
        $middleware = new HandleAdminInertiaRequests();
        $sharedData = $middleware->share($request);

        $customMenu = $sharedData['customMainMenu'];
        $this->assertCount(2, $customMenu);
        $this->assertEquals('Public', $customMenu[0]['name']);
        $this->assertEquals('Admin', $customMenu[1]['name']);

        // Test with admin user
        $adminUser = new \stdClass();
        $adminUser->is_admin = true;
        $request->setUserResolver(fn() => $adminUser);

        $sharedData = $middleware->share($request);
        $customMenu = $sharedData['customMainMenu'];
        $this->assertCount(2, $customMenu);
    }

    public function test_custom_main_menu_supports_conditional_sections(): void
    {
        // Register a custom main menu with conditional logic
        AdminPanel::mainMenu(function (Request $request) {
            $sections = [
                MenuSection::make('Dashboard')->icon('chart-bar'),
            ];

            if ($request->get('show_admin')) {
                $sections[] = MenuSection::make('Admin Panel')->icon('cog');
            }

            return $sections;
        });

        // Test without condition
        $request = Request::create('/admin/test');
        $middleware = new HandleAdminInertiaRequests();
        $sharedData = $middleware->share($request);

        $customMenu = $sharedData['customMainMenu'];
        $this->assertCount(1, $customMenu);
        $this->assertEquals('Dashboard', $customMenu[0]['name']);

        // Test with condition
        $request = Request::create('/admin/test?show_admin=1');
        $sharedData = $middleware->share($request);

        $customMenu = $sharedData['customMainMenu'];
        $this->assertCount(2, $customMenu);
        $this->assertEquals('Dashboard', $customMenu[0]['name']);
        $this->assertEquals('Admin Panel', $customMenu[1]['name']);
    }

    public function test_custom_main_menu_overrides_default_navigation(): void
    {
        // Register a custom main menu
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Custom Dashboard')->icon('chart-bar'),
            ];
        });

        $request = Request::create('/admin/test');
        $middleware = new HandleAdminInertiaRequests();
        $sharedData = $middleware->share($request);

        // Verify custom menu exists
        $this->assertNotNull($sharedData['customMainMenu']);
        $this->assertCount(1, $sharedData['customMainMenu']);
        $this->assertEquals('Custom Dashboard', $sharedData['customMainMenu'][0]['name']);

        // Verify default resources and pages are still available (for fallback)
        $this->assertArrayHasKey('resources', $sharedData);
        $this->assertArrayHasKey('pages', $sharedData);
    }

    public function test_custom_main_menu_with_mixed_item_types(): void
    {
        // Register a custom main menu with mixed item types
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Section 1')->icon('folder'),
                MenuItem::make('Direct Link', '/direct')->withIcon('link'),
            ];
        });

        $request = Request::create('/admin/test');
        $middleware = new HandleAdminInertiaRequests();
        $sharedData = $middleware->share($request);

        $customMenu = $sharedData['customMainMenu'];
        $this->assertCount(2, $customMenu);

        // First item should be a section
        $section = $customMenu[0];
        $this->assertEquals('Section 1', $section['name']);
        $this->assertEquals('folder', $section['icon']);
        $this->assertArrayHasKey('items', $section);

        // Second item should be a direct menu item
        $directItem = $customMenu[1];
        $this->assertEquals('Direct Link', $directItem['label']);
        $this->assertEquals('/direct', $directItem['url']);
        $this->assertEquals('link', $directItem['icon']);
    }
}
