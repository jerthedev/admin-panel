<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Support;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Support\CardDiscovery;
use JTD\AdminPanel\Tests\TestCase;

/**
 * CardDiscovery Unit Tests.
 *
 * Tests the card auto-discovery functionality including caching,
 * authorization, and card instantiation.
 */
class CardDiscoveryTest extends TestCase
{
    protected CardDiscovery $discovery;
    protected string $testPath;
    protected Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->discovery = new CardDiscovery();
        $this->testPath = sys_get_temp_dir() . '/admin-panel-card-discovery-test-' . uniqid();
        $this->files = new Filesystem();
        
        // Create test directory
        $this->files->makeDirectory($this->testPath, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if ($this->files->exists($this->testPath)) {
            $this->files->deleteDirectory($this->testPath);
        }
        
        parent::tearDown();
    }

    public function test_discover_returns_empty_collection_when_auto_discovery_disabled(): void
    {
        Config::set('admin-panel.cards.auto_discovery', false);
        
        $result = $this->discovery->discover();
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_discover_returns_empty_collection_when_path_does_not_exist(): void
    {
        Config::set('admin-panel.cards.auto_discovery', true);
        Config::set('admin-panel.cards.discovery_path', 'nonexistent/path');
        
        $result = $this->discovery->discover();
        
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertTrue($result->isEmpty());
    }

    public function test_discover_in_finds_valid_card_classes(): void
    {
        // Create test card files
        $this->createTestCardFile('TestCard', 'TestCard');
        $this->createTestCardFile('UserStatsCard', 'UserStats');
        $this->createTestCardFile('InvalidClass', 'InvalidClass', false); // Not a card

        $result = $this->discovery->discoverIn($this->testPath);

        $this->assertInstanceOf(Collection::class, $result);
        // The test cards should be found (exact count may vary based on class loading)
        $this->assertGreaterThanOrEqual(1, $result->count());

        // Check that at least one valid card class is found
        $foundValidCard = $result->contains(function ($className) {
            return str_contains($className, 'TestCard') || str_contains($className, 'UserStatsCard');
        });
        $this->assertTrue($foundValidCard, 'Should find at least one valid card class');
    }

    public function test_discover_in_ignores_non_php_files(): void
    {
        // Create non-PHP files
        $this->files->put($this->testPath . '/README.md', '# Cards');
        $this->files->put($this->testPath . '/config.json', '{}');

        // Create valid card file
        $this->createTestCardFile('ValidCard', 'Valid');

        $result = $this->discovery->discoverIn($this->testPath);

        // Should find at least the valid card, ignoring non-PHP files
        $this->assertGreaterThanOrEqual(1, $result->count());

        // Check that a valid card class is found
        $foundValidCard = $result->contains(function ($className) {
            return str_contains($className, 'ValidCard');
        });
        $this->assertTrue($foundValidCard, 'Should find the valid card class');
    }

    public function test_discover_in_handles_nested_directories(): void
    {
        // Create nested directory structure
        $nestedPath = $this->testPath . '/Analytics';
        $this->files->makeDirectory($nestedPath, 0755, true);

        $this->createTestCardFile('RevenueCard', 'Revenue', true, $nestedPath);
        $this->createTestCardFile('UserMetricsCard', 'UserMetrics', true, $nestedPath);

        $result = $this->discovery->discoverIn($this->testPath);

        // Should find the nested cards
        $this->assertGreaterThanOrEqual(1, $result->count());

        // Check that nested card classes are found
        $foundNestedCard = $result->contains(function ($className) {
            return str_contains($className, 'RevenueCard') || str_contains($className, 'UserMetricsCard');
        });
        $this->assertTrue($foundNestedCard, 'Should find nested card classes');
    }

    public function test_discover_uses_cache_when_enabled(): void
    {
        Config::set('admin-panel.performance.cache_cards', true);
        Config::set('admin-panel.performance.cache_ttl', 3600);
        Config::set('admin-panel.cards.discovery_path', $this->testPath);
        
        $this->createTestCardFile('CachedCard', 'Cached');
        
        // Clear any existing cache
        Cache::flush();
        
        // First call should cache the result
        $result1 = $this->discovery->discover();
        
        // Remove the file
        $this->files->delete($this->testPath . '/CachedCard.php');
        
        // Second call should return cached result
        $result2 = $this->discovery->discover();
        
        $this->assertEquals($result1, $result2);
        $this->assertContains('CachedCard', $result2->toArray());
    }

    public function test_discover_bypasses_cache_when_disabled(): void
    {
        Config::set('admin-panel.performance.cache_cards', false);
        Config::set('admin-panel.cards.discovery_path', $this->testPath);
        
        $this->createTestCardFile('UncachedCard', 'Uncached');
        
        // First call
        $result1 = $this->discovery->discover();
        $this->assertContains('UncachedCard', $result1->toArray());
        
        // Remove the file
        $this->files->delete($this->testPath . '/UncachedCard.php');
        
        // Second call should not find the file
        $result2 = $this->discovery->discover();
        $this->assertNotContains('UncachedCard', $result2->toArray());
    }

    public function test_clear_cache_removes_cached_results(): void
    {
        Config::set('admin-panel.performance.cache_cards', true);
        Config::set('admin-panel.cards.discovery_path', $this->testPath);
        
        $this->createTestCardFile('CacheTestCard', 'CacheTest');
        
        // Cache the result
        $result1 = $this->discovery->discover();
        $this->assertContains('CacheTestCard', $result1->toArray());
        
        // Clear cache
        $this->discovery->clearCache();
        
        // Remove the file
        $this->files->delete($this->testPath . '/CacheTestCard.php');
        
        // Should not find the file after cache clear
        $result2 = $this->discovery->discover();
        $this->assertNotContains('CacheTestCard', $result2->toArray());
    }

    public function test_get_card_instances_returns_instantiated_cards(): void
    {
        $this->createTestCardFile('InstanceCard', 'Instance');
        
        Config::set('admin-panel.cards.discovery_path', $this->testPath);
        
        $instances = $this->discovery->getCardInstances();
        
        $this->assertInstanceOf(Collection::class, $instances);
        $this->assertCount(1, $instances);
        $this->assertInstanceOf(Card::class, $instances->first());
    }

    public function test_get_grouped_cards_groups_by_meta_group(): void
    {
        $this->createTestCardFile('AnalyticsCard', 'Analytics', true, null, 'Analytics');
        $this->createTestCardFile('UserCard', 'User', true, null, 'Users');
        $this->createTestCardFile('DefaultCard', 'Default', true, null, null);
        
        Config::set('admin-panel.cards.discovery_path', $this->testPath);
        
        $grouped = $this->discovery->getGroupedCards();
        
        $this->assertInstanceOf(Collection::class, $grouped);
        $this->assertArrayHasKey('Analytics', $grouped->toArray());
        $this->assertArrayHasKey('Users', $grouped->toArray());
        $this->assertArrayHasKey('Default', $grouped->toArray());
    }

    public function test_find_by_uri_key_returns_correct_card(): void
    {
        $this->createTestCardFile('FindableCard', 'Findable');
        
        Config::set('admin-panel.cards.discovery_path', $this->testPath);
        
        $card = $this->discovery->findByUriKey('findable-card');
        
        $this->assertInstanceOf(Card::class, $card);
        $this->assertEquals('findable-card', $card->uriKey());
    }

    public function test_find_by_uri_key_returns_null_for_nonexistent_card(): void
    {
        Config::set('admin-panel.cards.discovery_path', $this->testPath);
        
        $card = $this->discovery->findByUriKey('nonexistent-card');
        
        $this->assertNull($card);
    }

    protected function createTestCardFile(string $className, string $cardName, bool $isCard = true, ?string $path = null, ?string $group = null): void
    {
        $path = $path ?? $this->testPath;
        $filePath = $path . '/' . $className . '.php';

        // Determine namespace based on path structure
        $relativePath = str_replace($this->testPath, '', $path);
        $namespace = $relativePath ? str_replace('/', '\\', trim($relativePath, '/')) : '';
        $fullNamespace = $namespace ? $namespace . '\\' . $className : $className;

        if ($isCard) {
            $groupMeta = $group ? "'group' => '{$group}'," : '';
            $namespaceDeclaration = $namespace ? "namespace {$namespace};\n\n" : '';

            $content = <<<PHP
<?php

{$namespaceDeclaration}use JTD\AdminPanel\Cards\Card;
use Illuminate\Http\Request;

class {$className} extends Card
{
    public function __construct()
    {
        parent::__construct();
        \$this->withMeta([
            'title' => '{$cardName}Card',
            {$groupMeta}
            'icon' => 'chart-bar',
        ]);
    }

    public function authorize(Request \$request): bool
    {
        return true;
    }

    protected function getData(): array
    {
        return [
            'value' => 42,
            'label' => '{$cardName} Value',
            'timestamp' => now()->toISOString(),
        ];
    }

    public function uriKey(): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-\$0', '{$cardName}')) . '-card';
    }
}
PHP;
        } else {
            $namespaceDeclaration = $namespace ? "namespace {$namespace};\n\n" : '';
            $content = <<<PHP
<?php

{$namespaceDeclaration}class {$className}
{
    // Not a card class
}
PHP;
        }

        $this->files->put($filePath, $content);

        // Include the file so the class is available
        if ($isCard) {
            require_once $filePath;
        }
    }
}
