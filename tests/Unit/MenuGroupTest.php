<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Tests\TestCase;

class MenuGroupTest extends TestCase
{
    public function test_menu_group_can_be_created_with_make(): void
    {
        $group = MenuGroup::make('Licensing');

        $this->assertEquals('Licensing', $group->name);
        $this->assertEmpty($group->items);
    }

    public function test_menu_group_can_be_created_with_items(): void
    {
        $item1 = MenuItem::make('Licenses', '/licenses');
        $item2 = MenuItem::make('Refunds', '/refunds');

        $group = MenuGroup::make('Business', [$item1, $item2]);

        $this->assertEquals('Business', $group->name);
        $this->assertCount(2, $group->items);
    }

    public function test_menu_group_can_be_collapsible(): void
    {
        $group = MenuGroup::make('Advanced')
            ->collapsible();

        $this->assertTrue($group->collapsible);
    }

    public function test_menu_group_defaults_to_not_collapsible(): void
    {
        $group = MenuGroup::make('Simple');

        $this->assertFalse($group->collapsible);
    }

    public function test_menu_group_can_set_collapsible_false(): void
    {
        $group = MenuGroup::make('Fixed')
            ->collapsible(false);

        $this->assertFalse($group->collapsible);
    }

    public function test_menu_group_supports_fluent_chaining(): void
    {
        $item = MenuItem::make('Test', '/test');

        $group = MenuGroup::make('Test Group', [$item])
            ->collapsible();

        $this->assertEquals('Test Group', $group->name);
        $this->assertCount(1, $group->items);
        $this->assertTrue($group->collapsible);
    }

    public function test_menu_group_is_json_serializable(): void
    {
        $item1 = MenuItem::make('Users', '/users');
        $item2 = MenuItem::make('Roles', '/roles');

        $group = MenuGroup::make('User Management', [$item1, $item2])
            ->collapsible();

        $json = $group->jsonSerialize();

        $this->assertEquals([
            'name' => 'User Management',
            'collapsible' => true,
            'collapsed' => false,
            'stateId' => 'menu_group_user_management',
            'items' => [
                $item1->toArray(),
                $item2->toArray(),
            ],
        ], $json);
    }

    public function test_menu_group_to_array(): void
    {
        $item = MenuItem::make('Settings', '/settings');
        $group = MenuGroup::make('Configuration', [$item]);

        $array = $group->toArray();

        $this->assertEquals([
            'name' => 'Configuration',
            'collapsible' => false,
            'collapsed' => false,
            'stateId' => 'menu_group_configuration',
            'items' => [$item->toArray()],
        ], $array);
    }

    public function test_menu_group_can_see_authorization(): void
    {
        $group = MenuGroup::make('Admin')
            ->canSee(fn() => true);

        $this->assertTrue($group->isVisible());

        $group2 = MenuGroup::make('Admin')
            ->canSee(fn() => false);

        $this->assertFalse($group2->isVisible());
    }

    public function test_menu_group_defaults_to_visible(): void
    {
        $group = MenuGroup::make('Public');

        $this->assertTrue($group->isVisible());
    }

    public function test_menu_group_with_empty_items(): void
    {
        $group = MenuGroup::make('Empty');

        $this->assertEmpty($group->items);
        $this->assertEquals([], $group->toArray()['items']);
    }

    public function test_menu_group_constructor(): void
    {
        $item = MenuItem::make('Test', '/test');
        $group = new MenuGroup('Direct', [$item]);

        $this->assertEquals('Direct', $group->name);
        $this->assertCount(1, $group->items);
        $this->assertEquals($item, $group->items[0]);
    }
}
