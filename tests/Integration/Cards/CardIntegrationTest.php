<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Integration tests for the Card base class within Laravel context.
 */
class CardIntegrationTest extends TestCase
{
    public function test_card_integrates_with_laravel_request(): void
    {
        $card = new IntegrationTestCard;
        $request = Request::create('/test', 'GET');

        // Test that the card can work with Laravel Request objects
        $this->assertTrue($card->authorize($request));

        // Test with authorization callback
        $card->canSee(function (Request $req) {
            return $req->path() === 'test';
        });

        $this->assertTrue($card->authorize($request));
    }

    public function test_card_authorization_with_user_context(): void
    {
        $card = new IntegrationTestCard;
        $request = Request::create('/admin/dashboard', 'GET');

        // Test authorization with user-based logic
        $card->canSee(function (Request $request) {
            // Simulate user authorization logic
            return $request->path() === 'admin/dashboard';
        });

        $this->assertTrue($card->authorize($request));

        // Test with different path
        $request = Request::create('/public/page', 'GET');
        $this->assertFalse($card->authorize($request));
    }

    public function test_card_meta_data_serialization(): void
    {
        $card = new IntegrationTestCard;
        $card->withMeta([
            'title' => 'Dashboard Card',
            'description' => 'Shows dashboard statistics',
            'refreshInterval' => 30,
            'permissions' => ['view_dashboard'],
        ]);

        $serialized = $card->jsonSerialize();

        $this->assertArrayHasKey('meta', $serialized);
        $this->assertEquals('Dashboard Card', $serialized['meta']['title']);
        $this->assertEquals('Shows dashboard statistics', $serialized['meta']['description']);
        $this->assertEquals(30, $serialized['meta']['refreshInterval']);
        $this->assertEquals(['view_dashboard'], $serialized['meta']['permissions']);
    }

    public function test_card_works_with_laravel_collections(): void
    {
        $cards = collect([
            new IntegrationTestCard,
            (new IntegrationTestCard)->withName('Second Card'),
            (new IntegrationTestCard)->withName('Third Card'),
        ]);

        $names = $cards->map(fn (Card $card) => $card->name())->toArray();

        $this->assertEquals([
            'Integration Test Card',
            'Second Card',
            'Third Card',
        ], $names);
    }

    public function test_card_authorization_with_middleware_like_logic(): void
    {
        $card = new IntegrationTestCard;
        $request = Request::create('/admin/cards', 'GET');

        // Simulate middleware-like authorization
        $card->canSee(function (Request $request) {
            // Check if request is for admin area
            if (! str_starts_with($request->path(), 'admin/')) {
                return false;
            }

            // Check if user has permission (simulated)
            $userPermissions = ['view_cards', 'manage_dashboard'];

            return in_array('view_cards', $userPermissions);
        });

        $this->assertTrue($card->authorize($request));

        // Test with non-admin request
        $publicRequest = Request::create('/public/page', 'GET');
        $this->assertFalse($card->authorize($publicRequest));
    }

    public function test_card_factory_pattern_integration(): void
    {
        $card = IntegrationTestCard::make()
            ->withName('Factory Card')
            ->withComponent('FactoryComponent')
            ->withMeta(['factory' => true])
            ->canSee(fn () => true);

        $this->assertEquals('Factory Card', $card->name());
        $this->assertEquals('FactoryComponent', $card->component());
        $this->assertEquals(['factory' => true], $card->meta());
        $this->assertNotNull($card->canSeeCallback);
    }

    public function test_card_inheritance_and_polymorphism(): void
    {
        $cards = [
            new IntegrationTestCard,
            new SpecializedTestCard,
        ];

        foreach ($cards as $card) {
            $this->assertInstanceOf(Card::class, $card);
            $this->assertIsString($card->name());
            $this->assertIsString($card->component());
            $this->assertIsString($card->uriKey());
            $this->assertIsArray($card->meta());
        }
    }

    public function test_card_with_complex_meta_data(): void
    {
        $card = new IntegrationTestCard;
        $complexMeta = [
            'config' => [
                'chart' => [
                    'type' => 'line',
                    'data' => [
                        'labels' => ['Jan', 'Feb', 'Mar'],
                        'datasets' => [
                            [
                                'label' => 'Sales',
                                'data' => [100, 200, 150],
                            ],
                        ],
                    ],
                ],
            ],
            'permissions' => ['read', 'write'],
            'refreshable' => true,
        ];

        $card->withMeta($complexMeta);
        $serialized = $card->jsonSerialize();

        $this->assertEquals($complexMeta, $serialized['meta']);
        $this->assertEquals('line', $serialized['meta']['config']['chart']['type']);
        $this->assertEquals(['read', 'write'], $serialized['meta']['permissions']);
        $this->assertTrue($serialized['meta']['refreshable']);
    }

    public function test_card_request_context_preservation(): void
    {
        $card = new IntegrationTestCard;
        $request = Request::create('/test', 'GET', ['param' => 'value']);
        $request->headers->set('X-Custom-Header', 'test-value');

        $capturedRequest = null;
        $card->canSee(function (Request $req) use (&$capturedRequest) {
            $capturedRequest = $req;

            return true;
        });

        $card->authorize($request);

        $this->assertSame($request, $capturedRequest);
        $this->assertEquals('value', $capturedRequest->get('param'));
        $this->assertEquals('test-value', $capturedRequest->header('X-Custom-Header'));
    }
}

/**
 * Test implementation of Card for integration testing.
 */
class IntegrationTestCard extends Card
{
    // Concrete implementation for integration testing
}

/**
 * Specialized test card for polymorphism testing.
 */
class SpecializedTestCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta(['specialized' => true]);
    }
}
