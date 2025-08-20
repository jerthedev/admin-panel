<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Support\CardDiscovery;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Card Discovery Integration Tests.
 *
 * Tests the complete card discovery and registration workflow
 * including file system integration and Laravel context.
 */
class CardDiscoveryIntegrationTest extends TestCase
{
    protected Filesystem $files;
    protected string $testCardsPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->files = new Filesystem();
        $this->testCardsPath = base_path('tests/temp/Admin/Cards');
        
        // Create test directory
        if (!$this->files->exists($this->testCardsPath)) {
            $this->files->makeDirectory($this->testCardsPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if ($this->files->exists(dirname($this->testCardsPath, 2))) {
            $this->files->deleteDirectory(dirname($this->testCardsPath, 2));
        }
        
        parent::tearDown();
    }

    public function test_card_discovery_finds_real_card_files(): void
    {
        // Create a real card file
        $this->createRealCardFile('UserStatsCard', 'UserStats', 'Analytics');
        $this->createRealCardFile('RevenueCard', 'Revenue', 'Finance');
        
        // Configure discovery path
        Config::set('admin-panel.cards.discovery_path', 'tests/temp/Admin/Cards');
        Config::set('admin-panel.cards.auto_discovery', true);
        
        $discovery = new CardDiscovery();
        $cards = $discovery->discover();
        
        $this->assertCount(2, $cards);
        $this->assertContains('App\\Tests\\Temp\\Admin\\Cards\\UserStatsCard', $cards->toArray());
        $this->assertContains('App\\Tests\\Temp\\Admin\\Cards\\RevenueCard', $cards->toArray());
    }

    public function test_card_discovery_instantiates_cards_correctly(): void
    {
        $this->createRealCardFile('InstantiableCard', 'Instantiable', 'Test');
        
        Config::set('admin-panel.cards.discovery_path', 'tests/temp/Admin/Cards');
        Config::set('admin-panel.cards.auto_discovery', true);
        
        $discovery = new CardDiscovery();
        $cardInstances = $discovery->getCardInstances();
        
        $this->assertCount(1, $cardInstances);
        
        $card = $cardInstances->first();
        $this->assertEquals('InstantiableCard', $card->name());
        $this->assertEquals('instantiable-card', $card->uriKey());
        $this->assertEquals('Test', $card->meta()['group']);
    }

    public function test_admin_panel_integrates_with_card_discovery(): void
    {
        $this->createRealCardFile('IntegratedCard', 'Integrated', 'Integration');
        
        Config::set('admin-panel.cards.discovery_path', 'tests/temp/Admin/Cards');
        Config::set('admin-panel.cards.auto_discovery', true);
        
        $adminPanel = app(AdminPanel::class);
        $cards = $adminPanel->getCards();
        
        // Should find our test card
        $integratedCard = $cards->first(function ($card) {
            return $card->name() === 'IntegratedCard';
        });
        
        $this->assertNotNull($integratedCard);
        $this->assertEquals('Integration', $integratedCard->meta()['group']);
    }

    public function test_card_authorization_works_in_integration(): void
    {
        $this->createRealCardFile('AuthorizedCard', 'Authorized', 'Auth', true);
        $this->createRealCardFile('UnauthorizedCard', 'Unauthorized', 'Auth', false);
        
        Config::set('admin-panel.cards.discovery_path', 'tests/temp/Admin/Cards');
        Config::set('admin-panel.cards.auto_discovery', true);
        
        $adminPanel = app(AdminPanel::class);
        $request = new Request();
        
        $authorizedCards = $adminPanel->getAuthorizedCards($request);
        
        // Should only contain the authorized card
        $authorizedNames = $authorizedCards->map(function ($card) {
            return $card->name();
        })->toArray();
        
        $this->assertContains('AuthorizedCard', $authorizedNames);
        $this->assertNotContains('UnauthorizedCard', $authorizedNames);
    }

    public function test_card_grouping_works_in_integration(): void
    {
        $this->createRealCardFile('AnalyticsCard1', 'Analytics1', 'Analytics');
        $this->createRealCardFile('AnalyticsCard2', 'Analytics2', 'Analytics');
        $this->createRealCardFile('FinanceCard', 'Finance', 'Finance');
        
        Config::set('admin-panel.cards.discovery_path', 'tests/temp/Admin/Cards');
        Config::set('admin-panel.cards.auto_discovery', true);
        
        $adminPanel = app(AdminPanel::class);
        $groupedCards = $adminPanel->getGroupedCards();
        
        $this->assertArrayHasKey('Analytics', $groupedCards->toArray());
        $this->assertArrayHasKey('Finance', $groupedCards->toArray());
        
        $analyticsCards = $groupedCards['Analytics'];
        $this->assertCount(2, $analyticsCards);
        
        $financeCards = $groupedCards['Finance'];
        $this->assertCount(1, $financeCards);
    }

    public function test_card_finding_by_uri_key_works(): void
    {
        $this->createRealCardFile('FindableCard', 'Findable', 'Test');
        
        Config::set('admin-panel.cards.discovery_path', 'tests/temp/Admin/Cards');
        Config::set('admin-panel.cards.auto_discovery', true);
        
        $adminPanel = app(AdminPanel::class);
        $card = $adminPanel->findCard('findable-card');
        
        $this->assertNotNull($card);
        $this->assertEquals('FindableCard', $card->name());
        $this->assertEquals('findable-card', $card->uriKey());
    }

    public function test_nested_card_discovery_works(): void
    {
        // Create nested directory
        $nestedPath = $this->testCardsPath . '/Analytics';
        $this->files->makeDirectory($nestedPath, 0755, true);
        
        $this->createRealCardFile('NestedCard', 'Nested', 'Analytics', true, $nestedPath);
        
        Config::set('admin-panel.cards.discovery_path', 'tests/temp/Admin/Cards');
        Config::set('admin-panel.cards.auto_discovery', true);
        
        $discovery = new CardDiscovery();
        $cards = $discovery->discover();
        
        $this->assertCount(1, $cards);
        $this->assertContains('App\\Tests\\Temp\\Admin\\Cards\\Analytics\\NestedCard', $cards->toArray());
    }

    public function test_card_cache_integration_works(): void
    {
        Config::set('admin-panel.performance.cache_cards', true);
        Config::set('admin-panel.performance.cache_ttl', 3600);
        Config::set('admin-panel.cards.discovery_path', 'tests/temp/Admin/Cards');
        Config::set('admin-panel.cards.auto_discovery', true);
        
        $this->createRealCardFile('CachedCard', 'Cached', 'Cache');
        
        $discovery = new CardDiscovery();
        
        // First call should cache
        $cards1 = $discovery->discover();
        $this->assertCount(1, $cards1);
        
        // Remove the file
        $this->files->delete($this->testCardsPath . '/CachedCard.php');
        
        // Second call should return cached result
        $cards2 = $discovery->discover();
        $this->assertEquals($cards1, $cards2);
        
        // Clear cache
        $discovery->clearCache();
        
        // Third call should not find the file
        $cards3 = $discovery->discover();
        $this->assertCount(0, $cards3);
    }

    protected function createRealCardFile(string $className, string $cardName, string $group, bool $authorized = true, ?string $path = null): void
    {
        $path = $path ?? $this->testCardsPath;
        $filePath = $path . '/' . $className . '.php';
        
        // Determine namespace based on path
        $relativePath = str_replace(base_path() . '/', '', $path);
        $namespace = 'App\\' . str_replace('/', '\\', ucfirst($relativePath));
        
        $authMethod = $authorized ? 'return true;' : 'return false;';
        
        $content = <<<PHP
<?php

namespace {$namespace};

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;

class {$className} extends Card
{
    public function __construct()
    {
        parent::__construct();
        \$this->withMeta([
            'title' => '{$cardName}',
            'group' => '{$group}',
            'icon' => 'chart-bar',
        ]);
    }

    public function authorize(Request \$request): bool
    {
        {$authMethod}
    }

    protected function getData(): array
    {
        return [
            'value' => 42,
            'label' => '{$cardName} Value',
            'timestamp' => now()->toISOString(),
        ];
    }
}
PHP;
        
        $this->files->put($filePath, $content);
    }
}
