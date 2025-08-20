<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Console\Commands\MakeCardCommand;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MakeCardCommand Integration Tests.
 *
 * Tests the MakeCardCommand integration with Laravel context,
 * including actual file generation and class instantiation.
 */
class MakeCardCommandIntegrationTest extends TestCase
{
    protected Filesystem $files;
    protected MakeCardCommand $command;
    protected string $testOutputPath;
    protected string $testAppPath;
    protected string $testResourcesPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->files = new Filesystem();
        $this->command = new MakeCardCommand($this->files);
        $this->testOutputPath = base_path('tests/temp');
        $this->testAppPath = $this->testOutputPath . '/app';
        $this->testResourcesPath = $this->testOutputPath . '/resources';
        
        // Create temp directories for test files
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

    public function test_create_card_generates_valid_php_file(): void
    {
        $result = $this->command->createCard(
            'TestCard',
            $this->testAppPath,
            $this->testResourcesPath,
            false,
            'Analytics',
            'chart-bar'
        );

        $this->assertTrue($result);

        // Check PHP file was created
        $phpFile = $this->testAppPath . '/Admin/Cards/TestCardCard.php';
        $this->assertTrue($this->files->exists($phpFile));

        // Check PHP file content
        $phpContent = $this->files->get($phpFile);
        $this->assertStringContainsString('namespace App\\Admin\\Cards;', $phpContent);
        $this->assertStringContainsString('class TestCardCard extends Card', $phpContent);
        $this->assertStringContainsString("'group' => 'Analytics'", $phpContent);
        $this->assertStringContainsString("'icon' => 'chart-bar'", $phpContent);
        $this->assertStringContainsString("'title' => 'TestCardCard'", $phpContent);
    }

    public function test_create_card_generates_valid_vue_file(): void
    {
        $result = $this->command->createCard(
            'TestCard',
            $this->testAppPath,
            $this->testResourcesPath,
            false,
            'Analytics',
            'chart-bar'
        );

        $this->assertTrue($result);

        // Check Vue file was created
        $vueFile = $this->testResourcesPath . '/js/admin-cards/TestCard.vue';
        $this->assertTrue($this->files->exists($vueFile));

        // Check Vue file content
        $vueContent = $this->files->get($vueFile);
        $this->assertStringContainsString('<template>', $vueContent);
        $this->assertStringContainsString('<script setup>', $vueContent);
        $this->assertStringContainsString('defineProps', $vueContent);
        $this->assertStringContainsString('TestCard', $vueContent);
        $this->assertStringContainsString('resources/js/admin-cards/TestCard.vue', $vueContent);
    }

    public function test_generated_php_class_is_syntactically_valid(): void
    {
        $this->command->createCard(
            'ValidCard',
            $this->testAppPath,
            $this->testResourcesPath,
            false,
            'Test',
            'star'
        );

        $phpFile = $this->testAppPath . '/Admin/Cards/ValidCardCard.php';
        $phpContent = $this->files->get($phpFile);

        // Test that the generated code is syntactically valid PHP
        $tempFile = tempnam(sys_get_temp_dir(), 'card_test');
        file_put_contents($tempFile, $phpContent);

        $output = [];
        $returnCode = 0;
        exec("php -l {$tempFile} 2>&1", $output, $returnCode);

        unlink($tempFile);

        $this->assertEquals(0, $returnCode, 'Generated PHP code should be syntactically valid');
    }

    public function test_generated_card_class_can_be_instantiated(): void
    {
        $this->command->createCard(
            'InstantiableCard',
            $this->testAppPath,
            $this->testResourcesPath,
            false,
            'Test',
            'cog'
        );

        $phpFile = $this->testAppPath . '/Admin/Cards/InstantiableCardCard.php';
        
        // Include the file and test class instantiation
        require_once $phpFile;
        
        $className = 'App\\Admin\\Cards\\InstantiableCardCard';
        $this->assertTrue(class_exists($className));

        $card = new $className();
        $this->assertInstanceOf('JTD\\AdminPanel\\Cards\\Card', $card);
    }

    public function test_generated_card_has_correct_meta_data(): void
    {
        $this->command->createCard(
            'MetaCard',
            $this->testAppPath,
            $this->testResourcesPath,
            false,
            'Dashboard',
            'chart-pie'
        );

        $phpFile = $this->testAppPath . '/Admin/Cards/MetaCardCard.php';
        require_once $phpFile;
        
        $className = 'App\\Admin\\Cards\\MetaCardCard';
        $card = new $className();

        $meta = $card->meta();
        
        $this->assertEquals('MetaCardCard', $meta['title']);
        $this->assertEquals('chart-pie', $meta['icon']);
        $this->assertEquals('Dashboard', $meta['group']);
        $this->assertFalse($meta['refreshable']);
        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('timestamp', $meta);
    }

    public function test_generated_card_make_method_works(): void
    {
        $this->command->createCard(
            'MakeCard',
            $this->testAppPath,
            $this->testResourcesPath,
            false,
            'Factory',
            'plus'
        );

        $phpFile = $this->testAppPath . '/Admin/Cards/MakeCardCard.php';
        require_once $phpFile;
        
        $className = 'App\\Admin\\Cards\\MakeCardCard';
        $card = $className::make();

        $this->assertInstanceOf($className, $card);
        $this->assertInstanceOf('JTD\\AdminPanel\\Cards\\Card', $card);
    }

    public function test_create_card_with_default_options(): void
    {
        $result = $this->command->createCard(
            'DefaultCard',
            $this->testAppPath,
            $this->testResourcesPath
        );

        $this->assertTrue($result);

        $phpFile = $this->testAppPath . '/Admin/Cards/DefaultCardCard.php';
        $phpContent = $this->files->get($phpFile);
        
        $this->assertStringContainsString("'group' => 'Default'", $phpContent);
        $this->assertStringContainsString("'icon' => 'square-3-stack-3d'", $phpContent);
    }

    public function test_create_card_respects_force_option(): void
    {
        // Create card first time
        $result1 = $this->command->createCard(
            'ForceCard',
            $this->testAppPath,
            $this->testResourcesPath,
            false,
            'Test',
            'test'
        );
        $this->assertTrue($result1);

        // Try to create again without force - should fail
        $result2 = $this->command->createCard(
            'ForceCard',
            $this->testAppPath,
            $this->testResourcesPath,
            false,
            'Test2',
            'test2'
        );
        $this->assertFalse($result2);

        // Try to create again with force - should succeed
        $result3 = $this->command->createCard(
            'ForceCard',
            $this->testAppPath,
            $this->testResourcesPath,
            true,
            'Test2',
            'test2'
        );
        $this->assertTrue($result3);

        // Check that the file was updated
        $phpFile = $this->testAppPath . '/Admin/Cards/ForceCardCard.php';
        $phpContent = $this->files->get($phpFile);
        $this->assertStringContainsString("'group' => 'Test2'", $phpContent);
        $this->assertStringContainsString("'icon' => 'test2'", $phpContent);
    }

    public function test_multiple_cards_can_be_created(): void
    {
        $cards = [
            ['name' => 'FirstCard', 'group' => 'First', 'icon' => 'one'],
            ['name' => 'SecondCard', 'group' => 'Second', 'icon' => 'two'],
            ['name' => 'ThirdCard', 'group' => 'Third', 'icon' => 'three'],
        ];

        foreach ($cards as $cardConfig) {
            $result = $this->command->createCard(
                $cardConfig['name'],
                $this->testAppPath,
                $this->testResourcesPath,
                false,
                $cardConfig['group'],
                $cardConfig['icon']
            );
            $this->assertTrue($result);

            // Check PHP file
            $phpFile = $this->testAppPath . '/Admin/Cards/' . $cardConfig['name'] . 'Card.php';
            $this->assertTrue($this->files->exists($phpFile));

            // Check Vue file
            $vueFile = $this->testResourcesPath . '/js/admin-cards/' . $cardConfig['name'] . '.vue';
            $this->assertTrue($this->files->exists($vueFile));
        }
    }

    public function test_auto_register_card_creates_service_provider_registration(): void
    {
        // Create a mock service provider file
        $serviceProviderPath = $this->testAppPath . '/Providers/AdminPanelServiceProvider.php';
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

        $result = $this->command->autoRegisterCard('TestCard', $this->testAppPath);
        $this->assertTrue($result);

        $updatedContent = $this->files->get($serviceProviderPath);
        $this->assertStringContainsString('Register custom admin cards', $updatedContent);
        $this->assertStringContainsString('\\App\\Admin\\Cards\\TestCardCard::class', $updatedContent);
    }

    public function test_card_validation_with_real_names(): void
    {
        $validNames = ['UserStats', 'OrderMetrics', 'RevenueChart', 'SystemHealth'];
        $invalidNames = ['userStats', '123Card', 'card-name', 'card name', ''];

        foreach ($validNames as $name) {
            $this->assertTrue($this->command->isValidCardName($name), "'{$name}' should be valid");
        }

        foreach ($invalidNames as $name) {
            $this->assertFalse($this->command->isValidCardName($name), "'{$name}' should be invalid");
        }
    }
}
