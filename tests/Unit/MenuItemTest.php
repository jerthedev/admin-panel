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

    public function test_menu_item_link_factory(): void
    {
        $menuItem = MenuItem::link('Cashier', '/cashier');

        $this->assertEquals('Cashier', $menuItem->label);
        $this->assertEquals('/cashier', $menuItem->url);
    }

    public function test_menu_item_resource_factory(): void
    {
        $menuItem = MenuItem::resource('UserResource');

        $this->assertEquals('Users', $menuItem->label);
        $this->assertEquals('/admin/resources/users', $menuItem->url);
    }

    public function test_menu_item_resource_factory_without_suffix(): void
    {
        $menuItem = MenuItem::resource('User');

        $this->assertEquals('Users', $menuItem->label);
        $this->assertEquals('/admin/resources/users', $menuItem->url);
    }

    public function test_menu_item_dashboard_factory(): void
    {
        $menuItem = MenuItem::dashboard('MainDashboard');

        $this->assertEquals('MainDashboard', $menuItem->label);
        $this->assertEquals('/admin/dashboards/MainDashboard', $menuItem->url);
    }

    public function test_menu_item_external_link_factory(): void
    {
        $menuItem = MenuItem::externalLink('Documentation', 'https://nova.laravel.com/docs');

        $this->assertEquals('Documentation', $menuItem->label);
        $this->assertEquals('https://nova.laravel.com/docs', $menuItem->url);
        $this->assertTrue($menuItem->meta['external']);
    }

    public function test_menu_item_lens_factory(): void
    {
        $menuItem = MenuItem::lens('UserResource', 'MostValuableUsers');

        $this->assertEquals('Most Valuable Users', $menuItem->label);
        $this->assertEquals('/admin/resources/users/lens/most-valuable-users', $menuItem->url);
        $this->assertEquals('lens', $menuItem->meta['type']);
    }

    public function test_menu_item_filter_factory(): void
    {
        $menuItem = MenuItem::filter('Active Users', 'UserResource');

        $this->assertEquals('Active Users', $menuItem->label);
        $this->assertEquals('/admin/resources/users', $menuItem->url);
        $this->assertEquals('filter', $menuItem->meta['type']);
        $this->assertArrayHasKey('filters', $menuItem->meta);
        $this->assertEmpty($menuItem->meta['filters']);
    }

    public function test_menu_item_open_in_new_tab(): void
    {
        $menuItem = MenuItem::externalLink('Documentation', 'https://nova.laravel.com/docs')
            ->openInNewTab();

        $this->assertTrue($menuItem->meta['openInNewTab']);
    }

    public function test_menu_item_open_in_new_tab_false(): void
    {
        $menuItem = MenuItem::externalLink('Documentation', 'https://nova.laravel.com/docs')
            ->openInNewTab(false);

        $this->assertFalse($menuItem->meta['openInNewTab']);
    }

    public function test_menu_item_method_specification(): void
    {
        $menuItem = MenuItem::externalLink('Logout', 'https://api.example.com/logout')
            ->method('POST');

        $this->assertEquals('POST', $menuItem->meta['method']);
    }

    public function test_menu_item_method_with_data_and_headers(): void
    {
        $data = ['user' => 'john'];
        $headers = ['API_TOKEN' => 'secret'];

        $menuItem = MenuItem::externalLink('API Call', 'https://api.example.com/action')
            ->method('POST', $data, $headers);

        $this->assertEquals('POST', $menuItem->meta['method']);
        $this->assertEquals($data, $menuItem->meta['data']);
        $this->assertEquals($headers, $menuItem->meta['headers']);
    }

    public function test_menu_item_can_see_authorization(): void
    {
        $menuItem = MenuItem::make('Admin Panel', '/admin')
            ->canSee(fn() => true);

        $this->assertTrue($menuItem->isVisible());

        $menuItem2 = MenuItem::make('Admin Panel', '/admin')
            ->canSee(fn() => false);

        $this->assertFalse($menuItem2->isVisible());
    }

    public function test_menu_item_can_see_with_request(): void
    {
        $menuItem = MenuItem::make('User Profile', '/profile')
            ->canSee(function ($request) {
                return $request && $request->user() !== null;
            });

        $request = Request::create('/test');
        $this->assertFalse($menuItem->isVisible($request));

        $user = new \stdClass();
        $request->setUserResolver(fn() => $user);
        $this->assertTrue($menuItem->isVisible($request));
    }

    public function test_menu_item_applies_filter(): void
    {
        $menuItem = MenuItem::filter('Active Users', 'UserResource')
            ->applies('StatusFilter', 'active');

        $filters = $menuItem->meta['filters'];
        $this->assertCount(1, $filters);
        $this->assertEquals('StatusFilter', $filters[0]['filter']);
        $this->assertEquals('active', $filters[0]['value']);
    }

    public function test_menu_item_applies_multiple_filters(): void
    {
        $menuItem = MenuItem::filter('Filtered Users', 'UserResource')
            ->applies('StatusFilter', 'active')
            ->applies('EmailFilter', '@laravel.com');

        $filters = $menuItem->meta['filters'];
        $this->assertCount(2, $filters);
        $this->assertEquals('StatusFilter', $filters[0]['filter']);
        $this->assertEquals('active', $filters[0]['value']);
        $this->assertEquals('EmailFilter', $filters[1]['filter']);
        $this->assertEquals('@laravel.com', $filters[1]['value']);
    }

    public function test_menu_item_filter_with_constructor_parameters(): void
    {
        $menuItem = MenuItem::filter('Column Filtered Users', 'UserResource')
            ->applies('ColumnFilter', 'active', ['column' => 'status']);

        $filters = $menuItem->meta['filters'];
        $this->assertCount(1, $filters);
        $this->assertEquals('ColumnFilter', $filters[0]['filter']);
        $this->assertEquals('active', $filters[0]['value']);
        $this->assertEquals(['column' => 'status'], $filters[0]['parameters']);
    }

    public function test_menu_item_filter_generates_url_with_query_parameters(): void
    {
        $menuItem = MenuItem::filter('Active Users', 'UserResource')
            ->applies('StatusFilter', 'active');

        $decodedUrl = urldecode($menuItem->url);
        $this->assertStringContains('filters[status]=active', $decodedUrl);
        $this->assertStringStartsWith('/admin/resources/users?', $menuItem->url);
    }

    public function test_menu_item_filter_multiple_filters_in_url(): void
    {
        $menuItem = MenuItem::filter('Filtered Users', 'UserResource')
            ->applies('StatusFilter', 'active')
            ->applies('EmailFilter', '@laravel.com');

        $decodedUrl = urldecode($menuItem->url);
        $this->assertStringContains('filters[status]=active', $decodedUrl);
        $this->assertStringContains('filters[email]=@laravel.com', $decodedUrl);
    }

    public function test_menu_item_filter_with_parameters_in_url(): void
    {
        $menuItem = MenuItem::filter('Column Filtered Users', 'UserResource')
            ->applies('ColumnFilter', 'premium', ['column' => 'subscription_type']);

        $decodedUrl = urldecode($menuItem->url);
        $this->assertStringContains('filters[column_subscription_type]=premium', $decodedUrl);
    }

    public function test_menu_item_filter_key_generation(): void
    {
        $menuItem = MenuItem::filter('Test', 'UserResource');

        // Test different filter class names
        $menuItem->applies('StatusFilter', 'active');
        $decodedUrl = urldecode($menuItem->url);
        $this->assertStringContains('filters[status]=active', $decodedUrl);

        // Reset and test with different filter
        $menuItem2 = MenuItem::filter('Test2', 'UserResource');
        $menuItem2->applies('EmailDomainFilter', '@example.com');
        $decodedUrl2 = urldecode($menuItem2->url);
        $this->assertStringContains('filters[email_domain]=@example.com', $decodedUrl2);
    }

    public function test_menu_item_filter_complex_parameters(): void
    {
        $menuItem = MenuItem::filter('Complex Filter', 'OrderResource')
            ->applies('AmountFilter', '1000', ['operator' => '>=', 'currency' => 'USD']);

        $decodedUrl = urldecode($menuItem->url);
        $this->assertStringContains('filters[amount_>=_usd]=1000', $decodedUrl);
    }

    public function test_menu_item_non_filter_items_dont_update_url(): void
    {
        $menuItem = MenuItem::make('Regular Item', '/test')
            ->applies('SomeFilter', 'value'); // This should not affect URL

        $this->assertEquals('/test', $menuItem->url);
    }

    public function test_menu_item_with_badge_if_true_condition(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withBadgeIf('New!', 'info', fn() => true);

        // Badge should be resolved when toArray is called
        $array = $menuItem->toArray();
        $this->assertEquals('New!', $array['badge']);
        $this->assertEquals('info', $array['badgeType']);
    }

    public function test_menu_item_with_badge_if_false_condition(): void
    {
        $menuItem = MenuItem::make('Users', '/admin/users')
            ->withBadgeIf('New!', 'info', fn() => false);

        // Badge should remain null when condition is false
        $array = $menuItem->toArray();
        $this->assertNull($array['badge']);
        $this->assertEquals('primary', $array['badgeType']); // Should remain default
    }

    public function test_menu_item_with_badge_if_closure_value(): void
    {
        $menuItem = MenuItem::make('Dynamic', '/dynamic')
            ->withBadgeIf(fn() => 'Dynamic Badge', 'warning', fn() => true);

        $array = $menuItem->toArray();
        $this->assertEquals('Dynamic Badge', $array['badge']);
        $this->assertEquals('warning', $array['badgeType']);
    }

    public function test_menu_item_with_badge_if_overwrites_existing_badge(): void
    {
        $menuItem = MenuItem::make('Test', '/test')
            ->withBadge('Original', 'primary')
            ->withBadgeIf('New', 'success', fn() => true);

        $array = $menuItem->toArray();
        $this->assertEquals('New', $array['badge']);
        $this->assertEquals('success', $array['badgeType']);
    }

    public function test_menu_item_with_badge_if_preserves_existing_when_false(): void
    {
        $menuItem = MenuItem::make('Test', '/test')
            ->withBadge('Original', 'primary')
            ->withBadgeIf('New', 'success', fn() => false);

        $array = $menuItem->toArray();
        $this->assertEquals('Original', $array['badge']);
        $this->assertEquals('primary', $array['badgeType']);
    }
}
