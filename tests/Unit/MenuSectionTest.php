<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Menu\Badge;
use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Tests\TestCase;

class MenuSectionTest extends TestCase
{
    public function test_menu_section_can_be_created_with_make(): void
    {
        $section = MenuSection::make('Dashboard');

        $this->assertEquals('Dashboard', $section->name);
        $this->assertEmpty($section->items);
    }

    public function test_menu_section_can_be_created_with_items(): void
    {
        $item1 = MenuItem::make('Users', '/users');
        $item2 = MenuItem::make('Posts', '/posts');

        $section = MenuSection::make('Content', [$item1, $item2]);

        $this->assertEquals('Content', $section->name);
        $this->assertCount(2, $section->items);
    }

    public function test_menu_section_can_set_icon(): void
    {
        $section = MenuSection::make('Dashboard')
            ->icon('chart-bar');

        $this->assertEquals('chart-bar', $section->icon);
    }

    public function test_menu_section_can_set_badge(): void
    {
        $section = MenuSection::make('Issues')
            ->withBadge(5, 'danger');

        $this->assertEquals(5, $section->badge);
        $this->assertEquals('danger', $section->badgeType);
    }

    public function test_menu_section_can_set_badge_with_closure(): void
    {
        $section = MenuSection::make('Users')
            ->withBadge(fn() => 10);

        $this->assertInstanceOf(\Closure::class, $section->badge);
        $this->assertEquals(10, $section->resolveBadge());
    }

    public function test_menu_section_can_set_badge_with_badge_instance(): void
    {
        $badge = Badge::make('New!', 'info');
        $section = MenuSection::make('Features')
            ->withBadge($badge);

        $this->assertSame($badge, $section->badge);
    }

    public function test_menu_section_can_be_collapsible(): void
    {
        $section = MenuSection::make('Admin')
            ->collapsible();

        $this->assertTrue($section->collapsible);
    }

    public function test_menu_section_can_set_path(): void
    {
        $section = MenuSection::make('Dashboard')
            ->path('/dashboard');

        $this->assertEquals('/dashboard', $section->path);
    }

    public function test_menu_section_supports_fluent_chaining(): void
    {
        $section = MenuSection::make('Business')
            ->icon('briefcase')
            ->withBadge(3, 'warning')
            ->path('/business'); // Path first, then can't be collapsible

        $this->assertEquals('Business', $section->name);
        $this->assertEquals('briefcase', $section->icon);
        $this->assertEquals(3, $section->badge);
        $this->assertEquals('warning', $section->badgeType);
        $this->assertFalse($section->collapsible);
        $this->assertEquals('/business', $section->path);
    }

    public function test_menu_section_is_json_serializable(): void
    {
        $item = MenuItem::make('Users', '/users');
        $section = MenuSection::make('Admin', [$item])
            ->icon('shield')
            ->withBadge(2, 'info')
            ->collapsible();

        $json = $section->jsonSerialize();

        $this->assertEquals([
            'name' => 'Admin',
            'icon' => 'shield',
            'badge' => 2,
            'badgeType' => 'info',
            'collapsible' => true,
            'collapsed' => false,
            'stateId' => 'menu_section_admin',
            'path' => null,
            'items' => [$item->toArray()],
        ], $json);
    }

    public function test_menu_section_factory_methods(): void
    {
        // Test dashboard factory
        $dashboardSection = MenuSection::dashboard('MainDashboard');
        $this->assertEquals('Dashboard', $dashboardSection->name);
        $this->assertEquals('/admin/dashboards/MainDashboard', $dashboardSection->path);

        // Test resource factory
        $resourceSection = MenuSection::resource('UserResource');
        $this->assertEquals('Users', $resourceSection->name);
        $this->assertEquals('/admin/resources/users', $resourceSection->path);
    }

    public function test_menu_section_with_badge_if(): void
    {
        $section = MenuSection::make('Issues')
            ->withBadgeIf('New!', 'info', fn() => true);

        $this->assertEquals('New!', $section->badge);
        $this->assertEquals('info', $section->badgeType);

        $section2 = MenuSection::make('Issues')
            ->withBadgeIf('New!', 'info', fn() => false);

        $this->assertNull($section2->badge);
    }

    public function test_menu_section_can_see_authorization(): void
    {
        $section = MenuSection::make('Admin')
            ->canSee(fn() => true);

        $this->assertTrue($section->isVisible());

        $section2 = MenuSection::make('Admin')
            ->canSee(fn() => false);

        $this->assertFalse($section2->isVisible());
    }

    public function test_menu_section_defaults_to_visible(): void
    {
        $section = MenuSection::make('Public');

        $this->assertTrue($section->isVisible());
    }

    public function test_collapsible_section_cannot_have_path(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Collapsible sections cannot have a direct path');

        MenuSection::make('Test')
            ->collapsible()
            ->path('/test');
    }

    public function test_section_with_path_cannot_be_collapsible(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sections with a path cannot be collapsible');

        MenuSection::make('Test')
            ->path('/test')
            ->collapsible();
    }

    public function test_section_can_be_collapsible_without_path(): void
    {
        $section = MenuSection::make('Test')
            ->collapsible();

        $this->assertTrue($section->collapsible);
        $this->assertNull($section->path);
    }

    public function test_section_can_have_path_without_being_collapsible(): void
    {
        $section = MenuSection::make('Test')
            ->path('/test');

        $this->assertEquals('/test', $section->path);
        $this->assertFalse($section->collapsible);
    }

    public function test_section_collapsible_state_serialization(): void
    {
        $section = MenuSection::make('Collapsible Section')
            ->collapsible()
            ->icon('folder');

        $array = $section->toArray();

        $this->assertTrue($array['collapsible']);
        $this->assertNull($array['path']);
        $this->assertEquals('folder', $array['icon']);
    }
}
