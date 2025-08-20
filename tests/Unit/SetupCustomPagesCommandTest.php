<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Console\Commands\SetupCustomPagesCommand;
use PHPUnit\Framework\TestCase;

/**
 * Setup Custom Pages Command Test
 *
 * Tests the setup command that configures Vite integration,
 * creates necessary directories, and sets up the development environment
 * for custom admin panel pages.
 */
class SetupCustomPagesCommandTest extends TestCase
{
    protected Filesystem $files;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/admin-panel-setup-test-' . uniqid();
        $this->files->makeDirectory($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if ($this->files->exists($this->tempDir)) {
            $this->files->deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_command_creates_admin_pages_directory(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $resourcesPath = $this->tempDir . '/resources';
        $this->files->makeDirectory($resourcesPath, 0755, true);

        $result = $command->setupDirectories($resourcesPath);

        $this->assertTrue($result);
        $this->assertDirectoryExists($resourcesPath . '/js/admin-pages');

        // Check if .gitkeep file was created
        $this->assertFileExists($resourcesPath . '/js/admin-pages/.gitkeep');
    }

    public function test_command_creates_admin_panel_service_provider(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $appPath = $this->tempDir . '/app';
        $this->files->makeDirectory($appPath . '/Providers', 0755, true);

        $result = $command->createAdminPanelServiceProvider($appPath);

        $this->assertTrue($result);
        $this->assertFileExists($appPath . '/Providers/AdminPanelServiceProvider.php');

        $content = $this->files->get($appPath . '/Providers/AdminPanelServiceProvider.php');
        $this->assertStringContainsString('class AdminPanelServiceProvider extends ServiceProvider', $content);
        $this->assertStringContainsString('use JTD\AdminPanel\Support\AdminPanel;', $content);
        $this->assertStringContainsString('app(AdminPanel::class)->pages([', $content);
    }

    public function test_command_skips_existing_service_provider(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $appPath = $this->tempDir . '/app';
        $this->files->makeDirectory($appPath . '/Providers', 0755, true);

        // Create existing service provider
        $existingContent = '<?php class AdminPanelServiceProvider { /* existing */ }';
        $this->files->put($appPath . '/Providers/AdminPanelServiceProvider.php', $existingContent);

        $result = $command->createAdminPanelServiceProvider($appPath);

        $this->assertTrue($result); // Should return true (success, but skipped)

        // Content should remain unchanged
        $content = $this->files->get($appPath . '/Providers/AdminPanelServiceProvider.php');
        $this->assertStringContainsString('/* existing */', $content);
    }

    public function test_command_modifies_vite_config(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $basePath = $this->tempDir;

        // Create basic vite.config.js
        $viteConfig = <<<JS
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
JS;
        $this->files->put($basePath . '/vite.config.js', $viteConfig);

        $result = $command->modifyViteConfig($basePath);

        $this->assertTrue($result);

        $updatedContent = $this->files->get($basePath . '/vite.config.js');
        $this->assertStringContainsString('adminPanel()', $updatedContent);
        $this->assertStringContainsString("import { adminPanel } from 'jerthedev/admin-panel/vite';", $updatedContent);
    }

    public function test_command_handles_missing_vite_config(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $basePath = $this->tempDir;

        $result = $command->modifyViteConfig($basePath);

        $this->assertFalse($result); // Should fail gracefully
    }

    public function test_command_updates_package_json_scripts(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $basePath = $this->tempDir;

        // Create basic package.json
        $packageJson = [
            'scripts' => [
                'dev' => 'vite',
                'build' => 'vite build'
            ]
        ];
        $this->files->put($basePath . '/package.json', json_encode($packageJson, JSON_PRETTY_PRINT));

        $result = $command->updatePackageJsonScripts($basePath);

        $this->assertTrue($result);

        $updatedContent = $this->files->get($basePath . '/package.json');
        $packageData = json_decode($updatedContent, true);

        $this->assertArrayHasKey('admin:dev', $packageData['scripts']);
        $this->assertArrayHasKey('admin:build', $packageData['scripts']);
        $this->assertEquals('vite --mode admin-panel', $packageData['scripts']['admin:dev']);
        $this->assertEquals('vite build --mode admin-panel', $packageData['scripts']['admin:build']);
    }

    public function test_command_registers_service_provider_in_bootstrap_providers(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $bootstrapPath = $this->tempDir . '/bootstrap';
        $this->files->makeDirectory($bootstrapPath, 0755, true);

        // Create basic bootstrap/providers.php (Laravel 12+)
        $providersConfig = <<<PHP
<?php

return [
    App\Providers\AppServiceProvider::class,
];
PHP;
        $this->files->put($bootstrapPath . '/providers.php', $providersConfig);

        $result = $command->registerServiceProvider($this->tempDir);

        $this->assertTrue($result);

        $updatedContent = $this->files->get($bootstrapPath . '/providers.php');
        $this->assertStringContainsString('App\Providers\AdminPanelServiceProvider::class,', $updatedContent);
    }

    public function test_command_registers_service_provider_in_config_fallback(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $configPath = $this->tempDir . '/config';
        $this->files->makeDirectory($configPath, 0755, true);

        // Create basic app.php config (older Laravel versions)
        $appConfig = <<<PHP
<?php

return [
    'providers' => [
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
    ],
];
PHP;
        $this->files->put($configPath . '/app.php', $appConfig);

        $result = $command->registerServiceProvider($this->tempDir);

        $this->assertTrue($result);

        $updatedContent = $this->files->get($configPath . '/app.php');
        $this->assertStringContainsString('App\Providers\AdminPanelServiceProvider::class,', $updatedContent);
    }

    public function test_command_validates_laravel_project(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        // Test with non-Laravel directory
        $result = $command->validateLaravelProject($this->tempDir);
        $this->assertFalse($result);

        // Create Laravel-like structure
        $this->files->makeDirectory($this->tempDir . '/app', 0755, true);
        $this->files->makeDirectory($this->tempDir . '/config', 0755, true);
        $this->files->put($this->tempDir . '/artisan', '#!/usr/bin/env php');

        $result = $command->validateLaravelProject($this->tempDir);
        $this->assertTrue($result);
    }

    public function test_command_creates_example_page(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        $appPath = $this->tempDir . '/app';
        $resourcesPath = $this->tempDir . '/resources';

        $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);

        $result = $command->createExamplePage($appPath, $resourcesPath);

        $this->assertTrue($result);

        // Check PHP page was created
        $this->assertFileExists($appPath . '/Admin/Pages/WelcomePage.php');

        // Check Vue component was created
        $this->assertFileExists($resourcesPath . '/js/admin-pages/Welcome.vue');

        // Verify content
        $phpContent = $this->files->get($appPath . '/Admin/Pages/WelcomePage.php');
        $this->assertStringContainsString('class WelcomePage extends Page', $phpContent);
        $this->assertStringContainsString('Welcome to Admin Panel', $phpContent);
    }

    public function test_command_detects_package_manager(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        // Test npm detection
        $this->files->put($this->tempDir . '/package.json', '{}');
        $packageManager = $command->detectPackageManager($this->tempDir);
        $this->assertEquals('npm', $packageManager);

        // Test yarn detection
        $this->files->put($this->tempDir . '/yarn.lock', '');
        $packageManager = $command->detectPackageManager($this->tempDir);
        $this->assertEquals('yarn', $packageManager);

        // Test pnpm detection
        $this->files->put($this->tempDir . '/pnpm-lock.yaml', '');
        $packageManager = $command->detectPackageManager($this->tempDir);
        $this->assertEquals('pnpm', $packageManager);
    }

    public function test_command_checks_dependency_installation(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        // Test with no package.json
        $result = $command->isDependencyInstalled($this->tempDir, '@vitejs/plugin-vue');
        $this->assertFalse($result);

        // Test with dependency in devDependencies
        $packageJson = [
            'devDependencies' => [
                '@vitejs/plugin-vue' => '^4.0.0'
            ]
        ];
        $this->files->put($this->tempDir . '/package.json', json_encode($packageJson));

        $result = $command->isDependencyInstalled($this->tempDir, '@vitejs/plugin-vue');
        $this->assertTrue($result);

        // Test with dependency in dependencies
        $packageJson = [
            'dependencies' => [
                'vue' => '^3.0.0'
            ]
        ];
        $this->files->put($this->tempDir . '/package.json', json_encode($packageJson));

        $result = $command->isDependencyInstalled($this->tempDir, 'vue');
        $this->assertTrue($result);
    }

    public function test_command_validates_setup(): void
    {
        $command = new SetupCustomPagesCommand($this->files);

        // Test validation failure with missing dependencies
        $result = $command->validateSetup($this->tempDir);
        $this->assertFalse($result);

        // Create required files for validation
        $this->files->makeDirectory($this->tempDir . '/resources/js/admin-pages', 0755, true);
        $this->files->makeDirectory($this->tempDir . '/app/Providers', 0755, true);

        // Create package.json with Vue plugin
        $packageJson = [
            'devDependencies' => [
                '@vitejs/plugin-vue' => '^4.0.0'
            ]
        ];
        $this->files->put($this->tempDir . '/package.json', json_encode($packageJson));

        // Create vite.config.js with required plugins
        $viteConfig = <<<JS
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { adminPanel } from 'jerthedev/admin-panel/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        adminPanel(),
    ],
});
JS;
        $this->files->put($this->tempDir . '/vite.config.js', $viteConfig);

        // Create AdminPanelServiceProvider
        $this->files->put($this->tempDir . '/app/Providers/AdminPanelServiceProvider.php', '<?php class AdminPanelServiceProvider {}');

        // Test validation success
        $result = $command->validateSetup($this->tempDir);
        $this->assertTrue($result);
    }
}
