<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MenuItem Tests
 *
 * Tests for the MenuItem class including fluent API, badge resolution,
 * conditional visibility, and JSON serialization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MenuItemTest extends TestCase
{
    public function test_menu_item_creation(): void
    {
        $menuItem = new MenuItem('Users', '/admin/users');

        $this->assertEquals('Users', $menuItem->label);
        $this->assertEquals('/admin/users', $menuItem->url);
        $this->assertNull($menuItem->icon);
        $this->assertNull($menuItem->badge);
        $this->assertEquals('primary', $menuItem->badgeType);
        $this->assertTrue($menuItem->visible);
    }

    public function test_menu_item_make_method(): void
    {
        $menuItem = MenuItem::make('Posts', '/admin/posts');

        $this->assertEquals('Posts', $menuItem->label);
        $this->assertEquals('/admin/posts', $menuItem->url);
    }

    public function test_menu_item_with_icon(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withIcon('users');

        $this->assertEquals('users', $menuItem->icon);
    }

    public function test_menu_item_with_static_badge(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withBadge(42);

        $this->assertEquals(42, $menuItem->badge);
        $this->assertEquals('primary', $menuItem->badgeType);
    }

    public function test_menu_item_with_badge_and_type(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withBadge(10, 'warning');

        $this->assertEquals(10, $menuItem->badge);
        $this->assertEquals('warning', $menuItem->badgeType);
    }

    public function test_menu_item_with_closure_badge(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withBadge(fn() => 25);

        $this->assertIsCallable($menuItem->badge);
        $this->assertEquals(25, $menuItem->resolveBadge());
    }

    public function test_menu_item_badge_type_method(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withBadge(5)
            ->badgeType('success');

        $this->assertEquals('success', $menuItem->badgeType);
    }

    public function test_menu_item_hide_and_show(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users');

        $this->assertTrue($menuItem->visible);
        $this->assertTrue($menuItem->isVisible());

        $menuItem->hide();
        $this->assertFalse($menuItem->visible);
        $this->assertFalse($menuItem->isVisible());

        $menuItem->show();
        $this->assertTrue($menuItem->visible);
        $this->assertTrue($menuItem->isVisible());
    }

    public function test_menu_item_with_meta(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withMeta(['custom' => 'value', 'another' => 'data']);

        $this->assertEquals(['custom' => 'value', 'another' => 'data'], $menuItem->meta);
    }

    public function test_menu_item_single_meta(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->meta('key', 'value');

        $this->assertEquals(['key' => 'value'], $menuItem->meta);
    }

    public function test_menu_item_resolve_badge_with_request(): void
    {
        $request = Request::create('/admin');
        
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withBadge(function ($req) {
                return $req ? 'with-request' : 'without-request';
            });

        $this->assertEquals('with-request', $menuItem->resolveBadge($request));
        $this->assertEquals('without-request', $menuItem->resolveBadge());
    }

    public function test_menu_item_conditional_when_true(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->when(true, function ($menu) {
                return $menu->withBadge(100)->withIcon('users');
            });

        $this->assertEquals(100, $menuItem->badge);
        $this->assertEquals('users', $menuItem->icon);
    }

    public function test_menu_item_conditional_when_false(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->when(false, function ($menu) {
                return $menu->withBadge(100)->withIcon('users');
            });

        $this->assertNull($menuItem->badge);
        $this->assertNull($menuItem->icon);
    }

    public function test_menu_item_conditional_unless_true(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->unless(true, function ($menu) {
                return $menu->withBadge(100);
            });

        $this->assertNull($menuItem->badge);
    }

    public function test_menu_item_conditional_unless_false(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->unless(false, function ($menu) {
                return $menu->withBadge(100);
            });

        $this->assertEquals(100, $menuItem->badge);
    }

    public function test_menu_item_to_array(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withIcon('users')
            ->withBadge(42, 'success')
            ->withMeta(['custom' => 'value']);

        $array = $menuItem->toArray();

        $this->assertEquals([
            'label' => 'Users',
            'url' => '/admin/users',
            'icon' => 'users',
            'badge' => 42,
            'badgeType' => 'success',
            'visible' => true,
            'meta' => ['custom' => 'value'],
        ], $array);
    }

    public function test_menu_item_to_array_with_closure_badge(): void
    {
        $request = Request::create('/admin');
        
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withBadge(fn() => 25);

        $array = $menuItem->toArray($request);

        $this->assertEquals(25, $array['badge']);
    }

    public function test_menu_item_json_serialization(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withIcon('users')
            ->withBadge(10, 'warning');

        $json = $menuItem->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Users', $json['label']);
        $this->assertEquals('/admin/users', $json['url']);
        $this->assertEquals('users', $json['icon']);
        $this->assertEquals(10, $json['badge']);
        $this->assertEquals('warning', $json['badgeType']);
        $this->assertTrue($json['visible']);
    }

    public function test_menu_item_to_string(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users');

        $this->assertEquals('Users', (string) $menuItem);
    }

    public function test_menu_item_fluent_chaining(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withIcon('users')
            ->withBadge(fn() => 50, 'success')
            ->withMeta(['role' => 'admin'])
            ->meta('permission', 'manage-users')
            ->when(true, fn($menu) => $menu->badgeType('warning'));

        $this->assertEquals('users', $menuItem->icon);
        $this->assertEquals(50, $menuItem->resolveBadge());
        $this->assertEquals('warning', $menuItem->badgeType);
        $this->assertEquals([
            'role' => 'admin',
            'permission' => 'manage-users'
        ], $menuItem->meta);
    }
}
