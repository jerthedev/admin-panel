<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class FilteredResourceMenuIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing menu registrations
        AdminPanel::clearMainMenu();
    }

    public function test_complete_filtered_resource_menu_system(): void
    {
        // Register a menu with various filtered resource items
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Filtered Resources', [
                    // Single filter
                    MenuItem::filter('Active Users', 'UserResource')
                        ->applies('StatusFilter', 'active')
                        ->withIcon('users')
                        ->withBadge('Active', 'success'),

                    // Multiple filters
                    MenuItem::filter('Premium Laravel Users', 'UserResource')
                        ->applies('EmailFilter', '@laravel.com')
                        ->applies('SubscriptionFilter', 'premium')
                        ->withIcon('star')
                        ->withBadge('VIP', 'warning'),

                    // Filter with parameters
                    MenuItem::filter('High Value Orders', 'OrderResource')
                        ->applies('AmountFilter', '1000', ['operator' => '>='])
                        ->applies('StatusFilter', 'completed')
                        ->withIcon('currency-dollar')
                        ->withBadge(fn() => 'High Value', 'info'),

                    // Complex filter with multiple parameters
                    MenuItem::filter('Recent Premium Subscriptions', 'SubscriptionResource')
                        ->applies('DateFilter', 'last_30_days', ['field' => 'created_at'])
                        ->applies('TypeFilter', 'premium', ['column' => 'subscription_type'])
                        ->applies('StatusFilter', 'active')
                        ->withIcon('credit-card')
                        ->withBadge('Recent', 'primary'),
                ])->icon('filter'),
            ];
        });

        // Create request and resolve menu
        $request = Request::create('/admin/test');
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        // Verify the complete structure
        $this->assertCount(1, $serializedMenu);

        $section = $serializedMenu[0];
        $this->assertEquals('Filtered Resources', $section['name']);
        $this->assertCount(4, $section['items']);

        // Test single filter item
        $activeUsers = $section['items'][0];
        $this->assertEquals('Active Users', $activeUsers['label']);
        $this->assertEquals('filter', $activeUsers['meta']['type']);
        $this->assertEquals('UserResource', $activeUsers['meta']['resource']);
        $this->assertCount(1, $activeUsers['meta']['filters']);

        // Verify URL contains filter parameters
        $decodedUrl = urldecode($activeUsers['url']);
        $this->assertStringContains('filters[status]=active', $decodedUrl);
        $this->assertStringStartsWith('/admin/resources/users?', $activeUsers['url']);

        // Test multiple filters item
        $premiumUsers = $section['items'][1];
        $this->assertEquals('Premium Laravel Users', $premiumUsers['label']);
        $this->assertCount(2, $premiumUsers['meta']['filters']);

        $decodedUrl = urldecode($premiumUsers['url']);
        $this->assertStringContains('filters[email]=@laravel.com', $decodedUrl);
        $this->assertStringContains('filters[subscription]=premium', $decodedUrl);

        // Test filter with parameters
        $highValueOrders = $section['items'][2];
        $this->assertEquals('High Value Orders', $highValueOrders['label']);
        $this->assertCount(2, $highValueOrders['meta']['filters']);

        $decodedUrl = urldecode($highValueOrders['url']);
        $this->assertStringContains('filters[amount_>=]=1000', $decodedUrl);
        $this->assertStringContains('filters[status]=completed', $decodedUrl);

        // Test complex filter with multiple parameters
        $recentSubscriptions = $section['items'][3];
        $this->assertEquals('Recent Premium Subscriptions', $recentSubscriptions['label']);
        $this->assertCount(3, $recentSubscriptions['meta']['filters']);

        $decodedUrl = urldecode($recentSubscriptions['url']);
        $this->assertStringContains('filters[date_created_at]=last_30_days', $decodedUrl);
        $this->assertStringContains('filters[type_subscription_type]=premium', $decodedUrl);
        $this->assertStringContains('filters[status]=active', $decodedUrl);
    }

    public function test_filtered_resource_with_authorization(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Admin Filters', [
                    MenuItem::filter('Admin Users', 'UserResource')
                        ->applies('RoleFilter', 'admin')
                        ->canSee(function ($request) {
                            return $request->user() && $request->user()->is_admin;
                        })
                        ->withBadge('Admin Only', 'danger'),
                ]),
            ];
        });

        // Test without admin user
        $request1 = Request::create('/admin/test');
        $menu1 = AdminPanel::resolveMainMenu($request1);
        $serialized1 = AdminPanel::serializeMainMenu($menu1, $request1);

        $adminUsers1 = $serialized1[0]['items'][0];
        $this->assertFalse($adminUsers1['visible']);

        // Test with admin user
        $request2 = Request::create('/admin/test');
        $adminUser = new \stdClass();
        $adminUser->is_admin = true;
        $request2->setUserResolver(fn() => $adminUser);

        $menu2 = AdminPanel::resolveMainMenu($request2);
        $serialized2 = AdminPanel::serializeMainMenu($menu2, $request2);

        $adminUsers2 = $serialized2[0]['items'][0];
        $this->assertTrue($adminUsers2['visible']);

        $decodedUrl = urldecode($adminUsers2['url']);
        $this->assertStringContains('filters[role]=admin', $decodedUrl);
    }

    public function test_filtered_resource_url_generation_consistency(): void
    {
        // Test that URLs are generated consistently
        $filterItem1 = MenuItem::filter('Test Filter', 'UserResource')
            ->applies('StatusFilter', 'active')
            ->applies('EmailFilter', '@test.com');

        $filterItem2 = MenuItem::filter('Same Filter', 'UserResource')
            ->applies('StatusFilter', 'active')
            ->applies('EmailFilter', '@test.com');

        // URLs should be identical for same filters
        $this->assertEquals($filterItem1->url, $filterItem2->url);

        // Different order should produce different URLs
        $filterItem3 = MenuItem::filter('Different Order', 'UserResource')
            ->applies('EmailFilter', '@test.com')
            ->applies('StatusFilter', 'active');

        $this->assertNotEquals($filterItem1->url, $filterItem3->url);
    }

    public function test_filtered_resource_with_dynamic_badges(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Dynamic Filters', [
                    MenuItem::filter('Active Users', 'UserResource')
                        ->applies('StatusFilter', 'active')
                        ->withBadge(function () {
                            // Simulate database query
                            return rand(10, 100);
                        }, 'info'),

                    MenuItem::filter('Recent Orders', 'OrderResource')
                        ->applies('DateFilter', 'today')
                        ->withBadge(function () {
                            return 'Today: ' . rand(5, 50);
                        }, 'success'),
                ]),
            ];
        });

        $request = Request::create('/admin/test');
        $menu = AdminPanel::resolveMainMenu($request);
        $serialized = AdminPanel::serializeMainMenu($menu, $request);

        $activeUsers = $serialized[0]['items'][0];
        $this->assertIsNumeric($activeUsers['badge']);
        $this->assertEquals('info', $activeUsers['badgeType']);

        $recentOrders = $serialized[0]['items'][1];
        $this->assertStringStartsWith('Today: ', $recentOrders['badge']);
        $this->assertEquals('success', $recentOrders['badgeType']);
    }

    public function test_filtered_resource_serialization_completeness(): void
    {
        $filterItem = MenuItem::filter('Complete Filter', 'UserResource')
            ->applies('StatusFilter', 'active', ['column' => 'user_status'])
            ->applies('EmailFilter', '@company.com')
            ->withIcon('users')
            ->withBadge('Filtered', 'warning')
            ->withMeta(['custom' => 'data']);

        $array = $filterItem->toArray();

        // Verify all expected properties are present
        $this->assertEquals('Complete Filter', $array['label']);
        $this->assertEquals('users', $array['icon']);
        $this->assertEquals('Filtered', $array['badge']);
        $this->assertEquals('warning', $array['badgeType']);
        $this->assertTrue($array['visible']);

        // Verify filter metadata
        $this->assertEquals('filter', $array['meta']['type']);
        $this->assertEquals('UserResource', $array['meta']['resource']);
        $this->assertCount(2, $array['meta']['filters']);
        $this->assertEquals('data', $array['meta']['custom']);

        // Verify filter details
        $statusFilter = $array['meta']['filters'][0];
        $this->assertEquals('StatusFilter', $statusFilter['filter']);
        $this->assertEquals('active', $statusFilter['value']);
        $this->assertEquals(['column' => 'user_status'], $statusFilter['parameters']);

        $emailFilter = $array['meta']['filters'][1];
        $this->assertEquals('EmailFilter', $emailFilter['filter']);
        $this->assertEquals('@company.com', $emailFilter['value']);

        // Verify URL contains all filters
        $decodedUrl = urldecode($array['url']);
        $this->assertStringContains('filters[status_user_status]=active', $decodedUrl);
        $this->assertStringContains('filters[email]=@company.com', $decodedUrl);
    }

    public function test_non_filter_items_unaffected_by_filter_methods(): void
    {
        // Regular menu items should not be affected by applies() calls
        $regularItem = MenuItem::make('Regular Item', '/regular')
            ->applies('SomeFilter', 'value'); // This should be ignored

        $resourceItem = MenuItem::resource('UserResource')
            ->applies('AnotherFilter', 'value'); // This should also be ignored

        $this->assertEquals('/regular', $regularItem->url);
        $this->assertEquals('/admin/resources/users', $resourceItem->url);

        // Meta should still contain filter data but URL shouldn't change
        $this->assertArrayHasKey('filters', $regularItem->meta);
        $this->assertArrayHasKey('filters', $resourceItem->meta);
    }
}
