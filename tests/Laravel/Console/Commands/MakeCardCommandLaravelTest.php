<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Laravel\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Tests\TestCase;

/**
 * MakeCardCommand Laravel Integration Tests.
 *
 * Tests the MakeCardCommand within full Laravel application context,
 * including artisan command execution and service provider integration.
 */
class MakeCardCommandLaravelTest extends TestCase
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

    public function test_make_card_command_is_registered(): void
    {
        $commands = $this->app->make('Illuminate\Contracts\Console\Kernel')->all();
        $this->assertArrayHasKey('admin-panel:make-card', $commands);
        $this->assertInstanceOf('JTD\AdminPanel\Console\Commands\MakeCardCommand', $commands['admin-panel:make-card']);
    }

    public function test_command_signature_and_description(): void
    {
        $command = $this->app->make('JTD\AdminPanel\Console\Commands\MakeCardCommand');
        
        $this->assertEquals('admin-panel:make-card', $command->getName());
        $this->assertStringContainsString('Create a new admin panel card', $command->getDescription());
    }

    public function test_command_has_required_arguments_and_options(): void
    {
        $command = $this->app->make('JTD\AdminPanel\Console\Commands\MakeCardCommand');
        $definition = $command->getDefinition();
        
        // Check argument
        $this->assertTrue($definition->hasArgument('name'));
        $this->assertEquals('The name of the card', $definition->getArgument('name')->getDescription());
        
        // Check options
        $this->assertTrue($definition->hasOption('group'));
        $this->assertTrue($definition->hasOption('icon'));
        $this->assertTrue($definition->hasOption('force'));
        
        $this->assertEquals('The group for the card', $definition->getOption('group')->getDescription());
        $this->assertEquals('The icon for the card', $definition->getOption('icon')->getDescription());
        $this->assertEquals('Overwrite existing files', $definition->getOption('force')->getDescription());
    }

    public function test_command_validates_card_name_input(): void
    {
        // Test invalid card name
        $this->artisan('admin-panel:make-card', ['name' => 'invalidName'])
            ->expectsOutput('Invalid card name: invalidName. Card names must be PascalCase and start with a letter.')
            ->assertExitCode(1);

        // Test empty card name
        $this->artisan('admin-panel:make-card', ['name' => ''])
            ->expectsOutput('Invalid card name: . Card names must be PascalCase and start with a letter.')
            ->assertExitCode(1);

        // Test numeric start
        $this->artisan('admin-panel:make-card', ['name' => '123Card'])
            ->expectsOutput('Invalid card name: 123Card. Card names must be PascalCase and start with a letter.')
            ->assertExitCode(1);
    }

    public function test_command_creates_card_files_successfully(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        $this->artisan('admin-panel:make-card', [
            'name' => 'TestCard',
            '--group' => 'Analytics',
            '--icon' => 'chart-bar'
        ])
            ->expectsOutput('Card TestCard created successfully!')
            ->expectsOutput('Created Vue component: TestCard.vue')
            ->assertExitCode(0);

        // Check that files were created
        $phpFile = $this->testOutputPath . '/app/Admin/Cards/TestCardCard.php';
        $vueFile = $this->testOutputPath . '/resources/js/admin-cards/TestCard.vue';
        
        $this->assertTrue($this->files->exists($phpFile));
        $this->assertTrue($this->files->exists($vueFile));
    }

    public function test_command_with_default_options(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        $this->artisan('admin-panel:make-card', ['name' => 'DefaultCard'])
            ->expectsOutput('Card DefaultCard created successfully!')
            ->assertExitCode(0);

        $phpFile = $this->testOutputPath . '/app/Admin/Cards/DefaultCardCard.php';
        $phpContent = $this->files->get($phpFile);
        
        $this->assertStringContainsString("'group' => 'Default'", $phpContent);
        $this->assertStringContainsString("'icon' => 'square-3-stack-3d'", $phpContent);
    }

    public function test_command_respects_force_option(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        // Create card first time
        $this->artisan('admin-panel:make-card', [
            'name' => 'ForceCard',
            '--group' => 'Test1',
            '--icon' => 'test1'
        ])->assertExitCode(0);

        // Try to create again without force - should fail
        $this->artisan('admin-panel:make-card', [
            'name' => 'ForceCard',
            '--group' => 'Test2',
            '--icon' => 'test2'
        ])
            ->expectsOutput('Failed to create card: ForceCard')
            ->assertExitCode(1);

        // Try to create again with force - should succeed
        $this->artisan('admin-panel:make-card', [
            'name' => 'ForceCard',
            '--group' => 'Test2',
            '--icon' => 'test2',
            '--force' => true
        ])
            ->expectsOutput('Card ForceCard created successfully!')
            ->assertExitCode(0);

        // Check that the file was updated
        $phpFile = $this->testOutputPath . '/app/Admin/Cards/ForceCardCard.php';
        $phpContent = $this->files->get($phpFile);
        $this->assertStringContainsString("'group' => 'Test2'", $phpContent);
        $this->assertStringContainsString("'icon' => 'test2'", $phpContent);
    }

    public function test_command_auto_registration_warning(): void
    {
        // Mock app_path to use test directory (no service provider)
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        $this->artisan('admin-panel:make-card', ['name' => 'WarningCard'])
            ->expectsOutput('Card WarningCard created successfully!')
            ->expectsOutput('Card created but auto-registration failed. You may need to manually register the card.')
            ->assertExitCode(0);
    }

    public function test_command_with_service_provider_auto_registration(): void
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

        $this->artisan('admin-panel:make-card', ['name' => 'AutoRegCard'])
            ->expectsOutput('Card AutoRegCard created successfully!')
            ->assertExitCode(0);

        $updatedContent = $this->files->get($serviceProviderPath);
        $this->assertStringContainsString('\\App\\Admin\\Cards\\AutoRegCardCard::class', $updatedContent);
    }

    public function test_command_handles_special_characters_in_options(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        $this->artisan('admin-panel:make-card', [
            'name' => 'SpecialCard',
            '--group' => 'Special & Group',
            '--icon' => 'chart-bar-square'
        ])
            ->expectsOutput('Card SpecialCard created successfully!')
            ->assertExitCode(0);

        $phpFile = $this->testOutputPath . '/app/Admin/Cards/SpecialCardCard.php';
        $phpContent = $this->files->get($phpFile);
        
        $this->assertStringContainsString("'group' => 'Special & Group'", $phpContent);
        $this->assertStringContainsString("'icon' => 'chart-bar-square'", $phpContent);
    }

    public function test_command_creates_directories_if_not_exist(): void
    {
        // Mock app_path and resource_path to use test directories
        $this->app->instance('path', $this->testOutputPath . '/app');
        $this->app->instance('path.resources', $this->testOutputPath . '/resources');

        // Ensure directories don't exist
        $cardsDir = $this->testOutputPath . '/app/Admin/Cards';
        $adminCardsDir = $this->testOutputPath . '/resources/js/admin-cards';
        
        if ($this->files->exists($cardsDir)) {
            $this->files->deleteDirectory($cardsDir);
        }
        if ($this->files->exists($adminCardsDir)) {
            $this->files->deleteDirectory($adminCardsDir);
        }

        $this->artisan('admin-panel:make-card', ['name' => 'DirCard'])
            ->expectsOutput('Card DirCard created successfully!')
            ->assertExitCode(0);

        // Check that directories were created
        $this->assertTrue($this->files->exists($cardsDir));
        $this->assertTrue($this->files->exists($adminCardsDir));
        
        // Check that files were created
        $this->assertTrue($this->files->exists($cardsDir . '/DirCardCard.php'));
        $this->assertTrue($this->files->exists($adminCardsDir . '/DirCard.vue'));
    }

    public function test_command_integration_with_laravel_container(): void
    {
        // Test that the command can be resolved from the container
        $command = $this->app->make('JTD\AdminPanel\Console\Commands\MakeCardCommand');
        $this->assertInstanceOf('JTD\AdminPanel\Console\Commands\MakeCardCommand', $command);

        // Test that filesystem is properly injected via reflection
        $reflection = new \ReflectionClass($command);
        $filesProperty = $reflection->getProperty('files');
        $filesProperty->setAccessible(true);
        $files = $filesProperty->getValue($command);
        $this->assertInstanceOf('Illuminate\Filesystem\Filesystem', $files);
    }
}
