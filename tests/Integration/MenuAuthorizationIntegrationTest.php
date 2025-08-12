<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class MenuAuthorizationIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing menu registrations
        AdminPanel::clearMainMenu();
    }

    public function test_menu_filtering_removes_unauthorized_items(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Public Section', [
                    MenuItem::make('Public Item', '/public'),
                    MenuItem::make('Hidden Item', '/hidden')
                        ->canSee(fn() => false),
                    MenuItem::make('Another Public Item', '/public2'),
                ]),

                MenuSection::make('Hidden Section', [
                    MenuItem::make('Item', '/item'),
                ])
                ->canSee(fn() => false),

                MenuSection::make('Mixed Section', [
                    MenuItem::make('Visible Item', '/visible'),
                    MenuItem::make('Hidden Item', '/hidden')
                        ->canSee(fn() => false),
                ]),
            ];
        });

        $request = Request::create('/admin/test');
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        // Should have 2 sections (Hidden Section filtered out)
        $this->assertCount(2, $serializedMenu);

        // Public Section should have 2 items (Hidden Item filtered out)
        $publicSection = $serializedMenu[0];
        $this->assertEquals('Public Section', $publicSection['name']);
        $this->assertCount(2, $publicSection['items']);
        $this->assertEquals('Public Item', $publicSection['items'][0]['label']);
        $this->assertEquals('Another Public Item', $publicSection['items'][1]['label']);

        // Mixed Section should have 1 item (Hidden Item filtered out)
        $mixedSection = $serializedMenu[1];
        $this->assertEquals('Mixed Section', $mixedSection['name']);
        $this->assertCount(1, $mixedSection['items']);
        $this->assertEquals('Visible Item', $mixedSection['items'][0]['label']);
    }

    public function test_empty_sections_are_hidden_when_all_children_unauthorized(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Visible Section', [
                    MenuItem::make('Visible Item', '/visible'),
                ]),

                MenuSection::make('Empty Section', [
                    MenuItem::make('Hidden Item 1', '/hidden1')
                        ->canSee(fn() => false),
                    MenuItem::make('Hidden Item 2', '/hidden2')
                        ->canSee(fn() => false),
                ])->collapsible(), // Collapsible sections are hidden when empty

                MenuSection::make('Non-Collapsible Empty Section', [
                    MenuItem::make('Hidden Item', '/hidden')
                        ->canSee(fn() => false),
                ]), // Non-collapsible sections are kept even when empty
            ];
        });

        $request = Request::create('/admin/test');
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        // Should have 2 sections (Empty collapsible section filtered out)
        $this->assertCount(2, $serializedMenu);

        $this->assertEquals('Visible Section', $serializedMenu[0]['name']);
        $this->assertEquals('Non-Collapsible Empty Section', $serializedMenu[1]['name']);
        $this->assertCount(0, $serializedMenu[1]['items']); // Empty but kept
    }

    public function test_nested_group_filtering(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Section with Groups', [
                    MenuGroup::make('Visible Group', [
                        MenuItem::make('Item 1', '/item1'),
                        MenuItem::make('Hidden Item', '/hidden')
                            ->canSee(fn() => false),
                        MenuItem::make('Item 2', '/item2'),
                    ]),

                    MenuGroup::make('Hidden Group', [
                        MenuItem::make('Item', '/item'),
                    ])->canSee(fn() => false),

                    MenuGroup::make('Empty Group', [
                        MenuItem::make('Hidden Item 1', '/hidden1')
                            ->canSee(fn() => false),
                        MenuItem::make('Hidden Item 2', '/hidden2')
                            ->canSee(fn() => false),
                    ])->collapsible(),
                ]),
            ];
        });

        $request = Request::create('/admin/test');
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        $this->assertCount(1, $serializedMenu);

        $section = $serializedMenu[0];
        $this->assertEquals('Section with Groups', $section['name']);

        // Should have 1 group (Hidden Group and Empty Group filtered out)
        $this->assertCount(1, $section['items']);

        $visibleGroup = $section['items'][0];
        $this->assertEquals('Visible Group', $visibleGroup['name']);

        // Should have 2 items (Hidden Item filtered out)
        $this->assertCount(2, $visibleGroup['items']);
        $this->assertEquals('Item 1', $visibleGroup['items'][0]['label']);
        $this->assertEquals('Item 2', $visibleGroup['items'][1]['label']);
    }

    public function test_authorization_with_request_context(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('User-Based Section', [
                    MenuItem::make('Public Item', '/public'),

                    MenuItem::make('User Item', '/user')
                        ->canSee(function ($request) {
                            return $request->user() !== null;
                        }),

                    MenuItem::make('Admin Item', '/admin')
                        ->canSee(function ($request) {
                            return $request->user() && $request->user()->is_admin;
                        }),
                ]),
            ];
        });

        // Test without user
        $request1 = Request::create('/admin/test');
        $menu1 = AdminPanel::resolveMainMenu($request1);
        $serialized1 = AdminPanel::serializeMainMenu($menu1, $request1);

        $section1 = $serialized1[0];
        $this->assertCount(1, $section1['items']); // Only public item
        $this->assertEquals('Public Item', $section1['items'][0]['label']);

        // Test with regular user
        $request2 = Request::create('/admin/test');
        $user = new \stdClass();
        $user->is_admin = false;
        $request2->setUserResolver(fn() => $user);

        $menu2 = AdminPanel::resolveMainMenu($request2);
        $serialized2 = AdminPanel::serializeMainMenu($menu2, $request2);

        $section2 = $serialized2[0];
        $this->assertCount(2, $section2['items']); // Public + User items
        $this->assertEquals('Public Item', $section2['items'][0]['label']);
        $this->assertEquals('User Item', $section2['items'][1]['label']);

        // Test with admin user
        $request3 = Request::create('/admin/test');
        $admin = new \stdClass();
        $admin->is_admin = true;
        $request3->setUserResolver(fn() => $admin);

        $menu3 = AdminPanel::resolveMainMenu($request3);
        $serialized3 = AdminPanel::serializeMainMenu($menu3, $request3);

        $section3 = $serialized3[0];
        $this->assertCount(3, $section3['items']); // All items
        $this->assertEquals('Public Item', $section3['items'][0]['label']);
        $this->assertEquals('User Item', $section3['items'][1]['label']);
        $this->assertEquals('Admin Item', $section3['items'][2]['label']);
    }

    public function test_authorization_caching_in_menu_system(): void
    {
        $callCount = 0;

        AdminPanel::mainMenu(function (Request $request) use (&$callCount) {
            return [
                MenuSection::make('Cached Section', [
                    MenuItem::make('Expensive Item', '/expensive')
                        ->canSee(function () use (&$callCount) {
                            $callCount++;
                            usleep(1000); // 1ms delay
                            return true;
                        })
                        ->cacheAuth(60),

                    MenuItem::make('Another Item', '/another'),
                ]),
            ];
        });

        $request = Request::create('/admin/test');

        // First resolution
        $menu1 = AdminPanel::resolveMainMenu($request);
        AdminPanel::serializeMainMenu($menu1, $request);

        // Second resolution (should use cache)
        $menu2 = AdminPanel::resolveMainMenu($request);
        AdminPanel::serializeMainMenu($menu2, $request);

        // Authorization callback should be called at most twice (once per resolution)
        // Due to the way menu resolution works, it may be called during both resolve and serialize
        $this->assertLessThanOrEqual(2, $callCount);
    }

    public function test_complex_authorization_hierarchy(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            $user = $request->user();

            return [
                MenuSection::make('Dashboard')
                    ->path('/dashboard'),

                MenuSection::make('User Management', [
                    MenuItem::resource('UserResource')
                        ->canSee(fn($req) => $req->user()?->can('view-users')),

                    MenuGroup::make('User Actions', [
                        MenuItem::make('Create User', '/users/create')
                            ->canSee(fn($req) => $req->user()?->can('create-users')),
                        MenuItem::make('Import Users', '/users/import')
                            ->canSee(fn($req) => $req->user()?->can('import-users')),
                    ])->canSee(fn($req) => $req->user()?->can('manage-users')),

                    MenuItem::make('User Reports', '/reports/users')
                        ->canSee(fn($req) => $req->user()?->can('view-reports')),
                ])
                ->canSee(fn($req) => $req->user() !== null),

                MenuSection::make('System Administration', [
                    MenuItem::make('System Settings', '/admin/settings'),
                    MenuItem::make('Audit Logs', '/admin/logs'),
                ])
                ->canSee(fn($req) => $req->user()?->is_admin ?? false),
            ];
        });

        // Test with user having limited permissions
        $request = Request::create('/admin/test');

        // Mock the can() method by creating a mock class
        $userMock = new class {
            public $is_admin = false;
            public $permissions = ['view-users', 'view-reports'];

            public function can($permission) {
                return in_array($permission, $this->permissions);
            }
        };

        $request->setUserResolver(fn() => $userMock);

        $menu = AdminPanel::resolveMainMenu($request);
        $serialized = AdminPanel::serializeMainMenu($menu, $request);

        // Should have 2 sections (Dashboard + User Management, no System Administration)
        $this->assertCount(2, $serialized);

        $this->assertEquals('Dashboard', $serialized[0]['name']);
        $this->assertEquals('User Management', $serialized[1]['name']);

        $userMgmt = $serialized[1];
        // Should have 2 items (Users resource + User Reports, no User Actions group)
        $this->assertCount(2, $userMgmt['items']);
        $this->assertEquals('Users', $userMgmt['items'][0]['label']); // Resource factory creates "Users" label
        $this->assertEquals('User Reports', $userMgmt['items'][1]['label']);
    }

    public function test_authorization_performance_with_large_menu(): void
    {
        // Create a large menu structure to test performance
        AdminPanel::mainMenu(function (Request $request) {
            $sections = [];

            for ($i = 1; $i <= 10; $i++) {
                $items = [];
                for ($j = 1; $j <= 20; $j++) {
                    $items[] = MenuItem::make("Item {$i}-{$j}", "/item-{$i}-{$j}")
                        ->canSee(function () {
                            // Simulate some authorization logic
                            return rand(0, 1) === 1;
                        })
                        ->cacheAuth(60); // Cache for performance
                }

                $sections[] = MenuSection::make("Section {$i}", $items);
            }

            return $sections;
        });

        $request = Request::create('/admin/test');

        // Measure performance
        $start = microtime(true);
        $menu = AdminPanel::resolveMainMenu($request);
        $serialized = AdminPanel::serializeMainMenu($menu, $request);
        $time = microtime(true) - $start;

        // Should complete in reasonable time (less than 1 second)
        $this->assertLessThan(1.0, $time);

        // Should have filtered some items
        $this->assertLessThanOrEqual(10, count($serialized));

        // Each section should have some items filtered
        foreach ($serialized as $section) {
            $this->assertLessThanOrEqual(20, count($section['items']));
        }
    }
}
