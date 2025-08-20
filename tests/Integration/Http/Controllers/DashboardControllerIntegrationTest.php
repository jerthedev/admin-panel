<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Http\Controllers;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Http\Controllers\DashboardController;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Integration tests for DashboardController with Laravel context.
 */
class DashboardControllerIntegrationTest extends TestCase
{
    private DashboardController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new DashboardController();
    }

    public function test_dashboard_index_returns_inertia_response(): void
    {
        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        // Verify Inertia response structure
        $this->assertArrayHasKey('page', $response->getOriginalContent()->getData());
        $pageData = $response->getOriginalContent()->getData()['page'];
        
        $this->assertArrayHasKey('component', $pageData);
        $this->assertEquals('Dashboard', $pageData['component']);
        
        $this->assertArrayHasKey('props', $pageData);
        $props = $pageData['props'];
        
        // Verify cards key exists (not widgets)
        $this->assertArrayHasKey('cards', $props);
        $this->assertArrayNotHasKey('widgets', $props);
    }

    public function test_get_cards_integrates_with_config(): void
    {
        // Set up test card configuration
        config(['admin-panel.dashboard.default_cards' => [
            TestDashboardCard::class
        ]]);

        $admin = $this->createAdminUser();
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn() => $admin);

        $adminPanel = app(AdminPanel::class);
        
        $reflection = new \ReflectionMethod($this->controller, 'getCards');
        $reflection->setAccessible(true);
        
        $cards = $reflection->invoke($this->controller, $adminPanel, $request);

        $this->assertIsArray($cards);
        $this->assertCount(1, $cards);
        
        $card = $cards[0];
        $this->assertArrayHasKey('component', $card);
        $this->assertArrayHasKey('data', $card);
        $this->assertArrayHasKey('title', $card);
        $this->assertArrayHasKey('size', $card);
        
        $this->assertEquals('TestDashboardCard', $card['component']);
        $this->assertEquals('Test Dashboard Card', $card['title']);
    }

    public function test_get_cards_respects_authorization(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            UnauthorizedTestCard::class
        ]]);

        $admin = $this->createAdminUser();
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn() => $admin);

        $adminPanel = app(AdminPanel::class);
        
        $reflection = new \ReflectionMethod($this->controller, 'getCards');
        $reflection->setAccessible(true);
        
        $cards = $reflection->invoke($this->controller, $adminPanel, $request);

        $this->assertIsArray($cards);
        $this->assertEmpty($cards); // Should be empty due to authorization failure
    }

    public function test_get_cards_handles_empty_configuration(): void
    {
        config(['admin-panel.dashboard.default_cards' => []]);

        $admin = $this->createAdminUser();
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn() => $admin);

        $adminPanel = app(AdminPanel::class);
        
        $reflection = new \ReflectionMethod($this->controller, 'getCards');
        $reflection->setAccessible(true);
        
        $cards = $reflection->invoke($this->controller, $adminPanel, $request);

        $this->assertIsArray($cards);
        $this->assertEmpty($cards);
    }

    public function test_get_cards_handles_non_existent_card_classes(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            'NonExistentCardClass',
            TestDashboardCard::class
        ]]);

        $admin = $this->createAdminUser();
        $request = Request::create('/admin', 'GET');
        $request->setUserResolver(fn() => $admin);

        $adminPanel = app(AdminPanel::class);
        
        $reflection = new \ReflectionMethod($this->controller, 'getCards');
        $reflection->setAccessible(true);
        
        $cards = $reflection->invoke($this->controller, $adminPanel, $request);

        $this->assertIsArray($cards);
        $this->assertCount(1, $cards); // Only the valid card should be included
        $this->assertEquals('TestDashboardCard', $cards[0]['component']);
    }

    public function test_dashboard_response_includes_all_expected_sections(): void
    {
        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $props = $response->getOriginalContent()->getData()['page']['props'];
        
        // Verify all expected sections are present
        $this->assertArrayHasKey('metrics', $props);
        $this->assertArrayHasKey('cards', $props); // Changed from 'widgets'
        $this->assertArrayHasKey('recentActivity', $props);
        $this->assertArrayHasKey('quickActions', $props);
        $this->assertArrayHasKey('systemInfo', $props);
    }

    public function test_cards_response_structure_matches_nova_format(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            TestDashboardCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        
        $this->assertIsArray($cards);
        
        if (!empty($cards)) {
            $card = $cards[0];
            
            // Verify Nova-compatible structure
            $this->assertArrayHasKey('component', $card);
            $this->assertArrayHasKey('data', $card);
            $this->assertArrayHasKey('title', $card);
            $this->assertArrayHasKey('size', $card);
            
            $this->assertIsString($card['component']);
            $this->assertIsArray($card['data']);
            $this->assertIsString($card['title']);
            $this->assertIsString($card['size']);
        }
    }

    public function test_multiple_cards_are_processed_correctly(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            TestDashboardCard::class,
            AnotherTestCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        
        $this->assertIsArray($cards);
        $this->assertCount(2, $cards);
        
        $this->assertEquals('TestDashboardCard', $cards[0]['component']);
        $this->assertEquals('AnotherTestCard', $cards[1]['component']);
    }
}

/**
 * Test card implementation for integration testing.
 */
class TestDashboardCard extends Card
{
    public function component(): string
    {
        return 'TestDashboardCard';
    }

    public function data(Request $request): array
    {
        return [
            'test_data' => 'integration_test',
            'user_id' => $request->user()?->id
        ];
    }

    public function title(): string
    {
        return 'Test Dashboard Card';
    }

    public function size(): string
    {
        return 'md';
    }

    public function authorize(Request $request): bool
    {
        return true;
    }
}

/**
 * Unauthorized test card for testing authorization.
 */
class UnauthorizedTestCard extends Card
{
    public function component(): string
    {
        return 'UnauthorizedCard';
    }

    public function data(Request $request): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Unauthorized Card';
    }

    public function size(): string
    {
        return 'sm';
    }

    public function authorize(Request $request): bool
    {
        return false; // Always deny authorization
    }
}

/**
 * Another test card for multiple card testing.
 */
class AnotherTestCard extends Card
{
    public function component(): string
    {
        return 'AnotherTestCard';
    }

    public function data(Request $request): array
    {
        return ['another' => 'test'];
    }

    public function title(): string
    {
        return 'Another Test Card';
    }

    public function size(): string
    {
        return 'lg';
    }

    public function authorize(Request $request): bool
    {
        return true;
    }
}
