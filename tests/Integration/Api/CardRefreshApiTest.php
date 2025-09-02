<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

class CardRefreshApiTest extends TestCase
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

        // Register test dashboard with cards
        $adminPanel->registerDashboards([
            TestDashboardWithRefreshableCard::class,
        ]);

        // Bind the fresh instance to the container
        $this->app->instance(AdminPanel::class, $adminPanel);
    }

    public function test_dashboard_cards_api_returns_cards(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->getJson('/admin/api/dashboards/test-refreshable/cards');

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
        $this->assertCount(1, $cards);
        $this->assertEquals('test-refreshable-card', $cards[0]['id']);
        $this->assertEquals('TestRefreshableCard', $cards[0]['component']);
        $this->assertEquals('Test Refreshable Card', $cards[0]['title']);
    }

    public function test_refresh_card_api_returns_updated_card_data(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->postJson('/admin/api/dashboards/test-refreshable/cards/test-refreshable-card/refresh');

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
        $this->assertEquals('test-refreshable-card', $card['id']);
        $this->assertEquals('TestRefreshableCard', $card['component']);
        $this->assertEquals('Test Refreshable Card', $card['title']);
        $this->assertArrayHasKey('refreshed_at', $card['data']);
    }

    public function test_refresh_card_api_returns_404_for_unknown_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->postJson('/admin/api/dashboards/unknown/cards/test-card/refresh');

        $response->assertNotFound()
            ->assertJson(['error' => 'Dashboard not found']);
    }

    public function test_refresh_card_api_returns_404_for_unknown_card(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin)
            ->postJson('/admin/api/dashboards/test-refreshable/cards/unknown-card/refresh');

        $response->assertNotFound()
            ->assertJson(['error' => 'Card not found']);
    }
}

/**
 * Test dashboard with refreshable card.
 */
class TestDashboardWithRefreshableCard extends Dashboard
{
    public function name(): string
    {
        return 'Test Refreshable Dashboard';
    }

    public function uriKey(): string
    {
        return 'test-refreshable';
    }

    public function cards(): array
    {
        return [
            new TestRefreshableCard(),
        ];
    }
}

/**
 * Test refreshable card.
 */
class TestRefreshableCard extends Card
{
    public function __construct()
    {
        parent::__construct();

        $this->withMeta([
            'title' => 'Test Refreshable Card',
            'refreshable' => true,
        ]);
    }

    public function uriKey(): string
    {
        return 'test-refreshable-card';
    }

    public function component(): string
    {
        return 'TestRefreshableCard';
    }

    public function title(): string
    {
        return 'Test Refreshable Card';
    }

    public function data(Request $request): array
    {
        return [
            'value' => rand(1, 100),
            'refreshed_at' => now()->toISOString(),
        ];
    }
}
