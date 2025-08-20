<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\e2e\Dashboard;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Tests\TestCase;

/**
 * End-to-end tests for Dashboard Cards functionality.
 * 
 * Tests the complete flow from configuration to frontend rendering
 * of dashboard cards with Nova compatibility.
 */
class DashboardCardsE2ETest extends TestCase
{
    public function test_complete_dashboard_cards_workflow(): void
    {
        // Step 1: Configure dashboard cards
        config(['admin-panel.dashboard.default_cards' => [
            UserStatsCard::class,
            RevenueCard::class,
            ActivityCard::class
        ]]);

        // Step 2: Create admin user and authenticate
        $admin = $this->createAdminUser();

        // Step 3: Make dashboard request
        $response = $this->actingAs($admin)
            ->get('/admin');

        // Step 4: Verify response structure
        $response->assertOk();
        
        $pageData = $response->getOriginalContent()->getData()['page'];
        $this->assertEquals('Dashboard', $pageData['component']);
        
        $props = $pageData['props'];
        $this->assertArrayHasKey('cards', $props);
        $this->assertArrayNotHasKey('widgets', $props); // Ensure old key is gone
        
        // Step 5: Verify cards data structure
        $cards = $props['cards'];
        $this->assertIsArray($cards);
        $this->assertCount(3, $cards);
        
        // Step 6: Verify each card has Nova-compatible structure
        foreach ($cards as $card) {
            $this->assertArrayHasKey('component', $card);
            $this->assertArrayHasKey('data', $card);
            $this->assertArrayHasKey('title', $card);
            $this->assertArrayHasKey('size', $card);
            
            $this->assertIsString($card['component']);
            $this->assertIsArray($card['data']);
            $this->assertIsString($card['title']);
            $this->assertIsString($card['size']);
        }
        
        // Step 7: Verify specific card content
        $userStatsCard = collect($cards)->firstWhere('component', 'UserStatsCard');
        $this->assertNotNull($userStatsCard);
        $this->assertEquals('User Statistics', $userStatsCard['title']);
        $this->assertArrayHasKey('total_users', $userStatsCard['data']);
        
        $revenueCard = collect($cards)->firstWhere('component', 'RevenueCard');
        $this->assertNotNull($revenueCard);
        $this->assertEquals('Revenue Overview', $revenueCard['title']);
        $this->assertArrayHasKey('total_revenue', $revenueCard['data']);
    }

    public function test_dashboard_cards_authorization_workflow(): void
    {
        // Configure cards with different authorization levels
        config(['admin-panel.dashboard.default_cards' => [
            PublicCard::class,
            AdminOnlyCard::class,
            SuperAdminCard::class
        ]]);

        // Test with regular admin user
        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        
        // Should only see public and admin cards, not super admin
        $this->assertCount(2, $cards);
        
        $cardComponents = collect($cards)->pluck('component')->toArray();
        $this->assertContains('PublicCard', $cardComponents);
        $this->assertContains('AdminOnlyCard', $cardComponents);
        $this->assertNotContains('SuperAdminCard', $cardComponents);
    }

    public function test_dashboard_cards_error_handling_workflow(): void
    {
        // Configure cards including one that throws an error
        config(['admin-panel.dashboard.default_cards' => [
            UserStatsCard::class,
            ErrorCard::class, // This card will throw an exception
            RevenueCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        // Dashboard should still load despite card errors
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        
        // Should have 2 working cards (ErrorCard should be excluded)
        $this->assertCount(2, $cards);
        
        $cardComponents = collect($cards)->pluck('component')->toArray();
        $this->assertContains('UserStatsCard', $cardComponents);
        $this->assertContains('RevenueCard', $cardComponents);
        $this->assertNotContains('ErrorCard', $cardComponents);
    }

    public function test_dashboard_cards_performance_with_large_dataset(): void
    {
        // Configure multiple cards to test performance
        $cardClasses = [];
        for ($i = 1; $i <= 10; $i++) {
            $cardClasses[] = "PerformanceTestCard{$i}";
        }
        
        // Only include actual test cards
        config(['admin-panel.dashboard.default_cards' => [
            UserStatsCard::class,
            RevenueCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $startTime = microtime(true);
        
        $response = $this->actingAs($admin)
            ->get('/admin');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $response->assertOk();
        
        // Dashboard should load within reasonable time (< 1 second)
        $this->assertLessThan(1.0, $executionTime);
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        $this->assertIsArray($cards);
    }

    public function test_dashboard_cards_nova_compatibility(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            NovaCompatibleCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        $this->assertCount(1, $cards);
        
        $card = $cards[0];
        
        // Verify Nova-specific structure
        $this->assertEquals('NovaCompatibleCard', $card['component']);
        $this->assertEquals('Nova Compatible Card', $card['title']);
        $this->assertEquals('1/2', $card['size']); // Nova size format
        
        // Verify Nova-style data structure
        $this->assertArrayHasKey('meta', $card['data']);
        $this->assertArrayHasKey('value', $card['data']);
        $this->assertArrayHasKey('format', $card['data']);
    }
}

// Test Card Implementations

class UserStatsCard extends Card
{
    public function component(): string
    {
        return 'UserStatsCard';
    }

    public function data(Request $request): array
    {
        return [
            'total_users' => 1250,
            'active_users' => 890,
            'new_users_today' => 45
        ];
    }

    public function title(): string
    {
        return 'User Statistics';
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

class RevenueCard extends Card
{
    public function component(): string
    {
        return 'RevenueCard';
    }

    public function data(Request $request): array
    {
        return [
            'total_revenue' => 125000.50,
            'monthly_revenue' => 15420.75,
            'growth_rate' => 12.5
        ];
    }

    public function title(): string
    {
        return 'Revenue Overview';
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

class ActivityCard extends Card
{
    public function component(): string
    {
        return 'ActivityCard';
    }

    public function data(Request $request): array
    {
        return [
            'recent_activities' => [
                ['action' => 'user_created', 'count' => 5],
                ['action' => 'order_placed', 'count' => 12]
            ]
        ];
    }

    public function title(): string
    {
        return 'Recent Activity';
    }

    public function size(): string
    {
        return 'sm';
    }

    public function authorize(Request $request): bool
    {
        return true;
    }
}

class PublicCard extends Card
{
    public function component(): string
    {
        return 'PublicCard';
    }

    public function data(Request $request): array
    {
        return ['public' => true];
    }

    public function title(): string
    {
        return 'Public Card';
    }

    public function size(): string
    {
        return 'sm';
    }

    public function authorize(Request $request): bool
    {
        return true; // Always authorized
    }
}

class AdminOnlyCard extends Card
{
    public function component(): string
    {
        return 'AdminOnlyCard';
    }

    public function data(Request $request): array
    {
        return ['admin_only' => true];
    }

    public function title(): string
    {
        return 'Admin Only Card';
    }

    public function size(): string
    {
        return 'md';
    }

    public function authorize(Request $request): bool
    {
        return $request->user()?->is_admin ?? false;
    }
}

class SuperAdminCard extends Card
{
    public function component(): string
    {
        return 'SuperAdminCard';
    }

    public function data(Request $request): array
    {
        return ['super_admin' => true];
    }

    public function title(): string
    {
        return 'Super Admin Card';
    }

    public function size(): string
    {
        return 'lg';
    }

    public function authorize(Request $request): bool
    {
        return false; // Simulate super admin check that fails
    }
}

class ErrorCard extends Card
{
    public function component(): string
    {
        return 'ErrorCard';
    }

    public function data(Request $request): array
    {
        throw new \Exception('Test error in card data');
    }

    public function title(): string
    {
        return 'Error Card';
    }

    public function size(): string
    {
        return 'sm';
    }

    public function authorize(Request $request): bool
    {
        return true;
    }
}

class NovaCompatibleCard extends Card
{
    public function component(): string
    {
        return 'NovaCompatibleCard';
    }

    public function data(Request $request): array
    {
        return [
            'meta' => [
                'refreshable' => true,
                'icon' => 'chart-bar'
            ],
            'value' => 1250,
            'format' => 'number'
        ];
    }

    public function title(): string
    {
        return 'Nova Compatible Card';
    }

    public function size(): string
    {
        return '1/2'; // Nova-style size
    }

    public function authorize(Request $request): bool
    {
        return true;
    }
}
