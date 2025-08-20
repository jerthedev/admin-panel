<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * PageController Cards Integration Tests.
 *
 * Tests the complete cards workflow in PageController including
 * authorization, serialization, and frontend data passing.
 */
class PageControllerCardsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Register test pages
        AdminPanel::pages([
            TestPageWithCards::class,
            TestPageWithAuthCards::class,
        ]);
    }

    public function test_page_controller_includes_cards_in_response(): void
    {
        // Register the page route
        Route::get('/admin/pages/test-cards', function (Request $request) {
            return app(\JTD\AdminPanel\Http\Controllers\PageController::class)
                ->show($request);
        })->name('admin-panel.pages.test-cards');

        $response = $this->get('/admin/pages/test-cards');

        $response->assertStatus(200);
        
        // Check that cards are included in the Inertia props
        $props = $response->viewData('page')['props'];
        
        $this->assertArrayHasKey('cards', $props);
        $this->assertIsArray($props['cards']);
        $this->assertCount(2, $props['cards']);
        
        // Check card structure
        $card = $props['cards'][0];
        $this->assertArrayHasKey('name', $card);
        $this->assertArrayHasKey('uriKey', $card);
        $this->assertArrayHasKey('component', $card);
        $this->assertArrayHasKey('meta', $card);
        
        $this->assertEquals('Test Page Card', $card['name']);
        $this->assertEquals('test-page-card', $card['uriKey']);
    }

    public function test_page_controller_filters_unauthorized_cards(): void
    {
        // Register the page route
        Route::get('/admin/pages/test-auth-cards', function (Request $request) {
            return app(\JTD\AdminPanel\Http\Controllers\PageController::class)
                ->show($request);
        })->name('admin-panel.pages.test-auth-cards');

        $response = $this->get('/admin/pages/test-auth-cards');

        $response->assertStatus(200);
        
        // Check that only authorized cards are included
        $props = $response->viewData('page')['props'];
        
        $this->assertArrayHasKey('cards', $props);
        $this->assertIsArray($props['cards']);
        $this->assertCount(1, $props['cards']); // Only authorized card
        
        $card = $props['cards'][0];
        $this->assertEquals('Authorized Page Card', $card['name']);
    }

    public function test_page_controller_handles_empty_cards(): void
    {
        // Register a page with no cards
        AdminPanel::pages([TestPageWithoutCards::class]);
        
        Route::get('/admin/pages/test-no-cards', function (Request $request) {
            return app(\JTD\AdminPanel\Http\Controllers\PageController::class)
                ->show($request);
        })->name('admin-panel.pages.test-no-cards');

        $response = $this->get('/admin/pages/test-no-cards');

        $response->assertStatus(200);
        
        $props = $response->viewData('page')['props'];
        
        $this->assertArrayHasKey('cards', $props);
        $this->assertIsArray($props['cards']);
        $this->assertEmpty($props['cards']);
    }

    public function test_page_controller_cards_are_properly_serialized(): void
    {
        Route::get('/admin/pages/test-cards', function (Request $request) {
            return app(\JTD\AdminPanel\Http\Controllers\PageController::class)
                ->show($request);
        })->name('admin-panel.pages.test-cards');

        $response = $this->get('/admin/pages/test-cards');
        
        $props = $response->viewData('page')['props'];
        $card = $props['cards'][0];
        
        // Check that all card properties are serialized
        $this->assertArrayHasKey('name', $card);
        $this->assertArrayHasKey('uriKey', $card);
        $this->assertArrayHasKey('component', $card);
        $this->assertArrayHasKey('meta', $card);
        
        // Check meta data structure
        $this->assertIsArray($card['meta']);
        $this->assertArrayHasKey('title', $card['meta']);
        $this->assertArrayHasKey('group', $card['meta']);
        
        $this->assertEquals('Test Page Card', $card['meta']['title']);
        $this->assertEquals('Pages', $card['meta']['group']);
    }
}

/**
 * Test page class with cards.
 */
class TestPageWithCards extends Page
{
    public static ?string $title = 'Test Page with Cards';

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        return [
            new TestPageCard(),
            new AnotherTestPageCard(),
        ];
    }

    public static function uriKey(): string
    {
        return 'test-cards';
    }
}

/**
 * Test page class with authorization cards.
 */
class TestPageWithAuthCards extends Page
{
    public static ?string $title = 'Test Page with Auth Cards';

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        return [
            new AuthorizedPageCard(),
            new UnauthorizedPageCard(),
        ];
    }

    public static function uriKey(): string
    {
        return 'test-auth-cards';
    }
}

/**
 * Test page class without cards.
 */
class TestPageWithoutCards extends Page
{
    public static ?string $title = 'Test Page without Cards';

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public static function uriKey(): string
    {
        return 'test-no-cards';
    }
}

/**
 * Test card for pages.
 */
class TestPageCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Test Page Card',
            'group' => 'Pages',
        ]);
    }

    public function uriKey(): string
    {
        return 'test-page-card';
    }
}

/**
 * Another test card for pages.
 */
class AnotherTestPageCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Another Page Card',
            'group' => 'Pages',
        ]);
    }

    public function uriKey(): string
    {
        return 'another-page-card';
    }
}

/**
 * Authorized test card for pages.
 */
class AuthorizedPageCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Authorized Page Card',
            'group' => 'Auth',
        ]);
    }

    public function authorize(Request $request): bool
    {
        return true;
    }

    public function uriKey(): string
    {
        return 'authorized-page-card';
    }
}

/**
 * Unauthorized test card for pages.
 */
class UnauthorizedPageCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Unauthorized Page Card',
            'group' => 'Auth',
        ]);
    }

    public function authorize(Request $request): bool
    {
        return false;
    }

    public function uriKey(): string
    {
        return 'unauthorized-page-card';
    }
}
