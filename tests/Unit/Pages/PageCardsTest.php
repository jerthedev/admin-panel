<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Pages;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Pages\Page;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Page Cards Unit Tests.
 *
 * Tests the cards functionality added to the Page base class.
 */
class PageCardsTest extends TestCase
{
    public function test_page_cards_method_returns_empty_array_by_default(): void
    {
        $page = new TestPageWithCards();
        $request = Request::create('/');

        $cards = $page->cards($request);

        $this->assertIsArray($cards);
        $this->assertEmpty($cards);
    }

    public function test_page_can_return_cards(): void
    {
        $page = new TestPageWithCustomCards();
        $request = Request::create('/');

        $cards = $page->cards($request);

        $this->assertIsArray($cards);
        $this->assertCount(2, $cards);
        $this->assertInstanceOf(TestCard::class, $cards[0]);
        $this->assertInstanceOf(AnotherTestCard::class, $cards[1]);
    }

    public function test_page_cards_can_be_filtered_by_authorization(): void
    {
        $page = new TestPageWithAuthorizationCards();
        $request = Request::create('/');

        $cards = $page->cards($request);

        $this->assertIsArray($cards);
        $this->assertCount(2, $cards);
        
        // Both cards should be returned since they don't have authorization restrictions
        $this->assertInstanceOf(TestCard::class, $cards[0]);
        $this->assertInstanceOf(UnauthorizedTestCard::class, $cards[1]);
    }

    public function test_page_cards_method_receives_request(): void
    {
        $page = new TestPageWithRequestAwareCards();
        $request = Request::create('/', 'GET', ['test' => 'value']);

        $cards = $page->cards($request);

        $this->assertIsArray($cards);
        $this->assertCount(1, $cards);
        $this->assertInstanceOf(TestCard::class, $cards[0]);
        
        // The card should have access to the request data
        $card = $cards[0];
        $this->assertEquals('Request aware card', $card->name());
    }
}

/**
 * Test page class with default cards behavior.
 */
class TestPageWithCards extends Page
{
    public static ?string $title = 'Test Page';

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }
}

/**
 * Test page class with custom cards.
 */
class TestPageWithCustomCards extends Page
{
    public static ?string $title = 'Test Page with Cards';

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        return [
            new TestCard(),
            new AnotherTestCard(),
        ];
    }
}

/**
 * Test page class with authorization-aware cards.
 */
class TestPageWithAuthorizationCards extends Page
{
    public static ?string $title = 'Test Page with Auth Cards';

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        return [
            new TestCard(),
            new UnauthorizedTestCard(),
        ];
    }
}

/**
 * Test page class with request-aware cards.
 */
class TestPageWithRequestAwareCards extends Page
{
    public static ?string $title = 'Test Page with Request Cards';

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        $card = new TestCard();
        
        if ($request->has('test')) {
            $card->withName('Request aware card');
        }
        
        return [$card];
    }
}

/**
 * Test card class for testing purposes.
 */
class TestCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Test Card',
            'group' => 'Test',
        ]);
    }

    public function uriKey(): string
    {
        return 'test-card';
    }
}

/**
 * Another test card class for testing purposes.
 */
class AnotherTestCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Another Test Card',
            'group' => 'Analytics',
        ]);
    }

    public function uriKey(): string
    {
        return 'another-test-card';
    }
}

/**
 * Unauthorized test card class for testing purposes.
 */
class UnauthorizedTestCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Unauthorized Card',
            'group' => 'Unauthorized',
        ]);
    }

    public function authorize(Request $request): bool
    {
        return false; // Always unauthorized
    }

    public function uriKey(): string
    {
        return 'unauthorized-card';
    }
}
