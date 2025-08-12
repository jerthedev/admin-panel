<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Tests\TestCase;

class CollapsibleStateTest extends TestCase
{
    public function test_menu_section_collapsed_state(): void
    {
        $section = MenuSection::make('Test Section')
            ->collapsible()
            ->collapsed();

        $this->assertTrue($section->collapsed);
        $this->assertTrue($section->collapsible);
    }

    public function test_menu_section_expanded_state(): void
    {
        $section = MenuSection::make('Test Section')
            ->collapsible()
            ->collapsed(false);

        $this->assertFalse($section->collapsed);
        $this->assertTrue($section->collapsible);
    }

    public function test_menu_section_defaults_to_expanded(): void
    {
        $section = MenuSection::make('Test Section')
            ->collapsible();

        $this->assertFalse($section->collapsed);
    }

    public function test_menu_section_state_id_generation(): void
    {
        $section = MenuSection::make('Test Section');

        $stateId = $section->getStateId();

        $this->assertEquals('menu_section_test_section', $stateId);
    }

    public function test_menu_section_custom_state_id(): void
    {
        $section = MenuSection::make('Test Section')
            ->stateId('custom_section_id');

        $this->assertEquals('custom_section_id', $section->getStateId());
    }

    public function test_menu_section_state_serialization(): void
    {
        $section = MenuSection::make('Collapsible Section')
            ->collapsible()
            ->collapsed()
            ->stateId('custom_id');

        $array = $section->toArray();

        $this->assertTrue($array['collapsible']);
        $this->assertTrue($array['collapsed']);
        $this->assertEquals('custom_id', $array['stateId']);
    }

    public function test_menu_group_collapsed_state(): void
    {
        $group = MenuGroup::make('Test Group')
            ->collapsible()
            ->collapsed();

        $this->assertTrue($group->collapsed);
        $this->assertTrue($group->collapsible);
    }

    public function test_menu_group_expanded_state(): void
    {
        $group = MenuGroup::make('Test Group')
            ->collapsible()
            ->collapsed(false);

        $this->assertFalse($group->collapsed);
        $this->assertTrue($group->collapsible);
    }

    public function test_menu_group_defaults_to_expanded(): void
    {
        $group = MenuGroup::make('Test Group')
            ->collapsible();

        $this->assertFalse($group->collapsed);
    }

    public function test_menu_group_state_id_generation(): void
    {
        $group = MenuGroup::make('Test Group');

        $stateId = $group->getStateId();

        $this->assertEquals('menu_group_test_group', $stateId);
    }

    public function test_menu_group_custom_state_id(): void
    {
        $group = MenuGroup::make('Test Group')
            ->stateId('custom_group_id');

        $this->assertEquals('custom_group_id', $group->getStateId());
    }

    public function test_menu_group_state_serialization(): void
    {
        $group = MenuGroup::make('Collapsible Group', [
            MenuItem::make('Item 1', '/item1'),
        ])->collapsible()
          ->collapsed()
          ->stateId('custom_group_id');

        $array = $group->toArray();

        $this->assertTrue($array['collapsible']);
        $this->assertTrue($array['collapsed']);
        $this->assertEquals('custom_group_id', $array['stateId']);
        $this->assertCount(1, $array['items']);
    }

    public function test_complex_menu_structure_with_state(): void
    {
        $section = MenuSection::make('Business', [
            MenuGroup::make('Licensing', [
                MenuItem::make('Licenses', '/licenses'),
                MenuItem::make('Refunds', '/refunds'),
            ])->collapsible()
              ->collapsed()
              ->stateId('licensing_group'),

            MenuGroup::make('Reports', [
                MenuItem::make('Sales', '/sales'),
                MenuItem::make('Analytics', '/analytics'),
            ])->collapsible()
              ->stateId('reports_group'),
        ])->collapsible()
          ->collapsed()
          ->stateId('business_section');

        $array = $section->toArray();

        // Test section state
        $this->assertTrue($array['collapsible']);
        $this->assertTrue($array['collapsed']);
        $this->assertEquals('business_section', $array['stateId']);

        // Test groups state
        $licensing = $array['items'][0];
        $this->assertTrue($licensing['collapsible']);
        $this->assertTrue($licensing['collapsed']);
        $this->assertEquals('licensing_group', $licensing['stateId']);

        $reports = $array['items'][1];
        $this->assertTrue($reports['collapsible']);
        $this->assertFalse($reports['collapsed']); // Default expanded
        $this->assertEquals('reports_group', $reports['stateId']);
    }

    public function test_state_id_with_special_characters(): void
    {
        $section = MenuSection::make('Test & Special Characters!');

        $stateId = $section->getStateId();

        $this->assertEquals('menu_section_test_special_characters', $stateId);
    }

    public function test_state_id_with_unicode_characters(): void
    {
        $section = MenuSection::make('Tëst Ünïcödé');

        $stateId = $section->getStateId();

        $this->assertEquals('menu_section_test_unicode', $stateId);
    }

    public function test_fluent_chaining_with_state_methods(): void
    {
        $section = MenuSection::make('Chained Section')
            ->collapsible()
            ->collapsed()
            ->stateId('chained_id')
            ->icon('folder')
            ->withBadge('New', 'info');

        $this->assertTrue($section->collapsible);
        $this->assertTrue($section->collapsed);
        $this->assertEquals('chained_id', $section->getStateId());
        $this->assertEquals('folder', $section->icon);
        $this->assertEquals('New', $section->resolveBadge());
    }

    public function test_non_collapsible_section_state_serialization(): void
    {
        $section = MenuSection::make('Non-Collapsible Section')
            ->path('/section');

        $array = $section->toArray();

        $this->assertFalse($array['collapsible']);
        $this->assertFalse($array['collapsed']);
        $this->assertEquals('menu_section_non_collapsible_section', $array['stateId']);
        $this->assertEquals('/section', $array['path']);
    }

    public function test_non_collapsible_group_state_serialization(): void
    {
        $group = MenuGroup::make('Non-Collapsible Group', [
            MenuItem::make('Item', '/item'),
        ]);

        $array = $group->toArray();

        $this->assertFalse($array['collapsible']);
        $this->assertFalse($array['collapsed']);
        $this->assertEquals('menu_group_non_collapsible_group', $array['stateId']);
    }
}
