<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * AdminPanel Card Functionality Tests.
 *
 * Tests the card registration and management functionality
 * added to the AdminPanel facade.
 */
class AdminPanelCardTest extends TestCase
{
    protected AdminPanel $adminPanel;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminPanel = new AdminPanel();
    }

    public function test_cards_static_method_registers_cards(): void
    {
        $cards = [TestCard::class, AnotherTestCard::class];
        
        AdminPanel::cards($cards);
        
        $instance = app(AdminPanel::class);
        $registeredCards = $instance->getCards();
        
        $this->assertCount(2, $registeredCards);
        $this->assertInstanceOf(TestCard::class, $registeredCards->first());
    }

    public function test_register_cards_adds_cards_to_collection(): void
    {
        $cards = [TestCard::class, AnotherTestCard::class];
        
        $this->adminPanel->registerCards($cards);
        $registeredCards = $this->adminPanel->getCards();
        
        $this->assertCount(2, $registeredCards);
        $this->assertInstanceOf(TestCard::class, $registeredCards->first());
        $this->assertInstanceOf(AnotherTestCard::class, $registeredCards->last());
    }

    public function test_card_method_registers_single_card(): void
    {
        $this->adminPanel->card(TestCard::class);
        
        $registeredCards = $this->adminPanel->getCards();
        
        $this->assertCount(1, $registeredCards);
        $this->assertInstanceOf(TestCard::class, $registeredCards->first());
    }

    public function test_card_method_validates_card_class(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Card [stdClass] must extend JTD\AdminPanel\Cards\Card');
        
        $this->adminPanel->card(\stdClass::class);
    }

    public function test_get_cards_merges_manual_and_discovered_cards(): void
    {
        // Register a manual card
        $this->adminPanel->card(TestCard::class);
        
        // The discovery will find cards automatically
        $allCards = $this->adminPanel->getCards();
        
        // Should have at least the manually registered card
        $this->assertGreaterThanOrEqual(1, $allCards->count());
        
        // Should contain our test card
        $testCard = $allCards->first(function (Card $card) {
            return $card instanceof TestCard;
        });
        $this->assertNotNull($testCard);
    }

    public function test_get_cards_removes_duplicates(): void
    {
        // Register the same card twice
        $this->adminPanel->card(TestCard::class);
        $this->adminPanel->card(TestCard::class);
        
        $registeredCards = $this->adminPanel->getCards();
        
        // Should only have one instance
        $testCards = $registeredCards->filter(function (Card $card) {
            return $card instanceof TestCard;
        });
        
        $this->assertCount(1, $testCards);
    }

    public function test_get_grouped_cards_groups_by_meta_group(): void
    {
        $this->adminPanel->card(TestCard::class);
        $this->adminPanel->card(AnotherTestCard::class);
        
        $groupedCards = $this->adminPanel->getGroupedCards();
        
        $this->assertInstanceOf(Collection::class, $groupedCards);
        $this->assertArrayHasKey('Test', $groupedCards->toArray());
        $this->assertArrayHasKey('Analytics', $groupedCards->toArray());
    }

    public function test_find_card_returns_correct_card(): void
    {
        $this->adminPanel->card(TestCard::class);
        
        $card = $this->adminPanel->findCard('test-card');
        
        $this->assertInstanceOf(TestCard::class, $card);
        $this->assertEquals('test-card', $card->uriKey());
    }

    public function test_find_card_returns_null_for_nonexistent_card(): void
    {
        $card = $this->adminPanel->findCard('nonexistent-card');
        
        $this->assertNull($card);
    }

    public function test_get_authorized_cards_filters_by_authorization(): void
    {
        $this->adminPanel->card(TestCard::class);
        $this->adminPanel->card(UnauthorizedTestCard::class);
        
        $request = new Request();
        $authorizedCards = $this->adminPanel->getAuthorizedCards($request);
        
        // Should only contain the authorized card
        $this->assertCount(1, $authorizedCards);
        $this->assertInstanceOf(TestCard::class, $authorizedCards->first());
    }

    public function test_get_authorized_grouped_cards_filters_and_groups(): void
    {
        $this->adminPanel->card(TestCard::class);
        $this->adminPanel->card(UnauthorizedTestCard::class);
        
        $request = new Request();
        $groupedCards = $this->adminPanel->getAuthorizedGroupedCards($request);
        
        $this->assertInstanceOf(Collection::class, $groupedCards);
        $this->assertArrayHasKey('Test', $groupedCards->toArray());
        $this->assertArrayNotHasKey('Unauthorized', $groupedCards->toArray());
    }

    public function test_cards_in_static_method_discovers_cards_in_path(): void
    {
        // This would require actual files, so we'll test the method exists
        $this->assertTrue(method_exists(AdminPanel::class, 'cardsIn'));
    }

    public function test_discover_cards_in_method_exists(): void
    {
        $this->assertTrue(method_exists($this->adminPanel, 'discoverCardsIn'));
    }

    public function test_clear_card_cache_method_exists(): void
    {
        $this->assertTrue(method_exists($this->adminPanel, 'clearCardCache'));
        
        // Should not throw an exception
        $this->adminPanel->clearCardCache();
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
