<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class AdminPanelMainMenuTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing menu registrations
        AdminPanel::clearMainMenu();
    }

    public function test_main_menu_can_be_registered_with_closure(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Dashboard')->icon('chart-bar'),
                MenuSection::make('Users')->icon('users'),
            ];
        });

        $this->assertTrue(AdminPanel::hasCustomMainMenu());
    }

    public function test_main_menu_closure_receives_request_instance(): void
    {
        $receivedRequest = null;

        AdminPanel::mainMenu(function (Request $request) use (&$receivedRequest) {
            $receivedRequest = $request;
            return [];
        });

        $request = Request::create('/test');
        AdminPanel::resolveMainMenu($request);

        $this->assertInstanceOf(Request::class, $receivedRequest);
        $this->assertEquals('/test', $receivedRequest->getRequestUri());
    }

    public function test_main_menu_returns_array_of_menu_sections(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Dashboard')->icon('chart-bar'),
                MenuSection::make('Users', [
                    MenuItem::make('All Users', '/users'),
                    MenuItem::make('Admins', '/users/admins'),
                ])->icon('users'),
            ];
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveMainMenu($request);

        $this->assertIsArray($menu);
        $this->assertCount(2, $menu);
        $this->assertInstanceOf(MenuSection::class, $menu[0]);
        $this->assertInstanceOf(MenuSection::class, $menu[1]);
        $this->assertEquals('Dashboard', $menu[0]->name);
        $this->assertEquals('Users', $menu[1]->name);
    }

    public function test_main_menu_supports_conditional_logic(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            $sections = [
                MenuSection::make('Dashboard')->icon('chart-bar'),
            ];

            if ($request->user() && $request->user()->is_admin) {
                $sections[] = MenuSection::make('Admin')->icon('shield');
            }

            return $sections;
        });

        // Test without admin user
        $request = Request::create('/test');
        $menu = AdminPanel::resolveMainMenu($request);
        $this->assertCount(1, $menu);

        // Test with admin user
        $adminUser = new \stdClass();
        $adminUser->is_admin = true;
        $request->setUserResolver(fn() => $adminUser);
        
        $menu = AdminPanel::resolveMainMenu($request);
        $this->assertCount(2, $menu);
        $this->assertEquals('Admin', $menu[1]->name);
    }

    public function test_main_menu_can_return_mixed_menu_items(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Dashboard')->icon('chart-bar'),
                MenuItem::make('Quick Link', '/quick'),
            ];
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveMainMenu($request);

        $this->assertCount(2, $menu);
        $this->assertInstanceOf(MenuSection::class, $menu[0]);
        $this->assertInstanceOf(MenuItem::class, $menu[1]);
    }

    public function test_has_custom_main_menu_returns_false_when_no_menu_registered(): void
    {
        $this->assertFalse(AdminPanel::hasCustomMainMenu());
    }

    public function test_resolve_main_menu_returns_empty_array_when_no_menu_registered(): void
    {
        $request = Request::create('/test');
        $menu = AdminPanel::resolveMainMenu($request);

        $this->assertIsArray($menu);
        $this->assertEmpty($menu);
    }

    public function test_main_menu_can_be_cleared(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [MenuSection::make('Test')];
        });

        $this->assertTrue(AdminPanel::hasCustomMainMenu());

        AdminPanel::clearMainMenu();

        $this->assertFalse(AdminPanel::hasCustomMainMenu());
    }

    public function test_main_menu_closure_can_access_admin_panel_instance(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            $adminPanel = app(AdminPanel::class);
            $resources = $adminPanel->getNavigationResources();
            
            return [
                MenuSection::make('Resources', [
                    MenuItem::make('Resource Count: ' . $resources->count(), '/resources'),
                ]),
            ];
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveMainMenu($request);

        $this->assertCount(1, $menu);
        $this->assertStringContains('Resource Count:', $menu[0]->items[0]->label);
    }

    public function test_main_menu_supports_factory_methods(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::dashboard('MainDashboard'),
                MenuSection::resource('UserResource'),
                MenuSection::make('External', [
                    MenuItem::externalLink('Documentation', 'https://docs.example.com'),
                ]),
            ];
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveMainMenu($request);

        $this->assertCount(3, $menu);
        $this->assertEquals('Dashboard', $menu[0]->name);
        $this->assertEquals('Users', $menu[1]->name);
        $this->assertEquals('External', $menu[2]->name);
        $this->assertTrue($menu[2]->items[0]->meta['external']);
    }

    public function test_main_menu_serializes_to_json_correctly(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Dashboard')->icon('chart-bar'),
                MenuSection::make('Users', [
                    MenuItem::make('All Users', '/users')->withBadge(5, 'info'),
                ])->icon('users')->collapsible(),
            ];
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveMainMenu($request);
        $json = AdminPanel::serializeMainMenu($menu, $request);

        $this->assertIsArray($json);
        $this->assertCount(2, $json);
        
        $dashboard = $json[0];
        $this->assertEquals('Dashboard', $dashboard['name']);
        $this->assertEquals('chart-bar', $dashboard['icon']);
        
        $users = $json[1];
        $this->assertEquals('Users', $users['name']);
        $this->assertEquals('users', $users['icon']);
        $this->assertTrue($users['collapsible']);
        $this->assertCount(1, $users['items']);
        $this->assertEquals(5, $users['items'][0]['badge']);
    }
}
