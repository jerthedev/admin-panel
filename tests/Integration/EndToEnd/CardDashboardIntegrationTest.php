<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\EndToEnd;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * End-to-end test for complete card-dashboard integration.
 * 
 * This test verifies that all components work together:
 * - Card base class with all required methods
 * - Dashboard integration with cards
 * - Configuration system (default_cards)
 * - API endpoints for card refresh
 * - Authorization and error handling
 * - Grid layout and positioning
 */
class CardDashboardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a fresh AdminPanel instance for this test
        $adminPanel = new AdminPanel(
            app(\JTD\AdminPanel\Support\ResourceDiscovery::class),
            app(\JTD\AdminPanel\Support\PageDiscovery::class),
            app(\JTD\AdminPanel\Support\PageRegistry::class),
            app(\JTD\AdminPanel\Support\CardDiscovery::class)
        );

        // Register test dashboards
        $adminPanel->registerDashboards([
            E2ETestDashboard::class,
        ]);

        // Bind the fresh instance to the container
        $this->app->instance(AdminPanel::class, $adminPanel);
    }

    public function test_complete_card_dashboard_integration(): void
    {
        $admin = $this->createAdminUser();

        // Test 1: Dashboard renders with cards
        $response = $this->actingAs($admin)
            ->get('/admin/dashboards/e2e-test');

        $response->assertOk();
        
        $pageData = $response->getOriginalContent()->getData()['page'];
        $this->assertEquals('Dashboard', $pageData['component']);
        
        $props = $pageData['props'];
        $this->assertArrayHasKey('dashboard', $props);
        $this->assertArrayHasKey('cards', $props);
        
        // Verify dashboard properties
        $dashboard = $props['dashboard'];
        $this->assertEquals('E2E Test Dashboard', $dashboard['name']);
        $this->assertEquals('e2e-test', $dashboard['uriKey']);
        $this->assertTrue($dashboard['showRefreshButton']);
        
        // Verify cards are present and properly formatted
        $cards = $props['cards'];
        $this->assertCount(3, $cards);
        
        // Test small card
        $smallCard = collect($cards)->firstWhere('size', 'sm');
        $this->assertNotNull($smallCard);
        $this->assertEquals('E2ESmallCard', $smallCard['component']);
        $this->assertEquals('Small Card', $smallCard['title']);
        
        // Test large card
        $largeCard = collect($cards)->firstWhere('size', 'lg');
        $this->assertNotNull($largeCard);
        $this->assertEquals('E2ELargeCard', $largeCard['component']);
        $this->assertEquals('Large Card', $largeCard['title']);
        
        // Test full-width card
        $fullCard = collect($cards)->firstWhere('size', 'full');
        $this->assertNotNull($fullCard);
        $this->assertEquals('E2EFullCard', $fullCard['component']);
        $this->assertEquals('Full Width Card', $fullCard['title']);
    }

    public function test_card_refresh_api_integration(): void
    {
        $admin = $this->createAdminUser();

        // Test 2: Card refresh API works
        $response = $this->actingAs($admin)
            ->postJson('/admin/api/dashboards/e2e-test/cards/e2e-small-card/refresh');

        $response->assertOk()
            ->assertJsonStructure([
                'card' => [
                    'id',
                    'component',
                    'title',
                    'data',
                    'size',
                    'meta',
                    'updated_at',
                ],
            ]);

        $card = $response->json('card');
        $this->assertEquals('e2e-small-card', $card['id']);
        $this->assertEquals('E2ESmallCard', $card['component']);
        $this->assertEquals('Small Card', $card['title']);
        $this->assertEquals('sm', $card['size']);
        $this->assertArrayHasKey('refreshed_at', $card['data']);
    }

    public function test_dashboard_cards_api_integration(): void
    {
        $admin = $this->createAdminUser();

        // Test 3: Dashboard cards API works
        $response = $this->actingAs($admin)
            ->getJson('/admin/api/dashboards/e2e-test/cards');

        $response->assertOk()
            ->assertJsonStructure([
                'cards' => [
                    '*' => [
                        'id',
                        'component',
                        'title',
                        'data',
                        'size',
                        'meta',
                        'updated_at',
                    ],
                ],
            ]);

        $cards = $response->json('cards');
        $this->assertCount(3, $cards);
        
        // Verify all cards have required properties
        foreach ($cards as $card) {
            $this->assertArrayHasKey('id', $card);
            $this->assertArrayHasKey('component', $card);
            $this->assertArrayHasKey('title', $card);
            $this->assertArrayHasKey('data', $card);
            $this->assertArrayHasKey('size', $card);
            $this->assertArrayHasKey('meta', $card);
            $this->assertArrayHasKey('updated_at', $card);
        }
    }

    public function test_card_authorization_integration(): void
    {
        $admin = $this->createAdminUser();

        // Test 4: Card authorization is respected
        // The E2ETestDashboard includes an unauthorized card that should be filtered out
        $response = $this->actingAs($admin)
            ->get('/admin/dashboards/e2e-test');

        $response->assertOk();
        
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $cards = $props['cards'];
        
        // Should only have 3 authorized cards, not 4 (unauthorized card filtered out)
        $this->assertCount(3, $cards);
        
        // Verify unauthorized card is not present
        $unauthorizedCard = collect($cards)->firstWhere('component', 'E2EUnauthorizedCard');
        $this->assertNull($unauthorizedCard);
    }

    public function test_default_cards_configuration_integration(): void
    {
        // Test 5: Default cards configuration works
        config(['admin-panel.dashboard.default_cards' => [
            E2EConfigCard::class,
        ]]);

        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $props = $response->getOriginalContent()->getData()['page']['props'];
        $cards = $props['cards'];
        
        // Should have the default card from configuration
        $this->assertCount(1, $cards);
        $configCard = $cards[0];
        $this->assertEquals('E2EConfigCard', $configCard['component']);
        $this->assertEquals('Config Card', $configCard['title']);
    }
}

/**
 * Test dashboard for end-to-end testing.
 */
class E2ETestDashboard extends Dashboard
{
    public function __construct()
    {
        $this->showRefreshButton();
    }

    public function name(): string
    {
        return 'E2E Test Dashboard';
    }

    public function uriKey(): string
    {
        return 'e2e-test';
    }

    public function cards(): array
    {
        return [
            new E2ESmallCard(),
            new E2ELargeCard(),
            new E2EFullCard(),
            new E2EUnauthorizedCard(), // This should be filtered out
        ];
    }
}

/**
 * Small test card.
 */
class E2ESmallCard extends Card
{
    public function uriKey(): string
    {
        return 'e2e-small-card';
    }

    public function component(): string
    {
        return 'E2ESmallCard';
    }

    public function title(): string
    {
        return 'Small Card';
    }

    public function size(): string
    {
        return 'sm';
    }

    public function data(Request $request): array
    {
        return [
            'value' => 42,
            'refreshed_at' => now()->toISOString(),
        ];
    }
}

/**
 * Large test card.
 */
class E2ELargeCard extends Card
{
    public function uriKey(): string
    {
        return 'e2e-large-card';
    }

    public function component(): string
    {
        return 'E2ELargeCard';
    }

    public function title(): string
    {
        return 'Large Card';
    }

    public function size(): string
    {
        return 'lg';
    }

    public function data(Request $request): array
    {
        return [
            'items' => ['item1', 'item2', 'item3'],
            'refreshed_at' => now()->toISOString(),
        ];
    }
}

/**
 * Full-width test card.
 */
class E2EFullCard extends Card
{
    public function uriKey(): string
    {
        return 'e2e-full-card';
    }

    public function component(): string
    {
        return 'E2EFullCard';
    }

    public function title(): string
    {
        return 'Full Width Card';
    }

    public function size(): string
    {
        return 'full';
    }

    public function data(Request $request): array
    {
        return [
            'chart_data' => [1, 2, 3, 4, 5],
            'refreshed_at' => now()->toISOString(),
        ];
    }
}

/**
 * Unauthorized test card (should be filtered out).
 */
class E2EUnauthorizedCard extends Card
{
    public function uriKey(): string
    {
        return 'e2e-unauthorized-card';
    }

    public function component(): string
    {
        return 'E2EUnauthorizedCard';
    }

    public function title(): string
    {
        return 'Unauthorized Card';
    }

    public function authorize(Request $request): bool
    {
        return false; // Always unauthorized
    }

    public function data(Request $request): array
    {
        return ['secret' => 'data'];
    }
}

/**
 * Configuration test card.
 */
class E2EConfigCard extends Card
{
    public function uriKey(): string
    {
        return 'e2e-config-card';
    }

    public function component(): string
    {
        return 'E2EConfigCard';
    }

    public function title(): string
    {
        return 'Config Card';
    }

    public function data(Request $request): array
    {
        return [
            'from_config' => true,
            'refreshed_at' => now()->toISOString(),
        ];
    }
}
