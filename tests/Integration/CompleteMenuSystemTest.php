<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use JTD\AdminPanel\Http\Middleware\HandleAdminInertiaRequests;
use JTD\AdminPanel\Menu\Menu;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class CompleteMenuSystemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing menu registrations
        AdminPanel::clearMainMenu();
        AdminPanel::clearUserMenu();
    }

    public function test_complete_menu_system_integration(): void
    {
        // Register both main menu and user menu
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::dashboard('MainDashboard')->icon('chart-bar'),
                MenuSection::make('Business', [
                    MenuItem::resource('UserResource'),
                    MenuItem::resource('LicenseResource'),
                ])->icon('briefcase')->collapsible(),
                MenuSection::make('Content', [
                    MenuItem::resource('PostResource'),
                    MenuItem::link('Media Library', '/media'),
                ])->icon('document-text'),
            ];
        });

        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $user = $request->user();

            if ($user) {
                $menu->prepend(
                    MenuItem::make("Profile ({$user->name})", "/profile/{$user->id}")
                        ->withIcon('user')
                );
            }

            $menu->append(
                MenuItem::make('Settings', '/settings')
                    ->withIcon('cog')
            );

            if ($user && $user->is_admin) {
                $menu->append(
                    MenuItem::make('Admin Panel', '/admin/system')
                        ->withIcon('shield')
                        ->withBadge('New!', 'info')
                );
            }

            return $menu;
        });

        // Create a request with a user
        $user = new \stdClass();
        $user->id = 123;
        $user->name = 'John Doe';
        $user->is_admin = true;

        $request = Request::create('/admin/test');
        $request->setUserResolver(fn() => $user);

        // Test the middleware integration
        $middleware = new HandleAdminInertiaRequests();
        $sharedData = $middleware->share($request);

        // Verify main menu
        $this->assertArrayHasKey('customMainMenu', $sharedData);
        $this->assertNotNull($sharedData['customMainMenu']);

        $mainMenu = $sharedData['customMainMenu'];
        $this->assertCount(3, $mainMenu);

        // Verify Dashboard section
        $dashboard = $mainMenu[0];
        $this->assertEquals('Dashboard', $dashboard['name']);
        $this->assertEquals('chart-bar', $dashboard['icon']);
        $this->assertEquals('/admin/dashboards/MainDashboard', $dashboard['path']);

        // Verify Business section
        $business = $mainMenu[1];
        $this->assertEquals('Business', $business['name']);
        $this->assertEquals('briefcase', $business['icon']);
        $this->assertTrue($business['collapsible']);
        $this->assertCount(2, $business['items']);

        $userResource = $business['items'][0];
        $this->assertEquals('Users', $userResource['label']);
        $this->assertEquals('/admin/resources/users', $userResource['url']);

        $licenseResource = $business['items'][1];
        $this->assertEquals('Licenses', $licenseResource['label']);
        $this->assertEquals('/admin/resources/licenses', $licenseResource['url']);

        // Verify Content section
        $content = $mainMenu[2];
        $this->assertEquals('Content', $content['name']);
        $this->assertEquals('document-text', $content['icon']);
        $this->assertCount(2, $content['items']);

        $mediaLink = $content['items'][1];
        $this->assertEquals('Media Library', $mediaLink['label']);
        $this->assertEquals('/media', $mediaLink['url']);

        // Verify user menu
        $this->assertArrayHasKey('customUserMenu', $sharedData);
        $this->assertNotNull($sharedData['customUserMenu']);

        $userMenu = $sharedData['customUserMenu'];
        $this->assertCount(3, $userMenu);

        // Verify profile item (prepended)
        $profile = $userMenu[0];
        $this->assertEquals('Profile (John Doe)', $profile['label']);
        $this->assertEquals('/profile/123', $profile['url']);
        $this->assertEquals('user', $profile['icon']);

        // Verify settings item
        $settings = $userMenu[1];
        $this->assertEquals('Settings', $settings['label']);
        $this->assertEquals('/settings', $settings['url']);
        $this->assertEquals('cog', $settings['icon']);

        // Verify admin panel item (conditional)
        $adminPanel = $userMenu[2];
        $this->assertEquals('Admin Panel', $adminPanel['label']);
        $this->assertEquals('/admin/system', $adminPanel['url']);
        $this->assertEquals('shield', $adminPanel['icon']);
        $this->assertEquals('New!', $adminPanel['badge']);
        $this->assertEquals('info', $adminPanel['badgeType']);
    }

    public function test_menu_system_without_custom_menus(): void
    {
        // Don't register any custom menus
        $request = Request::create('/admin/test');
        $middleware = new HandleAdminInertiaRequests();
        $sharedData = $middleware->share($request);

        // Verify both custom menus are null
        $this->assertArrayHasKey('customMainMenu', $sharedData);
        $this->assertNull($sharedData['customMainMenu']);

        $this->assertArrayHasKey('customUserMenu', $sharedData);
        $this->assertNull($sharedData['customUserMenu']);

        // Verify default navigation is still available
        $this->assertArrayHasKey('resources', $sharedData);
        $this->assertArrayHasKey('pages', $sharedData);
    }

    public function test_menu_system_with_authorization_and_conditional_logic(): void
    {
        // Register menus with complex authorization logic
        AdminPanel::mainMenu(function (Request $request) {
            $sections = [
                MenuSection::make('Dashboard')->icon('chart-bar'),
            ];

            $user = $request->user();
            if ($user) {
                $sections[] = MenuSection::make('User Area', [
                    MenuItem::make('My Profile', "/users/{$user->id}"),
                ])->icon('user');

                if ($user->is_admin) {
                    $sections[] = MenuSection::make('Admin', [
                        MenuItem::make('System Settings', '/admin/system'),
                        MenuItem::make('User Management', '/admin/users'),
                    ])->icon('shield')->canSee(fn() => $user->is_admin);
                }
            }

            return $sections;
        });

        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $user = $request->user();

            $menu->append(MenuItem::make('Logout', '/logout'));

            if ($user && $user->is_admin) {
                $menu->prepend(
                    MenuItem::make('Admin Dashboard', '/admin')
                        ->withBadge('Admin', 'danger')
                );
            }

            return $menu;
        });

        // Test with regular user
        $regularUser = new \stdClass();
        $regularUser->id = 456;
        $regularUser->name = 'Regular User';
        $regularUser->is_admin = false;

        $request = Request::create('/admin/test');
        $request->setUserResolver(fn() => $regularUser);

        $middleware = new HandleAdminInertiaRequests();
        $sharedData = $middleware->share($request);

        $mainMenu = $sharedData['customMainMenu'];
        $this->assertCount(2, $mainMenu); // Dashboard + User Area only

        $userMenu = $sharedData['customUserMenu'];
        $this->assertCount(1, $userMenu); // Only logout

        // Test with admin user
        $adminUser = new \stdClass();
        $adminUser->id = 789;
        $adminUser->name = 'Admin User';
        $adminUser->is_admin = true;

        $request->setUserResolver(fn() => $adminUser);
        $sharedData = $middleware->share($request);

        $mainMenu = $sharedData['customMainMenu'];
        $this->assertCount(3, $mainMenu); // Dashboard + User Area + Admin

        $adminSection = $mainMenu[2];
        $this->assertEquals('Admin', $adminSection['name']);
        $this->assertCount(2, $adminSection['items']);

        $userMenu = $sharedData['customUserMenu'];
        $this->assertCount(2, $userMenu); // Admin Dashboard + Logout

        $adminDashboard = $userMenu[0];
        $this->assertEquals('Admin Dashboard', $adminDashboard['label']);
        $this->assertEquals('Admin', $adminDashboard['badge']);
        $this->assertEquals('danger', $adminDashboard['badgeType']);
    }

    public function test_menu_system_performance_with_complex_structures(): void
    {
        // Register a complex menu structure to test performance
        AdminPanel::mainMenu(function (Request $request) {
            $sections = [];

            // Create 10 sections with 5 items each
            for ($i = 1; $i <= 10; $i++) {
                $items = [];
                for ($j = 1; $j <= 5; $j++) {
                    $items[] = MenuItem::make("Item {$i}-{$j}", "/section{$i}/item{$j}")
                        ->withBadge(fn() => rand(1, 100), 'primary');
                }

                $sections[] = MenuSection::make("Section {$i}", $items)
                    ->icon('folder')
                    ->collapsible()
                    ->withBadge(fn() => count($items), 'info');
            }

            return $sections;
        });

        $request = Request::create('/admin/test');
        $middleware = new HandleAdminInertiaRequests();

        // Measure performance
        $startTime = microtime(true);
        $sharedData = $middleware->share($request);
        $endTime = microtime(true);

        $executionTime = $endTime - $startTime;

        // Verify the structure was created correctly
        $mainMenu = $sharedData['customMainMenu'];
        $this->assertCount(10, $mainMenu);

        foreach ($mainMenu as $index => $section) {
            $this->assertEquals("Section " . ($index + 1), $section['name']);
            $this->assertCount(5, $section['items']);
            $this->assertEquals('folder', $section['icon']);
            $this->assertTrue($section['collapsible']);
            $this->assertEquals(5, $section['badge']); // Badge shows item count
        }

        // Performance should be reasonable (less than 1 second for this test)
        $this->assertLessThan(1.0, $executionTime, 'Menu system should perform well with complex structures');
    }
}
