<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Support\PageRegistry;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Page Unit Tests
 *
 * Tests for the Page base class including properties, methods,
 * and field integration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PageTest extends TestCase
{
    public function test_page_has_required_static_properties(): void
    {
        $page = new TestPage();

        $this->assertEquals('TestComponent', TestPage::$component);
        $this->assertEquals('Test Group', TestPage::$group);
        $this->assertEquals('Test Page', TestPage::$title);
        $this->assertEquals('test-icon', TestPage::$icon);
    }

    public function test_page_fields_method_returns_array(): void
    {
        $page = new TestPage();
        $request = Request::create('/');

        $fields = $page->fields($request);

        $this->assertIsArray($fields);
        $this->assertCount(1, $fields);
        $this->assertInstanceOf(Text::class, $fields[0]);
    }

    public function test_page_actions_method_returns_empty_array_by_default(): void
    {
        $page = new TestPage();
        $request = Request::create('/');

        $actions = $page->actions($request);

        $this->assertIsArray($actions);
        $this->assertEmpty($actions);
    }

    public function test_page_metrics_method_returns_empty_array_by_default(): void
    {
        $page = new TestPage();
        $request = Request::create('/');

        $metrics = $page->metrics($request);

        $this->assertIsArray($metrics);
        $this->assertEmpty($metrics);
    }

    public function test_page_data_method_returns_empty_array_by_default(): void
    {
        $page = new TestPage();
        $request = Request::create('/');

        $data = $page->data($request);

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function test_page_authorized_to_view_any_returns_true_by_default(): void
    {
        $request = Request::create('/');

        $authorized = TestPage::authorizedToViewAny($request);

        $this->assertTrue($authorized);
    }

    public function test_page_can_override_authorization(): void
    {
        $request = Request::create('/');

        $authorized = RestrictedTestPage::authorizedToViewAny($request);

        $this->assertFalse($authorized);
    }

    public function test_page_can_return_custom_data(): void
    {
        $page = new CustomDataPage();
        $request = Request::create('/');

        $data = $page->data($request);

        $this->assertIsArray($data);
        $this->assertEquals(['custom' => 'data'], $data);
    }

    public function test_page_properties_are_accessible(): void
    {
        $this->assertEquals('TestComponent', TestPage::$component);
        $this->assertEquals('Test Group', TestPage::$group);
        $this->assertEquals('Test Page', TestPage::$title);
        $this->assertEquals('test-icon', TestPage::$icon);
    }

    public function test_page_with_null_properties(): void
    {
        $this->assertNull(MinimalTestPage::$group);
        $this->assertNull(MinimalTestPage::$title);
        $this->assertNull(MinimalTestPage::$icon);
        $this->assertEquals('MinimalComponent', MinimalTestPage::$component);
    }

    public function test_page_registry_can_register_pages(): void
    {
        $registry = new PageRegistry();

        $registry->register([TestPage::class]);

        $pages = $registry->getPages();
        $this->assertCount(1, $pages);
        $this->assertEquals(TestPage::class, $pages->first());
    }

    public function test_page_registry_can_register_single_page(): void
    {
        $registry = new PageRegistry();

        $registry->page(TestPage::class);

        $pages = $registry->getPages();
        $this->assertCount(1, $pages);
        $this->assertEquals(TestPage::class, $pages->first());
    }

    public function test_admin_panel_pages_method_registers_pages(): void
    {
        AdminPanel::pages([TestPage::class]);

        $adminPanel = app(AdminPanel::class);
        $pages = $adminPanel->getPages();

        $this->assertContains(TestPage::class, $pages->toArray());
    }

    public function test_page_registry_validates_page_classes(): void
    {
        $registry = new PageRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend JTD\AdminPanel\Pages\Page');

        $registry->register([\stdClass::class]);
    }

    public function test_page_registry_validates_abstract_classes(): void
    {
        $registry = new PageRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be abstract');

        $registry->register([Page::class]);
    }

    public function test_page_registry_validates_missing_component(): void
    {
        $registry = new PageRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must define at least one component');

        $registry->register([InvalidTestPage::class]);
    }

    public function test_page_has_menu_method(): void
    {
        $page = new TestPage();
        $request = Request::create('/');

        // Mock the route function to avoid route not found errors
        $this->app['router']->get('admin/pages/test', function () {
            return 'test';
        })->name('admin-panel.pages.test');

        $menuItem = $page->menu($request);

        $this->assertInstanceOf(MenuItem::class, $menuItem);
        $this->assertEquals('Test Page', $menuItem->label);
        $this->assertEquals('test-icon', $menuItem->icon);
    }

    public function test_page_menu_uses_route_name(): void
    {
        $page = new TestPage();
        $request = Request::create('/');

        // Mock the route function to avoid route not found errors
        $this->app['router']->get('admin/pages/test', function () {
            return 'test';
        })->name('admin-panel.pages.test');

        $menuItem = $page->menu($request);

        $this->assertStringContains('admin/pages/test', $menuItem->url);
    }

    public function test_admin_panel_get_navigation_pages(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestPage::class]);

        $navigationPages = $adminPanel->getNavigationPages();

        $this->assertCount(1, $navigationPages);
        $this->assertInstanceOf(TestPage::class, $navigationPages->first());
    }
}

/**
 * Test Page Implementation
 */
class TestPage extends Page
{
    public static ?string $component = 'TestComponent';
    public static ?string $group = 'Test Group';
    public static ?string $title = 'Test Page';
    public static ?string $icon = 'test-icon';

    public function fields(Request $request): array
    {
        return [
            Text::make('Name'),
        ];
    }
}

/**
 * Restricted Test Page Implementation
 */
class RestrictedTestPage extends Page
{
    public static ?string $component = 'RestrictedComponent';

    public function fields(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return false;
    }
}

/**
 * Custom Data Test Page Implementation
 */
class CustomDataPage extends Page
{
    public static ?string $component = 'CustomDataComponent';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return ['custom' => 'data'];
    }
}

/**
 * Minimal Test Page Implementation
 */
class MinimalTestPage extends Page
{
    public static ?string $component = 'MinimalComponent';

    public function fields(Request $request): array
    {
        return [];
    }
}

/**
 * Invalid Test Page Implementation (missing component)
 */
class InvalidTestPage extends Page
{
    public function fields(Request $request): array
    {
        return [];
    }
}
