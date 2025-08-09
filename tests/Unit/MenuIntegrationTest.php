<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Http\Request;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class MenuIntegrationTest extends TestCase
{
    public function test_pages_appear_in_navigation(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([TestMenuPage::class]);

        $navigationPages = $adminPanel->getNavigationPages();

        $this->assertCount(1, $navigationPages);
        $this->assertInstanceOf(TestMenuPage::class, $navigationPages->first());
    }

    public function test_pages_are_grouped_correctly(): void
    {
        $adminPanel = app(AdminPanel::class);
        $adminPanel->registerPages([
            TestMenuPage::class,
            TestSystemPage::class,
            TestUserPage::class,
        ]);

        $navigationPages = $adminPanel->getNavigationPages();
        
        // Group pages by their group property
        $groupedPages = $navigationPages->groupBy(function ($page) {
            return $page::group() ?? 'Default';
        });

        $this->assertArrayHasKey('Testing', $groupedPages);
        $this->assertArrayHasKey('System', $groupedPages);
        $this->assertArrayHasKey('Users', $groupedPages);
        
        $this->assertCount(1, $groupedPages['Testing']);
        $this->assertCount(1, $groupedPages['System']);
        $this->assertCount(1, $groupedPages['Users']);
    }
}

class TestMenuPage extends Page
{
    public static array $components = ['Pages/TestMenu'];
    public static ?string $title = 'Test Menu Page';
    public static ?string $group = 'Testing';
    public static ?string $icon = 'test-tube';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function actions(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return true;
    }
}

class TestSystemPage extends Page
{
    public static array $components = ['Pages/TestSystem'];
    public static ?string $title = 'Test System Page';
    public static ?string $group = 'System';
    public static ?string $icon = 'server';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function actions(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return true;
    }
}

class TestUserPage extends Page
{
    public static array $components = ['Pages/TestUser'];
    public static ?string $title = 'Test User Page';
    public static ?string $group = 'Users';
    public static ?string $icon = 'user';

    public function fields(Request $request): array
    {
        return [];
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function actions(Request $request): array
    {
        return [];
    }

    public static function authorizedToViewAny(Request $request): bool
    {
        return true;
    }
}
