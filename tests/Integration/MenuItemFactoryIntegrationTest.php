<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Tests\TestCase;

class MenuItemFactoryIntegrationTest extends TestCase
{
    public function test_all_factory_methods_work_together(): void
    {
        // Test all factory methods
        $linkItem = MenuItem::link('Internal Link', '/internal');
        $resourceItem = MenuItem::resource('UserResource');
        $dashboardItem = MenuItem::dashboard('MainDashboard');
        $lensItem = MenuItem::lens('UserResource', 'MostValuableUsers');
        $filterItem = MenuItem::filter('Active Users', 'UserResource');
        $externalItem = MenuItem::externalLink('Documentation', 'https://docs.example.com');

        // Verify basic properties
        $this->assertEquals('Internal Link', $linkItem->label);
        $this->assertEquals('/internal', $linkItem->url);

        $this->assertEquals('Users', $resourceItem->label);
        $this->assertEquals('/admin/resources/users', $resourceItem->url);

        $this->assertEquals('MainDashboard', $dashboardItem->label);
        $this->assertEquals('/admin/dashboards/MainDashboard', $dashboardItem->url);

        $this->assertEquals('Most Valuable Users', $lensItem->label);
        $this->assertEquals('/admin/resources/users/lens/most-valuable-users', $lensItem->url);
        $this->assertEquals('lens', $lensItem->meta['type']);

        $this->assertEquals('Active Users', $filterItem->label);
        $this->assertEquals('/admin/resources/users', $filterItem->url);
        $this->assertEquals('filter', $filterItem->meta['type']);

        $this->assertEquals('Documentation', $externalItem->label);
        $this->assertEquals('https://docs.example.com', $externalItem->url);
        $this->assertTrue($externalItem->meta['external']);
    }

    public function test_enhanced_external_link_functionality(): void
    {
        $externalItem = MenuItem::externalLink('API Endpoint', 'https://api.example.com/action')
            ->openInNewTab()
            ->method('POST', ['key' => 'value'], ['Authorization' => 'Bearer token']);

        $this->assertTrue($externalItem->meta['openInNewTab']);
        $this->assertEquals('POST', $externalItem->meta['method']);
        $this->assertEquals(['key' => 'value'], $externalItem->meta['data']);
        $this->assertEquals(['Authorization' => 'Bearer token'], $externalItem->meta['headers']);
    }

    public function test_filtered_resource_with_multiple_filters(): void
    {
        $filterItem = MenuItem::filter('Complex Filtered Users', 'UserResource')
            ->applies('StatusFilter', 'active')
            ->applies('EmailFilter', '@laravel.com')
            ->applies('ColumnFilter', 'premium', ['column' => 'subscription_type']);

        $filters = $filterItem->meta['filters'];
        $this->assertCount(3, $filters);

        $this->assertEquals('StatusFilter', $filters[0]['filter']);
        $this->assertEquals('active', $filters[0]['value']);

        $this->assertEquals('EmailFilter', $filters[1]['filter']);
        $this->assertEquals('@laravel.com', $filters[1]['value']);

        $this->assertEquals('ColumnFilter', $filters[2]['filter']);
        $this->assertEquals('premium', $filters[2]['value']);
        $this->assertEquals(['column' => 'subscription_type'], $filters[2]['parameters']);
    }

    public function test_authorization_with_different_scenarios(): void
    {
        // Always visible
        $publicItem = MenuItem::link('Public', '/public');
        $this->assertTrue($publicItem->isVisible());

        // Conditional visibility
        $conditionalItem = MenuItem::link('Conditional', '/conditional')
            ->canSee(fn() => true);
        $this->assertTrue($conditionalItem->isVisible());

        $hiddenItem = MenuItem::link('Hidden', '/hidden')
            ->canSee(fn() => false);
        $this->assertFalse($hiddenItem->isVisible());

        // Request-based authorization
        $userItem = MenuItem::link('User Area', '/user')
            ->canSee(function ($request) {
                return $request && $request->user() !== null;
            });

        $request = Request::create('/test');
        $this->assertFalse($userItem->isVisible($request));

        $user = new \stdClass();
        $request->setUserResolver(fn() => $user);
        $this->assertTrue($userItem->isVisible($request));
    }

    public function test_complex_menu_item_with_all_features(): void
    {
        $complexItem = MenuItem::filter('Premium Active Users', 'UserResource')
            ->applies('StatusFilter', 'active')
            ->applies('SubscriptionFilter', 'premium')
            ->withIcon('users')
            ->withBadge(fn() => 42, 'success')
            ->canSee(function ($request) {
                return $request && $request->user() && $request->user()->is_admin;
            })
            ->withMeta(['priority' => 'high']);

        // Test all features
        $this->assertEquals('Premium Active Users', $complexItem->label);
        $this->assertStringStartsWith('/admin/resources/users?', $complexItem->url);
        $this->assertEquals('filter', $complexItem->meta['type']);
        $this->assertEquals('users', $complexItem->icon);
        $this->assertEquals(42, $complexItem->resolveBadge());
        $this->assertEquals('success', $complexItem->badgeType);
        $this->assertEquals('high', $complexItem->meta['priority']);

        // Test filters
        $filters = $complexItem->meta['filters'];
        $this->assertCount(2, $filters);
        $this->assertEquals('StatusFilter', $filters[0]['filter']);
        $this->assertEquals('SubscriptionFilter', $filters[1]['filter']);

        // Verify URL contains filter parameters
        $decodedUrl = urldecode($complexItem->url);
        $this->assertStringContains('filters[status]=active', $decodedUrl);
        $this->assertStringContains('filters[subscription]=premium', $decodedUrl);

        // Test authorization
        $request = Request::create('/test');
        $this->assertFalse($complexItem->isVisible($request));

        $adminUser = new \stdClass();
        $adminUser->is_admin = true;
        $request->setUserResolver(fn() => $adminUser);
        $this->assertTrue($complexItem->isVisible($request));
    }

    public function test_lens_factory_with_different_naming_patterns(): void
    {
        // Test with Resource suffix
        $lens1 = MenuItem::lens('UserResource', 'MostValuableUsers');
        $this->assertEquals('Most Valuable Users', $lens1->label);
        $this->assertEquals('/admin/resources/users/lens/most-valuable-users', $lens1->url);

        // Test without Resource suffix
        $lens2 = MenuItem::lens('User', 'TopPerformers');
        $this->assertEquals('Top Performers', $lens2->label);
        $this->assertEquals('/admin/resources/users/lens/top-performers', $lens2->url);

        // Test with complex names
        $lens3 = MenuItem::lens('CustomerOrderResource', 'RecentHighValueOrders');
        $this->assertEquals('Recent High Value Orders', $lens3->label);
        $this->assertEquals('/admin/resources/customer-orders/lens/recent-high-value-orders', $lens3->url);
    }

    public function test_serialization_with_all_new_features(): void
    {
        $request = Request::create('/test');
        $user = new \stdClass();
        $user->is_admin = true;
        $request->setUserResolver(fn() => $user);

        $item = MenuItem::externalLink('Complex External', 'https://api.example.com')
            ->openInNewTab()
            ->method('POST', ['data' => 'value'])
            ->withIcon('external-link')
            ->withBadge('New!', 'info')
            ->canSee(fn($req) => $req->user()->is_admin);

        $array = $item->toArray($request);

        $this->assertEquals([
            'label' => 'Complex External',
            'url' => 'https://api.example.com',
            'icon' => 'external-link',
            'badge' => 'New!',
            'badgeType' => 'info',
            'visible' => true,
            'meta' => [
                'external' => true,
                'openInNewTab' => true,
                'method' => 'POST',
                'data' => ['data' => 'value'],
            ],
        ], $array);
    }

    public function test_filter_item_serialization(): void
    {
        $filterItem = MenuItem::filter('Filtered Users', 'UserResource')
            ->applies('StatusFilter', 'active')
            ->applies('EmailFilter', '@company.com');

        $array = $filterItem->toArray();

        $this->assertEquals('filter', $array['meta']['type']);
        $this->assertEquals('UserResource', $array['meta']['resource']);
        $this->assertCount(2, $array['meta']['filters']);
        $this->assertEquals('StatusFilter', $array['meta']['filters'][0]['filter']);
        $this->assertEquals('active', $array['meta']['filters'][0]['value']);
    }

    public function test_lens_item_serialization(): void
    {
        $lensItem = MenuItem::lens('UserResource', 'MostValuableUsers')
            ->withIcon('chart-bar')
            ->withBadge(25, 'warning');

        $array = $lensItem->toArray();

        $this->assertEquals('lens', $array['meta']['type']);
        $this->assertEquals('UserResource', $array['meta']['resource']);
        $this->assertEquals('MostValuableUsers', $array['meta']['lens']);
        $this->assertEquals('chart-bar', $array['icon']);
        $this->assertEquals(25, $array['badge']);
        $this->assertEquals('warning', $array['badgeType']);
    }

    public function test_method_chaining_with_all_new_methods(): void
    {
        $item = MenuItem::externalLink('Chained', 'https://example.com')
            ->openInNewTab()
            ->method('PUT', ['update' => true])
            ->canSee(fn() => true)
            ->withIcon('link')
            ->withBadge('Updated', 'success');

        $this->assertEquals('Chained', $item->label);
        $this->assertTrue($item->meta['openInNewTab']);
        $this->assertEquals('PUT', $item->meta['method']);
        $this->assertEquals(['update' => true], $item->meta['data']);
        $this->assertTrue($item->isVisible());
        $this->assertEquals('link', $item->icon);
        $this->assertEquals('Updated', $item->resolveBadge());
        $this->assertEquals('success', $item->badgeType);
    }
}
