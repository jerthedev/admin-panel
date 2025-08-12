<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration;

use JTD\AdminPanel\Menu\Badge;
use JTD\AdminPanel\Menu\Menu;
use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Tests\TestCase;

class MenuFoundationIntegrationTest extends TestCase
{
    public function test_complete_menu_structure_integration(): void
    {
        // Create a complex menu structure using all foundation classes
        $menu = new Menu([
            MenuSection::make('Dashboard')
                ->icon('chart-bar')
                ->path('/admin/dashboard'),

            MenuSection::make('Business', [
                MenuGroup::make('Licensing', [
                    MenuItem::resource('LicenseResource'),
                    MenuItem::link('Refunds', '/admin/refunds'),
                ])->collapsible(),

                MenuGroup::make('External', [
                    MenuItem::externalLink('Stripe', 'https://dashboard.stripe.com'),
                ]),
            ])->icon('briefcase')->collapsible(),

            MenuSection::make('Users', [
                MenuItem::resource('UserResource')
                    ->withBadge(fn() => 42, 'success'),
            ])->withBadge(Badge::make('New!', 'info')),
        ]);

        // Test the complete structure serializes correctly
        $json = $menu->jsonSerialize();

        $this->assertCount(3, $json);

        // Test Dashboard section
        $dashboard = $json[0];
        $this->assertEquals('Dashboard', $dashboard['name']);
        $this->assertEquals('chart-bar', $dashboard['icon']);
        $this->assertEquals('/admin/dashboard', $dashboard['path']);
        $this->assertEmpty($dashboard['items']);

        // Test Business section
        $business = $json[1];
        $this->assertEquals('Business', $business['name']);
        $this->assertEquals('briefcase', $business['icon']);
        $this->assertTrue($business['collapsible']);
        $this->assertCount(2, $business['items']);

        // Test Licensing group
        $licensing = $business['items'][0];
        $this->assertEquals('Licensing', $licensing['name']);
        $this->assertTrue($licensing['collapsible']);
        $this->assertCount(2, $licensing['items']);

        // Test License resource item
        $licenseItem = $licensing['items'][0];
        $this->assertEquals('Licenses', $licenseItem['label']);
        $this->assertEquals('/admin/resources/licenses', $licenseItem['url']);

        // Test external link
        $external = $business['items'][1]['items'][0];
        $this->assertEquals('Stripe', $external['label']);
        $this->assertEquals('https://dashboard.stripe.com', $external['url']);
        $this->assertTrue($external['meta']['external']);

        // Test Users section with badge
        $users = $json[2];
        $this->assertEquals('Users', $users['name']);
        $this->assertEquals('New!', $users['badge']);
        $this->assertEquals('info', $users['badgeType']);

        // Test user item with closure badge
        $userItem = $users['items'][0];
        $this->assertEquals('Users', $userItem['label']);
        $this->assertEquals(42, $userItem['badge']);
        $this->assertEquals('success', $userItem['badgeType']);
    }

    public function test_menu_iteration_and_counting(): void
    {
        $section1 = MenuSection::make('Section 1');
        $section2 = MenuSection::make('Section 2');
        $menu = new Menu([$section1, $section2]);

        // Test countable interface
        $this->assertEquals(2, count($menu));
        $this->assertEquals(2, $menu->count());

        // Test iterator interface
        $items = [];
        foreach ($menu as $item) {
            $items[] = $item;
        }

        $this->assertCount(2, $items);
        $this->assertSame($section1, $items[0]);
        $this->assertSame($section2, $items[1]);
    }

    public function test_menu_append_prepend_operations(): void
    {
        $menu = new Menu();
        $item1 = MenuSection::make('First');
        $item2 = MenuSection::make('Second');
        $item3 = MenuSection::make('Third');

        $menu->append($item1)
            ->append($item2)
            ->prepend($item3);

        $items = $menu->getItems();
        $this->assertCount(3, $items);
        $this->assertSame($item3, $items[0]); // Prepended
        $this->assertSame($item1, $items[1]); // First appended
        $this->assertSame($item2, $items[2]); // Second appended
    }

    public function test_badge_resolution_in_complex_structure(): void
    {
        $dynamicBadge = Badge::make(fn() => 'Dynamic Value', 'warning');
        $staticBadge = Badge::make('Static', 'info');

        $section = MenuSection::make('Test')
            ->withBadge($dynamicBadge);

        $item = MenuItem::make('Item', '/item')
            ->withBadge($staticBadge);

        $this->assertEquals('Dynamic Value', $section->resolveBadge());
        $this->assertEquals('Static', $item->resolveBadge());
    }

    public function test_authorization_integration(): void
    {
        $visibleSection = MenuSection::make('Visible')
            ->canSee(fn() => true);

        $hiddenSection = MenuSection::make('Hidden')
            ->canSee(fn() => false);

        $visibleGroup = MenuGroup::make('Visible Group')
            ->canSee(fn() => true);

        $hiddenGroup = MenuGroup::make('Hidden Group')
            ->canSee(fn() => false);

        $this->assertTrue($visibleSection->isVisible());
        $this->assertFalse($hiddenSection->isVisible());
        $this->assertTrue($visibleGroup->isVisible());
        $this->assertFalse($hiddenGroup->isVisible());
    }

    public function test_factory_methods_integration(): void
    {
        // Test all factory methods work together
        $menu = new Menu([
            MenuSection::dashboard('MainDashboard'),
            MenuSection::resource('UserResource'),
            MenuSection::make('Links', [
                MenuItem::link('Internal', '/internal'),
                MenuItem::resource('PostResource'),
                MenuItem::dashboard('Analytics'),
                MenuItem::externalLink('External', 'https://example.com'),
            ]),
        ]);

        $json = $menu->jsonSerialize();

        $this->assertEquals('Dashboard', $json[0]['name']);
        $this->assertEquals('/admin/dashboards/MainDashboard', $json[0]['path']);

        $this->assertEquals('Users', $json[1]['name']);
        $this->assertEquals('/admin/resources/users', $json[1]['path']);

        $links = $json[2]['items'];
        $this->assertEquals('Internal', $links[0]['label']);
        $this->assertEquals('Posts', $links[1]['label']);
        $this->assertEquals('Analytics', $links[2]['label']);
        $this->assertEquals('External', $links[3]['label']);
        $this->assertTrue($links[3]['meta']['external']);
    }
}
