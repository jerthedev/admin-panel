<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Menu\Menu;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Tests\TestCase;

class MenuTest extends TestCase
{
    public function test_menu_can_be_created(): void
    {
        $menu = new Menu();

        $this->assertInstanceOf(Menu::class, $menu);
        $this->assertEmpty($menu->getItems());
    }

    public function test_menu_can_be_created_with_items(): void
    {
        $item1 = MenuItem::make('Home', '/home');
        $item2 = MenuItem::make('About', '/about');

        $menu = new Menu([$item1, $item2]);

        $this->assertCount(2, $menu->getItems());
        $this->assertEquals($item1, $menu->getItems()[0]);
        $this->assertEquals($item2, $menu->getItems()[1]);
    }

    public function test_menu_can_append_items(): void
    {
        $menu = new Menu();
        $item1 = MenuItem::make('Home', '/home');
        $item2 = MenuItem::make('About', '/about');

        $menu->append($item1);
        $menu->append($item2);

        $this->assertCount(2, $menu->getItems());
        $this->assertEquals($item1, $menu->getItems()[0]);
        $this->assertEquals($item2, $menu->getItems()[1]);
    }

    public function test_menu_can_prepend_items(): void
    {
        $menu = new Menu();
        $item1 = MenuItem::make('Home', '/home');
        $item2 = MenuItem::make('About', '/about');

        $menu->append($item1);
        $menu->prepend($item2);

        $this->assertCount(2, $menu->getItems());
        $this->assertEquals($item2, $menu->getItems()[0]); // Prepended item first
        $this->assertEquals($item1, $menu->getItems()[1]);
    }

    public function test_menu_append_returns_self_for_chaining(): void
    {
        $menu = new Menu();
        $item1 = MenuItem::make('Home', '/home');
        $item2 = MenuItem::make('About', '/about');

        $result = $menu->append($item1)->append($item2);

        $this->assertSame($menu, $result);
        $this->assertCount(2, $menu->getItems());
    }

    public function test_menu_prepend_returns_self_for_chaining(): void
    {
        $menu = new Menu();
        $item1 = MenuItem::make('Home', '/home');
        $item2 = MenuItem::make('About', '/about');

        $result = $menu->prepend($item1)->prepend($item2);

        $this->assertSame($menu, $result);
        $this->assertCount(2, $menu->getItems());
        $this->assertEquals($item2, $menu->getItems()[0]); // Last prepended is first
    }

    public function test_menu_can_add_multiple_items_at_once(): void
    {
        $menu = new Menu();
        $item1 = MenuItem::make('Home', '/home');
        $item2 = MenuItem::make('About', '/about');
        $item3 = MenuItem::make('Contact', '/contact');

        $menu->add([$item1, $item2, $item3]);

        $this->assertCount(3, $menu->getItems());
    }

    public function test_menu_can_check_if_empty(): void
    {
        $menu = new Menu();
        $this->assertTrue($menu->isEmpty());

        $menu->append(MenuItem::make('Home', '/home'));
        $this->assertFalse($menu->isEmpty());
    }

    public function test_menu_can_count_items(): void
    {
        $menu = new Menu();
        $this->assertEquals(0, $menu->count());

        $menu->append(MenuItem::make('Home', '/home'));
        $this->assertEquals(1, $menu->count());

        $menu->append(MenuItem::make('About', '/about'));
        $this->assertEquals(2, $menu->count());
    }

    public function test_menu_is_json_serializable(): void
    {
        $item1 = MenuItem::make('Home', '/home')->withIcon('home');
        $item2 = MenuItem::make('About', '/about')->withIcon('info');

        $menu = new Menu([$item1, $item2]);

        $json = $menu->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertCount(2, $json);
        $this->assertEquals('Home', $json[0]['label']);
        $this->assertEquals('About', $json[1]['label']);
    }

    public function test_menu_to_array(): void
    {
        $item = MenuItem::make('Test', '/test');
        $menu = new Menu([$item]);

        $array = $menu->toArray();

        $this->assertIsArray($array);
        $this->assertCount(1, $array);
        $this->assertEquals($item->toArray(), $array[0]);
    }

    public function test_menu_is_iterable(): void
    {
        $item1 = MenuItem::make('Home', '/home');
        $item2 = MenuItem::make('About', '/about');
        $menu = new Menu([$item1, $item2]);

        $items = [];
        foreach ($menu as $item) {
            $items[] = $item;
        }

        $this->assertCount(2, $items);
        $this->assertEquals($item1, $items[0]);
        $this->assertEquals($item2, $items[1]);
    }

    public function test_menu_is_countable(): void
    {
        $menu = new Menu([
            MenuItem::make('Home', '/home'),
            MenuItem::make('About', '/about'),
        ]);

        $this->assertEquals(2, count($menu));
    }
}
