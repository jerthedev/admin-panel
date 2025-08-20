<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\e2e\Dashboard;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Comprehensive E2E test to verify 100% Nova alignment for Dashboard feature.
 * 
 * This test validates that all dashboard components, patterns, and behaviors
 * match Laravel Nova standards exactly.
 */
class NovaAlignmentE2ETest extends TestCase
{
    public function test_dashboard_uses_nova_card_terminology_exclusively(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            NovaAlignmentTestCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $pageData = $response->getOriginalContent()->getData()['page'];
        $props = $pageData['props'];
        
        // Verify Nova terminology is used
        $this->assertArrayHasKey('cards', $props);
        $this->assertArrayNotHasKey('widgets', $props);
        $this->assertArrayNotHasKey('components', $props);
        $this->assertArrayNotHasKey('modules', $props);
        
        // Verify response structure matches Nova exactly
        $this->assertEquals('Dashboard', $pageData['component']);
        $this->assertIsArray($props['cards']);
    }

    public function test_card_structure_matches_nova_format_exactly(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            NovaAlignmentTestCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        $this->assertCount(1, $cards);
        
        $card = $cards[0];
        
        // Verify exact Nova card structure
        $this->assertArrayHasKey('component', $card);
        $this->assertArrayHasKey('data', $card);
        $this->assertArrayHasKey('title', $card);
        $this->assertArrayHasKey('size', $card);
        
        // Verify no extra keys that aren't in Nova
        $expectedKeys = ['component', 'data', 'title', 'size'];
        $actualKeys = array_keys($card);
        sort($expectedKeys);
        sort($actualKeys);
        $this->assertEquals($expectedKeys, $actualKeys);
        
        // Verify data types match Nova
        $this->assertIsString($card['component']);
        $this->assertIsArray($card['data']);
        $this->assertIsString($card['title']);
        $this->assertIsString($card['size']);
    }

    public function test_card_authorization_pattern_matches_nova(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            AuthorizedNovaCard::class,
            UnauthorizedNovaCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        
        // Only authorized card should be present
        $this->assertCount(1, $cards);
        $this->assertEquals('AuthorizedNovaCard', $cards[0]['component']);
    }

    public function test_card_data_method_receives_request_like_nova(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            RequestAwareNovaCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        $this->assertCount(1, $cards);
        
        $cardData = $cards[0]['data'];
        
        // Verify request data was passed correctly
        $this->assertArrayHasKey('user_id', $cardData);
        $this->assertEquals($admin->id, $cardData['user_id']);
        $this->assertArrayHasKey('request_method', $cardData);
        $this->assertEquals('GET', $cardData['request_method']);
    }

    public function test_card_sizes_match_nova_conventions(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            SmallNovaCard::class,
            MediumNovaCard::class,
            LargeNovaCard::class,
            FullWidthNovaCard::class,
            FractionNovaCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        $this->assertCount(5, $cards);
        
        $sizes = array_column($cards, 'size');
        
        // Verify Nova-compatible sizes
        $this->assertContains('sm', $sizes);
        $this->assertContains('md', $sizes);
        $this->assertContains('lg', $sizes);
        $this->assertContains('full', $sizes);
        $this->assertContains('1/3', $sizes); // Nova fraction format
    }

    public function test_error_handling_matches_nova_behavior(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            WorkingNovaCard::class,
            ErrorNovaCard::class,
            AnotherWorkingNovaCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        // Dashboard should still load despite card error (Nova behavior)
        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        
        // Only working cards should be present
        $this->assertCount(2, $cards);
        
        $cardComponents = array_column($cards, 'component');
        $this->assertContains('WorkingNovaCard', $cardComponents);
        $this->assertContains('AnotherWorkingNovaCard', $cardComponents);
        $this->assertNotContains('ErrorNovaCard', $cardComponents);
    }

    public function test_dashboard_response_structure_matches_nova_exactly(): void
    {
        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $pageData = $response->getOriginalContent()->getData()['page'];
        
        // Verify Inertia structure matches Nova
        $this->assertArrayHasKey('component', $pageData);
        $this->assertArrayHasKey('props', $pageData);
        $this->assertEquals('Dashboard', $pageData['component']);
        
        $props = $pageData['props'];
        
        // Verify all expected Nova dashboard sections
        $this->assertArrayHasKey('metrics', $props);
        $this->assertArrayHasKey('cards', $props);
        $this->assertArrayHasKey('recentActivity', $props);
        $this->assertArrayHasKey('quickActions', $props);
        $this->assertArrayHasKey('systemInfo', $props);
        
        // Verify data types match Nova
        $this->assertIsArray($props['metrics']);
        $this->assertIsArray($props['cards']);
        $this->assertIsArray($props['recentActivity']);
        $this->assertIsArray($props['quickActions']);
        $this->assertIsArray($props['systemInfo']);
    }

    public function test_card_meta_data_structure_supports_nova_patterns(): void
    {
        config(['admin-panel.dashboard.default_cards' => [
            MetaDataNovaCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $cards = $response->getOriginalContent()->getData()['page']['props']['cards'];
        $this->assertCount(1, $cards);
        
        $cardData = $cards[0]['data'];
        
        // Verify Nova-style meta data structure
        $this->assertArrayHasKey('meta', $cardData);
        $this->assertArrayHasKey('value', $cardData);
        $this->assertArrayHasKey('format', $cardData);
        $this->assertArrayHasKey('refreshable', $cardData['meta']);
        $this->assertArrayHasKey('icon', $cardData['meta']);
        
        // Verify data types
        $this->assertIsBool($cardData['meta']['refreshable']);
        $this->assertIsString($cardData['meta']['icon']);
        $this->assertIsNumeric($cardData['value']);
        $this->assertIsString($cardData['format']);
    }

    public function test_complete_nova_workflow_integration(): void
    {
        // Configure multiple cards like Nova dashboard
        config(['admin-panel.dashboard.default_cards' => [
            NovaAlignmentTestCard::class,
            AuthorizedNovaCard::class,
            RequestAwareNovaCard::class,
            MetaDataNovaCard::class
        ]]);

        $admin = $this->createAdminUser();
        
        // Test complete workflow
        $response = $this->actingAs($admin)
            ->get('/admin');

        $response->assertOk();
        
        $pageData = $response->getOriginalContent()->getData()['page'];
        $cards = $pageData['props']['cards'];
        
        // Verify all cards loaded correctly
        $this->assertCount(4, $cards);
        
        // Verify each card follows Nova patterns
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
        
        // Verify dashboard structure is Nova-compatible
        $this->assertEquals('Dashboard', $pageData['component']);
        $this->assertArrayHasKey('metrics', $pageData['props']);
        $this->assertArrayHasKey('cards', $pageData['props']);
    }
}

// Test Card Implementations for Nova Alignment Testing

class NovaAlignmentTestCard extends Card
{
    public function component(): string
    {
        return 'NovaAlignmentTestCard';
    }

    public function data(Request $request): array
    {
        return ['test' => 'nova_alignment'];
    }

    public function title(): string
    {
        return 'Nova Alignment Test';
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

class AuthorizedNovaCard extends Card
{
    public function component(): string
    {
        return 'AuthorizedNovaCard';
    }

    public function data(Request $request): array
    {
        return ['authorized' => true];
    }

    public function title(): string
    {
        return 'Authorized Card';
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

class UnauthorizedNovaCard extends Card
{
    public function component(): string
    {
        return 'UnauthorizedNovaCard';
    }

    public function data(Request $request): array
    {
        return ['unauthorized' => true];
    }

    public function title(): string
    {
        return 'Unauthorized Card';
    }

    public function size(): string
    {
        return 'md';
    }

    public function authorize(Request $request): bool
    {
        return false;
    }
}

class RequestAwareNovaCard extends Card
{
    public function component(): string
    {
        return 'RequestAwareNovaCard';
    }

    public function data(Request $request): array
    {
        return [
            'user_id' => $request->user()?->id,
            'request_method' => $request->method(),
            'request_path' => $request->path()
        ];
    }

    public function title(): string
    {
        return 'Request Aware Card';
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

class SmallNovaCard extends Card
{
    public function component(): string { return 'SmallNovaCard'; }
    public function data(Request $request): array { return []; }
    public function title(): string { return 'Small Card'; }
    public function size(): string { return 'sm'; }
    public function authorize(Request $request): bool { return true; }
}

class MediumNovaCard extends Card
{
    public function component(): string { return 'MediumNovaCard'; }
    public function data(Request $request): array { return []; }
    public function title(): string { return 'Medium Card'; }
    public function size(): string { return 'md'; }
    public function authorize(Request $request): bool { return true; }
}

class LargeNovaCard extends Card
{
    public function component(): string { return 'LargeNovaCard'; }
    public function data(Request $request): array { return []; }
    public function title(): string { return 'Large Card'; }
    public function size(): string { return 'lg'; }
    public function authorize(Request $request): bool { return true; }
}

class FullWidthNovaCard extends Card
{
    public function component(): string { return 'FullWidthNovaCard'; }
    public function data(Request $request): array { return []; }
    public function title(): string { return 'Full Width Card'; }
    public function size(): string { return 'full'; }
    public function authorize(Request $request): bool { return true; }
}

class FractionNovaCard extends Card
{
    public function component(): string { return 'FractionNovaCard'; }
    public function data(Request $request): array { return []; }
    public function title(): string { return 'Fraction Card'; }
    public function size(): string { return '1/3'; }
    public function authorize(Request $request): bool { return true; }
}

class WorkingNovaCard extends Card
{
    public function component(): string { return 'WorkingNovaCard'; }
    public function data(Request $request): array { return ['working' => true]; }
    public function title(): string { return 'Working Card'; }
    public function size(): string { return 'md'; }
    public function authorize(Request $request): bool { return true; }
}

class ErrorNovaCard extends Card
{
    public function component(): string { return 'ErrorNovaCard'; }
    public function data(Request $request): array { throw new \Exception('Test error'); }
    public function title(): string { return 'Error Card'; }
    public function size(): string { return 'sm'; }
    public function authorize(Request $request): bool { return true; }
}

class AnotherWorkingNovaCard extends Card
{
    public function component(): string { return 'AnotherWorkingNovaCard'; }
    public function data(Request $request): array { return ['another' => 'working']; }
    public function title(): string { return 'Another Working Card'; }
    public function size(): string { return 'lg'; }
    public function authorize(Request $request): bool { return true; }
}

class MetaDataNovaCard extends Card
{
    public function component(): string { return 'MetaDataNovaCard'; }
    
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
    
    public function title(): string { return 'Meta Data Card'; }
    public function size(): string { return '1/2'; }
    public function authorize(Request $request): bool { return true; }
}
