<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Menu\Badge;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class BadgeSystemIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear cache and menu registrations
        Cache::flush();
        AdminPanel::clearMainMenu();
    }

    public function test_complete_badge_system_integration(): void
    {
        // Register a complex menu with various badge types
        AdminPanel::mainMenu(function (Request $request) {
            return [
                // Section with static badge
                MenuSection::make('Dashboard')
                    ->withBadge('Live', 'success')
                    ->icon('chart-bar'),

                // Section with closure badge and caching
                MenuSection::make('Users', [
                    // MenuItem with Badge instance
                    MenuItem::resource('UserResource')
                        ->withBadge(Badge::make('Active', 'info')),

                    // MenuItem with closure badge and caching
                    MenuItem::link('User Reports', '/reports/users')
                        ->withBadge(function () {
                            return rand(1, 100); // Simulate dynamic count
                        }, 'warning')
                        ->cacheBadge(60),

                    // MenuItem with conditional badge
                    MenuItem::link('Admin Users', '/admin/users')
                        ->withBadgeIf('Admin', 'danger', function ($request) {
                            return $request && $request->user() && $request->user()->is_admin;
                        }),
                ])->withBadge(function () {
                    return 'Total: ' . rand(50, 200);
                }, 'primary')
                  ->cacheBadge(30),

                // Section with conditional badge
                MenuSection::make('Reports')
                    ->withBadgeIf('New!', 'info', fn() => true)
                    ->icon('document-text'),
            ];
        });

        // Create request with admin user
        $request = Request::create('/admin/test');
        $adminUser = new \stdClass();
        $adminUser->is_admin = true;
        $request->setUserResolver(fn() => $adminUser);

        // Resolve the menu
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        // Verify the complete badge system
        $this->assertCount(3, $serializedMenu);

        // Test Dashboard section (static badge)
        $dashboard = $serializedMenu[0];
        $this->assertEquals('Dashboard', $dashboard['name']);
        $this->assertEquals('Live', $dashboard['badge']);
        $this->assertEquals('success', $dashboard['badgeType']);

        // Test Users section (closure badge with caching)
        $users = $serializedMenu[1];
        $this->assertEquals('Users', $users['name']);
        $this->assertStringStartsWith('Total: ', $users['badge']);
        $this->assertEquals('primary', $users['badgeType']);

        // Test user items
        $userItems = $users['items'];
        $this->assertCount(3, $userItems);

        // Badge instance
        $userResource = $userItems[0];
        $this->assertEquals('Active', $userResource['badge']);
        $this->assertEquals('info', $userResource['badgeType']);

        // Closure badge with caching
        $userReports = $userItems[1];
        $this->assertIsNumeric($userReports['badge']);
        $this->assertEquals('warning', $userReports['badgeType']);

        // Conditional badge (should show for admin)
        $adminUsers = $userItems[2];
        $this->assertEquals('Admin', $adminUsers['badge']);
        $this->assertEquals('danger', $adminUsers['badgeType']);

        // Test Reports section (conditional badge)
        $reports = $serializedMenu[2];
        $this->assertEquals('Reports', $reports['name']);
        $this->assertEquals('New!', $reports['badge']);
        $this->assertEquals('info', $reports['badgeType']);
    }

    public function test_badge_caching_across_menu_resolution(): void
    {
        $callCount = 0;

        AdminPanel::mainMenu(function (Request $request) use (&$callCount) {
            return [
                MenuSection::make('Cached Section')
                    ->withBadge(function () use (&$callCount) {
                        $callCount++;
                        return 'Call #' . $callCount;
                    })
                    ->cacheBadge(60),
            ];
        });

        $request = Request::create('/admin/test');

        // First resolution
        $menu1 = AdminPanel::resolveMainMenu($request);
        $serialized1 = AdminPanel::serializeMainMenu($menu1, $request);

        // Second resolution (should use cache)
        $menu2 = AdminPanel::resolveMainMenu($request);
        $serialized2 = AdminPanel::serializeMainMenu($menu2, $request);

        // Badge should be the same (cached)
        $this->assertEquals($serialized1[0]['badge'], $serialized2[0]['badge']);
        $this->assertEquals('Call #1', $serialized1[0]['badge']);
        $this->assertEquals('Call #1', $serialized2[0]['badge']);
        $this->assertEquals(1, $callCount); // Called only once
    }

    public function test_badge_types_and_styling(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Badge Types', [
                    MenuItem::link('Primary', '/primary')
                        ->withBadge('Primary', 'primary'),
                    MenuItem::link('Secondary', '/secondary')
                        ->withBadge('Secondary', 'secondary'),
                    MenuItem::link('Success', '/success')
                        ->withBadge('Success', 'success'),
                    MenuItem::link('Warning', '/warning')
                        ->withBadge('Warning', 'warning'),
                    MenuItem::link('Danger', '/danger')
                        ->withBadge('Danger', 'danger'),
                    MenuItem::link('Info', '/info')
                        ->withBadge('Info', 'info'),
                ]),
            ];
        });

        $request = Request::create('/admin/test');
        $menu = AdminPanel::resolveMainMenu($request);
        $serialized = AdminPanel::serializeMainMenu($menu, $request);

        $items = $serialized[0]['items'];
        $this->assertCount(6, $items);

        $expectedTypes = ['primary', 'secondary', 'success', 'warning', 'danger', 'info'];
        foreach ($items as $index => $item) {
            $this->assertEquals($expectedTypes[$index], $item['badgeType']);
            $this->assertEquals(ucfirst($expectedTypes[$index]), $item['badge']);
        }
    }

    public function test_conditional_badges_with_different_users(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Conditional Badges', [
                    MenuItem::link('Public', '/public')
                        ->withBadge('Public', 'info'),
                    MenuItem::link('User Only', '/user')
                        ->withBadgeIf('User', 'success', fn($req) => $req->user() !== null),
                    MenuItem::link('Admin Only', '/admin')
                        ->withBadgeIf('Admin', 'danger', fn($req) => $req->user()?->is_admin),
                ]),
            ];
        });

        // Test with no user
        $request1 = Request::create('/admin/test');
        $menu1 = AdminPanel::resolveMainMenu($request1);
        $serialized1 = AdminPanel::serializeMainMenu($menu1, $request1);

        $items1 = $serialized1[0]['items'];
        $this->assertEquals('Public', $items1[0]['badge']);
        $this->assertNull($items1[1]['badge']); // No user
        $this->assertNull($items1[2]['badge']); // No admin

        // Test with regular user
        $request2 = Request::create('/admin/test');
        $user = new \stdClass();
        $user->is_admin = false;
        $request2->setUserResolver(fn() => $user);

        $menu2 = AdminPanel::resolveMainMenu($request2);
        $serialized2 = AdminPanel::serializeMainMenu($menu2, $request2);

        $items2 = $serialized2[0]['items'];
        $this->assertEquals('Public', $items2[0]['badge']);
        $this->assertEquals('User', $items2[1]['badge']); // Has user
        $this->assertNull($items2[2]['badge']); // Not admin

        // Test with admin user
        $request3 = Request::create('/admin/test');
        $admin = new \stdClass();
        $admin->is_admin = true;
        $request3->setUserResolver(fn() => $admin);

        $menu3 = AdminPanel::resolveMainMenu($request3);
        $serialized3 = AdminPanel::serializeMainMenu($menu3, $request3);

        $items3 = $serialized3[0]['items'];
        $this->assertEquals('Public', $items3[0]['badge']);
        $this->assertEquals('User', $items3[1]['badge']); // Has user
        $this->assertEquals('Admin', $items3[2]['badge']); // Is admin
    }

    public function test_badge_performance_with_caching(): void
    {
        $expensiveCallCount = 0;

        AdminPanel::mainMenu(function (Request $request) use (&$expensiveCallCount) {
            return [
                MenuSection::make('Performance Test', [
                    // Expensive operation without caching
                    MenuItem::link('No Cache', '/no-cache')
                        ->withBadge(function () use (&$expensiveCallCount) {
                            $expensiveCallCount++;
                            usleep(1000); // 1ms delay
                            return 'Expensive';
                        }),

                    // Expensive operation with caching
                    MenuItem::link('With Cache', '/with-cache')
                        ->withBadge(function () use (&$expensiveCallCount) {
                            $expensiveCallCount++;
                            usleep(1000); // 1ms delay
                            return 'Cached';
                        })
                        ->cacheBadge(60),
                ]),
            ];
        });

        $request = Request::create('/admin/test');

        // First resolution
        $start1 = microtime(true);
        $menu1 = AdminPanel::resolveMainMenu($request);
        AdminPanel::serializeMainMenu($menu1, $request);
        $time1 = microtime(true) - $start1;

        // Second resolution
        $start2 = microtime(true);
        $menu2 = AdminPanel::resolveMainMenu($request);
        AdminPanel::serializeMainMenu($menu2, $request);
        $time2 = microtime(true) - $start2;

        // Verify caching behavior
        $this->assertEquals(3, $expensiveCallCount); // 2 calls first time, 1 call second time (cached item not called)

        // Second resolution should be faster due to caching
        $this->assertLessThan($time1, $time2);
    }

    public function test_badge_system_with_complex_menu_structure(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Business Intelligence', [
                    MenuItem::dashboard('SalesDashboard')
                        ->withBadge(fn() => 'Sales: $' . number_format(rand(10000, 99999)), 'success')
                        ->cacheBadge(30),

                    MenuItem::filter('High Value Orders', 'OrderResource')
                        ->applies('AmountFilter', 1000)
                        ->withBadge(fn() => rand(5, 50), 'warning')
                        ->cacheBadge(60),

                    MenuItem::lens('UserResource', 'TopCustomers')
                        ->withBadgeIf('VIP', 'info', fn() => true),
                ])->withBadge(fn() => 'BI Dashboard', 'primary'),

                MenuSection::make('System Health')
                    ->withBadge(Badge::make('Healthy', 'success'))
                    ->withBadgeIf('Alert', 'danger', fn() => false), // Override with conditional
            ];
        });

        $request = Request::create('/admin/test');
        $menu = AdminPanel::resolveMainMenu($request);
        $serialized = AdminPanel::serializeMainMenu($menu, $request);

        $this->assertCount(2, $serialized);

        // Business Intelligence section
        $bi = $serialized[0];
        $this->assertEquals('BI Dashboard', $bi['badge']);
        $this->assertCount(3, $bi['items']);

        // Sales dashboard with Badge instance caching
        $sales = $bi['items'][0];
        $this->assertStringStartsWith('Sales: $', $sales['badge']);
        $this->assertEquals('success', $sales['badgeType']);

        // Filtered orders with caching
        $orders = $bi['items'][1];
        $this->assertIsNumeric($orders['badge']);
        $this->assertEquals('warning', $orders['badgeType']);

        // Lens with conditional badge
        $customers = $bi['items'][2];
        $this->assertEquals('VIP', $customers['badge']);
        $this->assertEquals('info', $customers['badgeType']);

        // System Health section (conditional badge should not override)
        $health = $serialized[1];
        $this->assertEquals('Healthy', $health['badge']);
        $this->assertEquals('success', $health['badgeType']);
    }
}
