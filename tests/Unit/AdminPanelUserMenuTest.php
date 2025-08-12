<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\Menu;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class AdminPanelUserMenuTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing menu registrations
        AdminPanel::clearUserMenu();
    }

    public function test_user_menu_can_be_registered_with_closure(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(MenuItem::make('Profile', '/profile'));
            return $menu;
        });

        $this->assertTrue(AdminPanel::hasCustomUserMenu());
    }

    public function test_user_menu_closure_receives_request_and_menu_instances(): void
    {
        $receivedRequest = null;
        $receivedMenu = null;

        AdminPanel::userMenu(function (Request $request, Menu $menu) use (&$receivedRequest, &$receivedMenu) {
            $receivedRequest = $request;
            $receivedMenu = $menu;
            return $menu;
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertInstanceOf(Request::class, $receivedRequest);
        $this->assertInstanceOf(Menu::class, $receivedMenu);
        $this->assertEquals('/test', $receivedRequest->getRequestUri());
    }

    public function test_user_menu_can_append_items(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(MenuItem::make('Profile', '/profile'));
            $menu->append(MenuItem::make('Settings', '/settings'));
            return $menu;
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertCount(3, $menu->getItems()); // +1 for default logout
        $this->assertEquals('Profile', $menu->getItems()[0]->label);
        $this->assertEquals('Settings', $menu->getItems()[1]->label);
        $this->assertEquals('Sign out', $menu->getItems()[2]->label); // Default logout
    }

    public function test_user_menu_can_prepend_items(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(MenuItem::make('Profile', '/profile'));
            $menu->prepend(MenuItem::make('Dashboard', '/dashboard'));
            return $menu;
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertCount(3, $menu->getItems()); // +1 for default logout
        $this->assertEquals('Dashboard', $menu->getItems()[0]->label); // Prepended
        $this->assertEquals('Profile', $menu->getItems()[1]->label);
        $this->assertEquals('Sign out', $menu->getItems()[2]->label); // Default logout
    }

    public function test_user_menu_supports_conditional_logic(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(MenuItem::make('Profile', '/profile'));

            if ($request->user() && $request->user()->is_admin) {
                $menu->append(MenuItem::make('Admin Settings', '/admin/settings'));
            }

            return $menu;
        });

        // Test without admin user
        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);
        $this->assertCount(2, $menu->getItems()); // Profile + default logout

        // Test with admin user
        $adminUser = new \stdClass();
        $adminUser->is_admin = true;
        $request->setUserResolver(fn() => $adminUser);

        $menu = AdminPanel::resolveUserMenu($request);
        $this->assertCount(3, $menu->getItems()); // Profile + Admin Settings + default logout
        $this->assertEquals('Admin Settings', $menu->getItems()[1]->label);
    }

    public function test_user_menu_can_access_user_data(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $user = $request->user();
            if ($user) {
                $menu->append(
                    MenuItem::make("Profile ({$user->name})", "/users/{$user->id}")
                );
            }
            return $menu;
        });

        $user = new \stdClass();
        $user->id = 123;
        $user->name = 'John Doe';

        $request = Request::create('/test');
        $request->setUserResolver(fn() => $user);

        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertCount(2, $menu->getItems()); // +1 for default logout
        $this->assertEquals('Profile (John Doe)', $menu->getItems()[0]->label);
        $this->assertEquals('/users/123', $menu->getItems()[0]->url);
    }

    public function test_has_custom_user_menu_returns_false_when_no_menu_registered(): void
    {
        $this->assertFalse(AdminPanel::hasCustomUserMenu());
    }

    public function test_resolve_user_menu_returns_null_when_no_menu_registered(): void
    {
        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertNull($menu);
    }

    public function test_user_menu_can_be_cleared(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            return $menu;
        });

        $this->assertTrue(AdminPanel::hasCustomUserMenu());

        AdminPanel::clearUserMenu();

        $this->assertFalse(AdminPanel::hasCustomUserMenu());
    }

    public function test_user_menu_callback_can_return_modified_menu(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(MenuItem::make('Item 1', '/item1'));
            $menu->append(MenuItem::make('Item 2', '/item2'));

            // Return the modified menu
            return $menu;
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertCount(3, $menu->getItems()); // +1 for default logout
    }

    public function test_user_menu_callback_can_modify_menu_without_explicit_return(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(MenuItem::make('Item 1', '/item1'));
            // No explicit return - should still work
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertCount(2, $menu->getItems()); // +1 for default logout
        $this->assertEquals('Item 1', $menu->getItems()[0]->label);
    }

    public function test_user_menu_supports_badges_and_icons(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(
                MenuItem::make('Messages', '/messages')
                    ->withIcon('mail')
                    ->withBadge(5, 'danger')
            );

            $menu->append(
                MenuItem::make('Notifications', '/notifications')
                    ->withIcon('bell')
                    ->withBadge(fn() => 3, 'info')
            );

            return $menu;
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertCount(3, $menu->getItems()); // +1 for default logout

        $messages = $menu->getItems()[0];
        $this->assertEquals('mail', $messages->icon);
        $this->assertEquals(5, $messages->resolveBadge());
        $this->assertEquals('danger', $messages->badgeType);

        $notifications = $menu->getItems()[1];
        $this->assertEquals('bell', $notifications->icon);
        $this->assertEquals(3, $notifications->resolveBadge());
        $this->assertEquals('info', $notifications->badgeType);
    }

    public function test_user_menu_preserves_default_logout_link(): void
    {
        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->prepend(MenuItem::make('Profile', '/profile'));
            return $menu;
        });

        $request = Request::create('/test');
        $menu = AdminPanel::resolveUserMenu($request);

        $this->assertCount(2, $menu->getItems());

        // Profile should be first (prepended)
        $profile = $menu->getItems()[0];
        $this->assertEquals('Profile', $profile->label);

        // Default logout should be last
        $logout = $menu->getItems()[1];
        $this->assertEquals('Sign out', $logout->label);
        $this->assertEquals('/logout', $logout->url);
        $this->assertTrue($logout->meta['default']);
    }

    public function test_user_menu_prevents_menu_sections(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User menu only supports MenuItem objects');

        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(\JTD\AdminPanel\Menu\MenuSection::make('Invalid Section'));
            return $menu;
        });

        $request = Request::create('/test');
        AdminPanel::resolveUserMenu($request);
    }

    public function test_user_menu_prevents_menu_groups(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User menu only supports MenuItem objects');

        AdminPanel::userMenu(function (Request $request, Menu $menu) {
            $menu->append(\JTD\AdminPanel\Menu\MenuGroup::make('Invalid Group'));
            return $menu;
        });

        $request = Request::create('/test');
        AdminPanel::resolveUserMenu($request);
    }
}
