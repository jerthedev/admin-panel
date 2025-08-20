<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Tests\TestCase;

/**
 * End-to-end tests for the Card base class simulating real-world usage scenarios.
 */
class CardE2ETest extends TestCase
{
    public function test_complete_card_lifecycle(): void
    {
        // Create a card as it would be used in a real application
        $card = DashboardStatsCard::make()
            ->withName('Dashboard Statistics')
            ->withMeta([
                'title' => 'Key Metrics',
                'refreshInterval' => 60,
                'chartType' => 'bar',
                'data' => [
                    'users' => 1250,
                    'orders' => 89,
                    'revenue' => 15420.50,
                ],
            ])
            ->canSee(function (Request $request) {
                // Simulate real authorization logic
                return $request->user()?->hasPermission('view_dashboard') ?? false;
            });

        // Test card properties
        $this->assertEquals('Dashboard Statistics', $card->name());
        $this->assertEquals('dashboard-stats-card', $card->uriKey());
        $this->assertEquals('DashboardStatsCardCard', $card->component());

        // Test meta data
        $meta = $card->meta();
        $this->assertEquals('Key Metrics', $meta['title']);
        $this->assertEquals(60, $meta['refreshInterval']);
        $this->assertEquals('bar', $meta['chartType']);
        $this->assertEquals(1250, $meta['data']['users']);

        // Test serialization for API response
        $serialized = $card->jsonSerialize();
        $this->assertArrayHasKey('name', $serialized);
        $this->assertArrayHasKey('component', $serialized);
        $this->assertArrayHasKey('uriKey', $serialized);
        $this->assertArrayHasKey('meta', $serialized);
    }

    public function test_card_collection_management(): void
    {
        // Simulate a dashboard with multiple cards
        $cards = collect([
            UserStatsCard::make()->withMeta(['type' => 'users']),
            OrderStatsCard::make()->withMeta(['type' => 'orders']),
            RevenueStatsCard::make()->withMeta(['type' => 'revenue']),
        ]);

        // Filter cards based on authorization
        $request = Request::create('/admin/dashboard', 'GET');
        $authorizedCards = $cards->filter(fn (Card $card) => $card->authorize($request));

        $this->assertCount(3, $authorizedCards);

        // Test card serialization for API
        $serializedCards = $authorizedCards->map(fn (Card $card) => $card->jsonSerialize());

        $this->assertCount(3, $serializedCards);
        foreach ($serializedCards as $serialized) {
            $this->assertArrayHasKey('name', $serialized);
            $this->assertArrayHasKey('component', $serialized);
            $this->assertArrayHasKey('uriKey', $serialized);
            $this->assertArrayHasKey('meta', $serialized);
        }
    }

    public function test_card_with_conditional_authorization(): void
    {
        $card = AdminOnlyCard::make()
            ->canSee(function (Request $request) {
                // Simulate admin-only access
                $userRole = $request->header('X-User-Role', 'user');

                return $userRole === 'admin';
            });

        // Test with regular user
        $userRequest = Request::create('/dashboard', 'GET');
        $userRequest->headers->set('X-User-Role', 'user');
        $this->assertFalse($card->authorize($userRequest));

        // Test with admin user
        $adminRequest = Request::create('/dashboard', 'GET');
        $adminRequest->headers->set('X-User-Role', 'admin');
        $this->assertTrue($card->authorize($adminRequest));
    }

    public function test_card_meta_data_updates(): void
    {
        $card = DynamicCard::make();

        // Initial meta data
        $card->withMeta(['version' => '1.0', 'status' => 'active']);
        $this->assertEquals('1.0', $card->meta()['version']);
        $this->assertEquals('active', $card->meta()['status']);

        // Update meta data (simulating runtime updates)
        $card->withMeta(['version' => '1.1', 'lastUpdated' => '2024-01-15']);
        $meta = $card->meta();

        $this->assertEquals('1.1', $meta['version']); // Updated
        $this->assertEquals('active', $meta['status']); // Preserved
        $this->assertEquals('2024-01-15', $meta['lastUpdated']); // Added
    }

    public function test_card_inheritance_scenario(): void
    {
        // Test that specialized cards inherit base functionality
        $baseCard = new BaseTestCard;
        $specializedCard = new SpecializedE2ECard;

        // Both should have base functionality
        $this->assertInstanceOf(Card::class, $baseCard);
        $this->assertInstanceOf(Card::class, $specializedCard);

        // Test that specialized card can override behavior
        $this->assertEquals('Base Test Card', $baseCard->name());
        $this->assertEquals('Enhanced Specialized Card', $specializedCard->name());

        // Test that both can use fluent interface
        $baseCard->withMeta(['base' => true]);
        $specializedCard->withMeta(['specialized' => true]);

        $this->assertTrue($baseCard->meta()['base']);
        $this->assertTrue($specializedCard->meta()['specialized']);
    }

    public function test_card_error_handling(): void
    {
        $card = ErrorProneCard::make();

        // Test that card handles authorization errors gracefully
        $card->canSee(function (Request $request) {
            // Simulate an error in authorization logic
            if ($request->get('trigger_error')) {
                throw new \Exception('Authorization error');
            }

            return true;
        });

        // Normal request should work
        $normalRequest = Request::create('/test', 'GET');
        $this->assertTrue($card->authorize($normalRequest));

        // Error request should be handled
        $errorRequest = Request::create('/test', 'GET', ['trigger_error' => true]);
        $this->expectException(\Exception::class);
        $card->authorize($errorRequest);
    }

    public function test_card_performance_with_large_meta_data(): void
    {
        $card = PerformanceTestCard::make();

        // Add large meta data set
        $largeMeta = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeMeta["item_{$i}"] = [
                'id' => $i,
                'name' => "Item {$i}",
                'data' => array_fill(0, 100, "data_{$i}"),
            ];
        }

        $startTime = microtime(true);
        $card->withMeta($largeMeta);
        $endTime = microtime(true);

        // Should handle large meta data efficiently
        $this->assertLessThan(0.1, $endTime - $startTime); // Less than 100ms

        // Serialization should also be efficient
        $startTime = microtime(true);
        $serialized = $card->jsonSerialize();
        $endTime = microtime(true);

        $this->assertLessThan(0.1, $endTime - $startTime);
        $this->assertArrayHasKey('meta', $serialized);
        $this->assertCount(1000, $serialized['meta']);
    }
}

// Test card implementations for E2E testing

class DashboardStatsCard extends Card {}

class UserStatsCard extends Card {}

class OrderStatsCard extends Card {}

class RevenueStatsCard extends Card {}

class AdminOnlyCard extends Card {}

class DynamicCard extends Card {}

class BaseTestCard extends Card {}

class SpecializedE2ECard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withName('Enhanced Specialized Card');
    }
}

class ErrorProneCard extends Card {}

class PerformanceTestCard extends Card {}
