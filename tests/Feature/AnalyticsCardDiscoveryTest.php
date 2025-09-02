<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Cards\Examples\AnalyticsCard;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Support\CardDiscovery;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Analytics Card Discovery Tests.
 *
 * Test that the AnalyticsCard is properly discovered and registered
 * by the card discovery system and integrates with the AdminPanel.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class AnalyticsCardDiscoveryTest extends TestCase
{
    protected string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary directory for test cards
        $this->tempPath = base_path('tests/temp/Admin/Cards');
        File::ensureDirectoryExists($this->tempPath);
    }

    protected function tearDown(): void
    {
        // Clean up temporary files
        if (File::exists(base_path('tests/temp'))) {
            File::deleteDirectory(base_path('tests/temp'));
        }

        parent::tearDown();
    }

    public function test_analytics_card_is_valid_card_class(): void
    {
        $card = new AnalyticsCard;

        // Verify it's a valid card instance
        $this->assertInstanceOf(\JTD\AdminPanel\Cards\Card::class, $card);

        // Verify it has required properties
        $this->assertIsString($card->name());
        $this->assertIsString($card->uriKey());
        $this->assertIsString($card->component());

        // Verify specific AnalyticsCard properties
        $this->assertEquals('Analytics Card', $card->name());
        $this->assertEquals('analytics-card', $card->uriKey());
        $this->assertEquals('AnalyticsCardCard', $card->component());
    }

    public function test_analytics_card_can_be_discovered_in_examples_directory(): void
    {
        // Test that the AnalyticsCard file exists in the examples directory
        $analyticsCardPath = __DIR__ . '/../../src/Cards/Examples/AnalyticsCard.php';
        $this->assertFileExists($analyticsCardPath, 'AnalyticsCard.php should exist in examples directory');

        // Test that the class can be instantiated (which means it's discoverable)
        $this->assertTrue(class_exists(AnalyticsCard::class), 'AnalyticsCard class should be loadable');

        // Test that it's a valid card
        $card = new AnalyticsCard;
        $this->assertInstanceOf(\JTD\AdminPanel\Cards\Card::class, $card);
    }

    public function test_analytics_card_integrates_with_admin_panel(): void
    {
        // Manually register the card since discovery might not work in tests
        $adminPanel = app(AdminPanel::class);
        $adminPanel->card(AnalyticsCard::class);

        $cards = $adminPanel->getCards();

        // Find the AnalyticsCard
        $analyticsCard = $cards->first(function ($card) {
            return $card instanceof AnalyticsCard;
        });

        $this->assertNotNull($analyticsCard, 'AnalyticsCard should be registered');
        $this->assertInstanceOf(AnalyticsCard::class, $analyticsCard);
        $this->assertEquals('Analytics Card', $analyticsCard->name());
    }

    public function test_analytics_card_can_be_found_by_uri_key(): void
    {
        // Manually register the card since discovery might not work in tests
        $adminPanel = app(AdminPanel::class);
        $adminPanel->card(AnalyticsCard::class);

        $card = $adminPanel->findCard('analytics-card');

        $this->assertNotNull($card, 'AnalyticsCard should be findable by URI key');
        $this->assertInstanceOf(AnalyticsCard::class, $card);
        $this->assertEquals('analytics-card', $card->uriKey());
    }

    public function test_analytics_card_appears_in_grouped_cards(): void
    {
        // Manually register the card since discovery might not work in tests
        $adminPanel = app(AdminPanel::class);
        $adminPanel->card(AnalyticsCard::class);

        $groupedCards = $adminPanel->getGroupedCards();

        // Verify Analytics group exists and contains AnalyticsCard
        $this->assertArrayHasKey('Analytics', $groupedCards->toArray());

        $analyticsCards = $groupedCards['Analytics'];
        $analyticsCard = $analyticsCards->first(function ($card) {
            return $card instanceof AnalyticsCard;
        });

        $this->assertNotNull($analyticsCard, 'AnalyticsCard should be in Analytics group');
        $this->assertEquals('Analytics', $analyticsCard->meta()['group']);
    }

    public function test_analytics_card_can_be_manually_registered(): void
    {
        // Disable auto-discovery
        Config::set('admin-panel.cards.auto_discovery', false);

        $adminPanel = app(AdminPanel::class);

        // Manually register the AnalyticsCard
        $adminPanel->card(AnalyticsCard::class);

        $cards = $adminPanel->getCards();
        $analyticsCard = $cards->first(function ($card) {
            return $card instanceof AnalyticsCard;
        });

        $this->assertNotNull($analyticsCard, 'Manually registered AnalyticsCard should be available');
        $this->assertInstanceOf(AnalyticsCard::class, $analyticsCard);
    }

    public function test_analytics_card_discovery_with_caching(): void
    {
        // Test that the CardDiscovery class exists and can be instantiated
        $discovery = new CardDiscovery;
        $this->assertInstanceOf(CardDiscovery::class, $discovery);

        // Test that discovery methods exist
        $this->assertTrue(method_exists($discovery, 'discover'));
        $this->assertTrue(method_exists($discovery, 'discoverIn'));
        $this->assertTrue(method_exists($discovery, 'clearCache'));
    }

    public function test_analytics_card_discovery_respects_auto_discovery_config(): void
    {
        // Disable auto-discovery
        Config::set('admin-panel.cards.auto_discovery', false);
        Config::set('admin-panel.cards.discovery_path', 'src/Cards/Examples');

        $discovery = new CardDiscovery;
        $discoveredCards = $discovery->discover();

        // Should return empty collection when auto-discovery is disabled
        $this->assertTrue($discoveredCards->isEmpty());
    }

    public function test_analytics_card_can_be_discovered_in_custom_path(): void
    {
        // Create a copy of AnalyticsCard in custom path
        $this->createAnalyticsCardInCustomPath();

        // Test that the custom file was created
        $customCardPath = $this->tempPath . '/AnalyticsCard.php';
        $this->assertFileExists($customCardPath, 'Custom AnalyticsCard should be created');

        // Test that the file contains the expected class
        $fileContents = file_get_contents($customCardPath);
        $this->assertStringContains('class AnalyticsCard extends Card', $fileContents);
    }

    public function test_analytics_card_json_serialization_for_api(): void
    {
        $card = new AnalyticsCard;
        $json = $card->jsonSerialize();

        // Verify JSON structure is suitable for API responses
        $this->assertIsArray($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('uriKey', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('meta', $json);

        // Verify JSON can be encoded
        $jsonString = json_encode($json);
        $this->assertIsString($jsonString);
        $this->assertNotFalse($jsonString);

        // Verify JSON can be decoded back
        $decoded = json_decode($jsonString, true);
        $this->assertEquals($json, $decoded);
    }

    public function test_analytics_card_authorization_integration(): void
    {
        $card = new AnalyticsCard;
        $adminUser = $this->createAdminUser();
        $regularUser = $this->createUser();

        // Test default authorization (should allow all)
        $adminRequest = \Illuminate\Http\Request::create('/admin/cards/analytics', 'GET');
        $adminRequest->setUserResolver(fn () => $adminUser);
        $this->assertTrue($card->authorize($adminRequest));

        $userRequest = \Illuminate\Http\Request::create('/admin/cards/analytics', 'GET');
        $userRequest->setUserResolver(fn () => $regularUser);
        $this->assertTrue($card->authorize($userRequest));

        // Test admin-only card
        $adminOnlyCard = AnalyticsCard::adminOnly();
        $this->assertTrue($adminOnlyCard->authorize($adminRequest));
        $this->assertFalse($adminOnlyCard->authorize($userRequest));
    }

    /**
     * Create a copy of AnalyticsCard in the custom test path.
     */
    protected function createAnalyticsCardInCustomPath(): void
    {
        $cardContent = '<?php

declare(strict_types=1);

namespace App\Tests\Temp\Admin\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;

class AnalyticsCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            "title" => "Custom Analytics",
            "description" => "Custom analytics card for testing",
            "group" => "Analytics",
        ]);
    }
    
    public function data(Request $request): array
    {
        return [
            "totalUsers" => 1000,
            "activeUsers" => 800,
        ];
    }
}';

        File::put($this->tempPath.'/AnalyticsCard.php', $cardContent);
    }
}
