<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MakeCardCommand End-to-End Tests.
 *
 * Tests the complete MakeCardCommand workflow from command execution
 * to actual card usage in admin panel context.
 */
class MakeCardCommandE2ETest extends TestCase
{
    protected Filesystem $files;
    protected string $testOutputPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->files = new Filesystem();
        $this->testOutputPath = base_path('tests/temp');
        
        // Create temp directory for test files
        if (!$this->files->exists($this->testOutputPath)) {
            $this->files->makeDirectory($this->testOutputPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if ($this->files->exists($this->testOutputPath)) {
            $this->files->deleteDirectory($this->testOutputPath);
        }
        
        parent::tearDown();
    }

    public function test_complete_card_creation_workflow(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        // Step 1: Execute command
        $this->artisan('admin-panel:make-card', [
            'name' => 'DashboardStats',
            '--group' => 'Analytics',
            '--icon' => 'chart-bar'
        ])
            ->expectsOutput('Card DashboardStats created successfully!')
            ->expectsOutput('Created Vue component: DashboardStats.vue')
            ->assertExitCode(0);

        // Step 2: Verify PHP file creation and content
        $phpFile = $this->testOutputPath . '/app/Admin/Cards/DashboardStatsCard.php';
        $this->assertTrue($this->files->exists($phpFile));
        
        $phpContent = $this->files->get($phpFile);
        $this->assertStringContainsString('namespace App\\Admin\\Cards;', $phpContent);
        $this->assertStringContainsString('class DashboardStatsCard extends Card', $phpContent);
        $this->assertStringContainsString("'group' => 'Analytics'", $phpContent);
        $this->assertStringContainsString("'icon' => 'chart-bar'", $phpContent);

        // Step 3: Verify Vue file creation and content
        $vueFile = $this->testOutputPath . '/resources/js/admin-cards/DashboardStats.vue';
        $this->assertTrue($this->files->exists($vueFile));
        
        $vueContent = $this->files->get($vueFile);
        $this->assertStringContainsString('<template>', $vueContent);
        $this->assertStringContainsString('<script setup>', $vueContent);
        $this->assertStringContainsString('DashboardStats', $vueContent);

        // Step 4: Test PHP class instantiation
        require_once $phpFile;
        $className = 'App\\Admin\\Cards\\DashboardStatsCard';
        $card = new $className();
        
        $this->assertInstanceOf('JTD\\AdminPanel\\Cards\\Card', $card);
        
        // Step 5: Test card functionality
        $meta = $card->meta();
        $this->assertEquals('DashboardStatsCard', $meta['title']);
        $this->assertEquals('chart-bar', $meta['icon']);
        $this->assertEquals('Analytics', $meta['group']);
        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('timestamp', $meta);
    }

    public function test_multiple_cards_creation_and_usage(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        $cardConfigs = [
            ['name' => 'UserMetrics', 'group' => 'Users', 'icon' => 'users'],
            ['name' => 'OrderStats', 'group' => 'Orders', 'icon' => 'shopping-cart'],
            ['name' => 'RevenueChart', 'group' => 'Finance', 'icon' => 'currency-dollar'],
        ];

        $cards = [];
        
        foreach ($cardConfigs as $config) {
            // Create each card
            $this->artisan('admin-panel:make-card', [
                'name' => $config['name'],
                '--group' => $config['group'],
                '--icon' => $config['icon']
            ])->assertExitCode(0);

            // Load and instantiate the card
            $phpFile = $this->testOutputPath . '/app/Admin/Cards/' . $config['name'] . 'Card.php';
            require_once $phpFile;
            
            $className = 'App\\Admin\\Cards\\' . $config['name'] . 'Card';
            $cards[] = new $className();
        }

        // Test that all cards are properly instantiated
        $this->assertCount(3, $cards);
        
        foreach ($cards as $index => $card) {
            $config = $cardConfigs[$index];
            $meta = $card->meta();
            
            $this->assertEquals($config['name'] . 'Card', $meta['title']);
            $this->assertEquals($config['icon'], $meta['icon']);
            $this->assertEquals($config['group'], $meta['group']);
        }
    }

    public function test_card_with_custom_authorization(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        $this->artisan('admin-panel:make-card', [
            'name' => 'AuthCard',
            '--group' => 'Security',
            '--icon' => 'shield'
        ])->assertExitCode(0);

        $phpFile = $this->testOutputPath . '/app/Admin/Cards/AuthCardCard.php';
        require_once $phpFile;
        
        $className = 'App\\Admin\\Cards\\AuthCardCard';
        $card = new $className();

        // Test default authorization (should always return true)
        $request = $this->createMock(\Illuminate\Http\Request::class);
        $this->assertTrue($card->authorize($request));

        // Test custom authorization
        $card->canSee(function ($request) {
            return false; // Always deny for this test
        });
        
        $this->assertFalse($card->authorize($request));
    }

    public function test_card_json_serialization(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        $this->artisan('admin-panel:make-card', [
            'name' => 'JsonCard',
            '--group' => 'API',
            '--icon' => 'code'
        ])->assertExitCode(0);

        $phpFile = $this->testOutputPath . '/app/Admin/Cards/JsonCardCard.php';
        require_once $phpFile;
        
        $className = 'App\\Admin\\Cards\\JsonCardCard';
        $card = new $className();

        $json = $card->jsonSerialize();
        
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('component', $json);
        $this->assertArrayHasKey('uriKey', $json);
        $this->assertArrayHasKey('meta', $json);
        
        $this->assertEquals('JsonCardCard', $json['name']);
        $this->assertEquals('Cards/JsonCard', $json['component']);
        $this->assertEquals('json-card-card', $json['uriKey']);
    }

    public function test_card_with_service_provider_registration(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        // Create a mock service provider file
        $serviceProviderPath = $this->testOutputPath . '/app/Providers/AdminPanelServiceProvider.php';
        $this->files->makeDirectory(dirname($serviceProviderPath), 0755, true);
        
        $serviceProviderContent = <<<PHP
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Support\AdminPanel;

class AdminPanelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Some existing code
    }
}
PHP;
        
        $this->files->put($serviceProviderPath, $serviceProviderContent);

        // Create card with auto-registration
        $this->artisan('admin-panel:make-card', [
            'name' => 'RegisteredCard',
            '--group' => 'Test',
            '--icon' => 'test'
        ])->assertExitCode(0);

        // Verify registration was added
        $updatedContent = $this->files->get($serviceProviderPath);
        $this->assertStringContainsString('Register custom admin cards', $updatedContent);
        $this->assertStringContainsString('\\App\\Admin\\Cards\\RegisteredCardCard::class', $updatedContent);
        $this->assertStringContainsString('app(AdminPanel::class)->cards([', $updatedContent);
    }

    public function test_card_force_overwrite_workflow(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        // Create initial card
        $this->artisan('admin-panel:make-card', [
            'name' => 'OverwriteCard',
            '--group' => 'Original',
            '--icon' => 'original'
        ])->assertExitCode(0);

        $phpFile = $this->testOutputPath . '/app/Admin/Cards/OverwriteCardCard.php';
        $originalContent = $this->files->get($phpFile);
        $this->assertStringContainsString("'group' => 'Original'", $originalContent);

        // Try to overwrite without force (should fail)
        $this->artisan('admin-panel:make-card', [
            'name' => 'OverwriteCard',
            '--group' => 'Updated',
            '--icon' => 'updated'
        ])
            ->expectsOutput('Failed to create card: OverwriteCard')
            ->assertExitCode(1);

        // Content should remain unchanged
        $unchangedContent = $this->files->get($phpFile);
        $this->assertEquals($originalContent, $unchangedContent);

        // Overwrite with force (should succeed)
        $this->artisan('admin-panel:make-card', [
            'name' => 'OverwriteCard',
            '--group' => 'Updated',
            '--icon' => 'updated',
            '--force' => true
        ])
            ->expectsOutput('Card OverwriteCard created successfully!')
            ->assertExitCode(0);

        // Content should be updated
        $updatedContent = $this->files->get($phpFile);
        $this->assertStringContainsString("'group' => 'Updated'", $updatedContent);
        $this->assertStringContainsString("'icon' => 'updated'", $updatedContent);
    }

    public function test_card_with_complex_meta_data(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        $this->artisan('admin-panel:make-card', [
            'name' => 'ComplexCard',
            '--group' => 'Advanced Analytics',
            '--icon' => 'chart-pie-3d'
        ])->assertExitCode(0);

        $phpFile = $this->testOutputPath . '/app/Admin/Cards/ComplexCardCard.php';
        require_once $phpFile;
        
        $className = 'App\\Admin\\Cards\\ComplexCardCard';
        $card = new $className();

        // Test that the card can be extended with custom meta
        $card->withMeta([
            'customProperty' => 'customValue',
            'refreshInterval' => 30,
            'apiEndpoint' => '/api/complex-data',
            'chartType' => 'pie',
            'colors' => ['#FF6384', '#36A2EB', '#FFCE56'],
        ]);

        $meta = $card->meta();
        $this->assertEquals('customValue', $meta['customProperty']);
        $this->assertEquals(30, $meta['refreshInterval']);
        $this->assertEquals('/api/complex-data', $meta['apiEndpoint']);
        $this->assertEquals('pie', $meta['chartType']);
        $this->assertIsArray($meta['colors']);
        $this->assertCount(3, $meta['colors']);
    }

    public function test_end_to_end_card_lifecycle(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        // 1. Create card
        $this->artisan('admin-panel:make-card', [
            'name' => 'LifecycleCard',
            '--group' => 'Testing',
            '--icon' => 'beaker'
        ])->assertExitCode(0);

        // 2. Load and instantiate
        $phpFile = $this->testOutputPath . '/app/Admin/Cards/LifecycleCardCard.php';
        require_once $phpFile;
        
        $className = 'App\\Admin\\Cards\\LifecycleCardCard';
        $card = new $className();

        // 3. Test factory method
        $factoryCard = $className::make();
        $this->assertInstanceOf($className, $factoryCard);
        $this->assertNotSame($card, $factoryCard);

        // 4. Test authorization
        $request = $this->createMock(\Illuminate\Http\Request::class);
        $this->assertTrue($card->authorize($request));

        // 5. Test meta data
        $meta = $card->meta();
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('icon', $meta);
        $this->assertArrayHasKey('group', $meta);

        // 6. Test JSON serialization
        $json = $card->jsonSerialize();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('component', $json);

        // 7. Test Vue component file exists
        $vueFile = $this->testOutputPath . '/resources/js/admin-cards/LifecycleCard.vue';
        $this->assertTrue($this->files->exists($vueFile));
        
        $vueContent = $this->files->get($vueFile);
        $this->assertStringContainsString('LifecycleCard', $vueContent);
        $this->assertStringContainsString('defineProps', $vueContent);
        $this->assertStringContainsString('card:', $vueContent);
    }
}
