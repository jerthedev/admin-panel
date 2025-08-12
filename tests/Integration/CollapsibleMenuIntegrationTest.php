<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use Illuminate\Http\Request;
use JTD\AdminPanel\Http\Middleware\HandleAdminInertiaRequests;
use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class CollapsibleMenuIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any existing menu registrations
        AdminPanel::clearMainMenu();
    }

    public function test_complete_collapsible_menu_system(): void
    {
        // Register a complex menu with collapsible sections and groups
        AdminPanel::mainMenu(function (Request $request) {
            return [
                // Non-collapsible section with path
                MenuSection::make('Dashboard')
                    ->path('/admin/dashboard')
                    ->icon('chart-bar'),

                // Collapsible section with groups
                MenuSection::make('Business Management', [
                    MenuGroup::make('Licensing', [
                        MenuItem::resource('LicenseResource'),
                        MenuItem::link('License Reports', '/reports/licenses'),
                    ])->collapsible()
                      ->collapsed()
                      ->stateId('licensing_group'),

                    MenuGroup::make('Financial', [
                        MenuItem::resource('OrderResource'),
                        MenuItem::link('Revenue Reports', '/reports/revenue'),
                        MenuItem::externalLink('Stripe Dashboard', 'https://dashboard.stripe.com'),
                    ])->collapsible()
                      ->stateId('financial_group'),
                ])->collapsible()
                  ->stateId('business_section')
                  ->icon('briefcase'),

                // Another collapsible section
                MenuSection::make('User Management', [
                    MenuItem::resource('UserResource'),
                    MenuItem::resource('RoleResource'),
                    MenuItem::link('User Analytics', '/analytics/users'),
                ])->collapsible()
                  ->collapsed()
                  ->stateId('user_management_section')
                  ->icon('users'),

                // Non-collapsible section with items
                MenuSection::make('Reports', [
                    MenuItem::link('Sales Reports', '/reports/sales'),
                    MenuItem::link('User Reports', '/reports/users'),
                ])->icon('document-text'),
            ];
        });

        // Create request and resolve menu
        $request = Request::create('/admin/test');
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        // Verify the complete structure
        $this->assertCount(4, $serializedMenu);

        // Test Dashboard section (non-collapsible with path)
        $dashboard = $serializedMenu[0];
        $this->assertEquals('Dashboard', $dashboard['name']);
        $this->assertEquals('/admin/dashboard', $dashboard['path']);
        $this->assertFalse($dashboard['collapsible']);
        $this->assertFalse($dashboard['collapsed']);
        $this->assertEquals('menu_section_dashboard', $dashboard['stateId']);

        // Test Business Management section (collapsible)
        $business = $serializedMenu[1];
        $this->assertEquals('Business Management', $business['name']);
        $this->assertNull($business['path']);
        $this->assertTrue($business['collapsible']);
        $this->assertFalse($business['collapsed']); // Default expanded
        $this->assertEquals('business_section', $business['stateId']);

        // Test groups within Business Management
        $businessGroups = $business['items'];
        $this->assertCount(2, $businessGroups);

        // Licensing group (collapsed)
        $licensing = $businessGroups[0];
        $this->assertEquals('Licensing', $licensing['name']);
        $this->assertTrue($licensing['collapsible']);
        $this->assertTrue($licensing['collapsed']);
        $this->assertEquals('licensing_group', $licensing['stateId']);
        $this->assertCount(2, $licensing['items']);

        // Financial group (expanded)
        $financial = $businessGroups[1];
        $this->assertEquals('Financial', $financial['name']);
        $this->assertTrue($financial['collapsible']);
        $this->assertFalse($financial['collapsed']);
        $this->assertEquals('financial_group', $financial['stateId']);
        $this->assertCount(3, $financial['items']);

        // Test User Management section (collapsible and collapsed)
        $userMgmt = $serializedMenu[2];
        $this->assertEquals('User Management', $userMgmt['name']);
        $this->assertTrue($userMgmt['collapsible']);
        $this->assertTrue($userMgmt['collapsed']);
        $this->assertEquals('user_management_section', $userMgmt['stateId']);
        $this->assertCount(3, $userMgmt['items']);

        // Test Reports section (non-collapsible)
        $reports = $serializedMenu[3];
        $this->assertEquals('Reports', $reports['name']);
        $this->assertFalse($reports['collapsible']);
        $this->assertFalse($reports['collapsed']);
        $this->assertEquals('menu_section_reports', $reports['stateId']);
        $this->assertCount(2, $reports['items']);
    }

    public function test_collapsible_validation_in_menu_system(): void
    {
        // Test that validation works in the complete menu system
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Collapsible sections cannot have a direct path');

        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Invalid Section')
                    ->collapsible()
                    ->path('/invalid'), // This should throw an exception
            ];
        });

        $request = Request::create('/admin/test');
        AdminPanel::resolveMainMenu($request);
    }

    public function test_middleware_integration_with_collapsible_menus(): void
    {
        // Register collapsible menu
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Collapsible Section', [
                    MenuItem::link('Item 1', '/item1'),
                    MenuItem::link('Item 2', '/item2'),
                ])->collapsible()
                  ->collapsed()
                  ->stateId('test_section'),
            ];
        });

        // Test middleware integration
        $request = Request::create('/admin/test');
        $middleware = new HandleAdminInertiaRequests();
        $sharedData = $middleware->share($request);

        // Verify custom menu is included with collapsible state
        $this->assertArrayHasKey('customMainMenu', $sharedData);
        $this->assertNotNull($sharedData['customMainMenu']);

        $customMenu = $sharedData['customMainMenu'];
        $this->assertCount(1, $customMenu);

        $section = $customMenu[0];
        $this->assertEquals('Collapsible Section', $section['name']);
        $this->assertTrue($section['collapsible']);
        $this->assertTrue($section['collapsed']);
        $this->assertEquals('test_section', $section['stateId']);
        $this->assertCount(2, $section['items']);
    }

    public function test_state_id_uniqueness_across_menu(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Section 1', [
                    MenuGroup::make('Group A', [
                        MenuItem::link('Item', '/item1'),
                    ])->collapsible()->stateId('group_a'),
                    
                    MenuGroup::make('Group B', [
                        MenuItem::link('Item', '/item2'),
                    ])->collapsible()->stateId('group_b'),
                ])->collapsible()->stateId('section_1'),

                MenuSection::make('Section 2', [
                    MenuGroup::make('Group C', [
                        MenuItem::link('Item', '/item3'),
                    ])->collapsible()->stateId('group_c'),
                ])->collapsible()->stateId('section_2'),
            ];
        });

        $request = Request::create('/admin/test');
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        // Collect all state IDs
        $stateIds = [];
        
        foreach ($serializedMenu as $section) {
            $stateIds[] = $section['stateId'];
            
            if (isset($section['items'])) {
                foreach ($section['items'] as $item) {
                    if (isset($item['stateId'])) {
                        $stateIds[] = $item['stateId'];
                    }
                }
            }
        }

        // Verify all state IDs are unique
        $this->assertEquals(count($stateIds), count(array_unique($stateIds)));
        $this->assertContains('section_1', $stateIds);
        $this->assertContains('section_2', $stateIds);
        $this->assertContains('group_a', $stateIds);
        $this->assertContains('group_b', $stateIds);
        $this->assertContains('group_c', $stateIds);
    }

    public function test_auto_generated_state_ids(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                MenuSection::make('Auto Generated Section', [
                    MenuGroup::make('Auto Generated Group', [
                        MenuItem::link('Item', '/item'),
                    ])->collapsible(),
                ])->collapsible(),
            ];
        });

        $request = Request::create('/admin/test');
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        $section = $serializedMenu[0];
        $this->assertEquals('menu_section_auto_generated_section', $section['stateId']);

        $group = $section['items'][0];
        $this->assertEquals('menu_group_auto_generated_group', $group['stateId']);
    }

    public function test_mixed_collapsible_and_non_collapsible_structure(): void
    {
        AdminPanel::mainMenu(function (Request $request) {
            return [
                // Non-collapsible section with path
                MenuSection::make('Dashboard')
                    ->path('/dashboard')
                    ->icon('home'),

                // Collapsible section with mixed groups
                MenuSection::make('Mixed Section', [
                    // Non-collapsible group
                    MenuGroup::make('Static Group', [
                        MenuItem::link('Static Item', '/static'),
                    ]),

                    // Collapsible group
                    MenuGroup::make('Collapsible Group', [
                        MenuItem::link('Collapsible Item', '/collapsible'),
                    ])->collapsible()->collapsed(),
                ])->collapsible(),

                // Non-collapsible section with items
                MenuSection::make('Static Section', [
                    MenuItem::link('Static Item', '/static-item'),
                ]),
            ];
        });

        $request = Request::create('/admin/test');
        $menuItems = AdminPanel::resolveMainMenu($request);
        $serializedMenu = AdminPanel::serializeMainMenu($menuItems, $request);

        $this->assertCount(3, $serializedMenu);

        // Dashboard (non-collapsible with path)
        $dashboard = $serializedMenu[0];
        $this->assertFalse($dashboard['collapsible']);
        $this->assertEquals('/dashboard', $dashboard['path']);

        // Mixed section (collapsible)
        $mixed = $serializedMenu[1];
        $this->assertTrue($mixed['collapsible']);
        $this->assertNull($mixed['path']);

        $mixedGroups = $mixed['items'];
        $this->assertCount(2, $mixedGroups);

        // Static group (non-collapsible)
        $staticGroup = $mixedGroups[0];
        $this->assertFalse($staticGroup['collapsible']);

        // Collapsible group
        $collapsibleGroup = $mixedGroups[1];
        $this->assertTrue($collapsibleGroup['collapsible']);
        $this->assertTrue($collapsibleGroup['collapsed']);

        // Static section (non-collapsible)
        $static = $serializedMenu[2];
        $this->assertFalse($static['collapsible']);
    }
}
