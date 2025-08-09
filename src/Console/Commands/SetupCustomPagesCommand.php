<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Setup Custom Pages Command
 *
 * Sets up the development environment for custom admin panel pages
 * including Vite integration, directory structure, and example files.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Console\Commands
 */
class SetupCustomPagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:setup-custom-pages
                            {--force : Overwrite existing files}
                            {--no-example : Skip creating example page}';

    /**
     * The console command description.
     */
    protected $description = 'Set up custom pages development environment with Vite integration';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $basePath = base_path();
        $force = $this->option('force');
        $noExample = $this->option('no-example');

        $this->safeInfo('Setting up custom pages development environment...');

        // Validate Laravel project
        if (!$this->validateLaravelProject($basePath)) {
            $this->safeError('This command must be run from a Laravel project root.');
            return 1;
        }

        // Setup directories
        if (!$this->setupDirectories(resource_path())) {
            $this->safeError('Failed to create admin-pages directory.');
            return 1;
        }

        // Create AdminPanelServiceProvider if needed
        if (!$this->createAdminPanelServiceProvider(app_path())) {
            $this->safeWarn('Could not create AdminPanelServiceProvider. You may need to create it manually.');
        }

        // Register service provider
        if (!$this->registerServiceProvider($basePath)) {
            $this->safeWarn('Could not register AdminPanelServiceProvider. You may need to add it manually to config/app.php.');
        }

        // Install frontend dependencies
        if (!$this->installFrontendDependencies($basePath)) {
            $this->safeWarn('Could not install frontend dependencies. You may need to install them manually.');
            $this->safeLine('Run: npm install @vitejs/plugin-vue');
        }

        // Modify Vite config
        if (!$this->modifyViteConfig($basePath)) {
            $this->safeWarn('Could not modify vite.config.js. You may need to add the adminPanel plugin manually.');
            $this->safeLine('Add this to your vite.config.js:');
            $this->safeLine("import { adminPanel } from 'jerthedev/admin-panel/vite';");
            $this->safeLine('Then add adminPanel() to your plugins array.');
        }

        // Update package.json scripts
        if (!$this->updatePackageJsonScripts($basePath)) {
            $this->safeWarn('Could not update package.json scripts. You may need to add admin build scripts manually.');
        }

        // Create example page
        if (!$noExample) {
            if (!$this->createExamplePage(app_path(), resource_path())) {
                $this->safeWarn('Could not create example page.');
            } else {
                $this->safeInfo('Created example Welcome page.');
            }
        }

        // Validate setup
        if (!$this->validateSetup($basePath)) {
            $this->safeWarn('Setup validation failed. Some manual configuration may be required.');
        }

        $this->safeInfo('✅ Custom pages setup complete!');
        $this->safeLine('');
        $this->safeLine('Next steps:');
        $this->safeLine('1. Run: npm run admin:dev (for development)');
        $this->safeLine('2. Run: php artisan admin-panel:make-page YourPage');
        $this->safeLine('3. Visit: /admin to see your custom pages');

        return 0;
    }

    /**
     * Validate that this is a Laravel project.
     */
    public function validateLaravelProject(string $basePath): bool
    {
        return $this->files->exists($basePath . '/artisan') &&
               $this->files->exists($basePath . '/app') &&
               $this->files->exists($basePath . '/config');
    }

    /**
     * Setup necessary directories.
     */
    public function setupDirectories(string $resourcesPath): bool
    {
        $adminPagesPath = $resourcesPath . '/js/admin-pages';

        if (!$this->files->exists($adminPagesPath)) {
            $this->files->makeDirectory($adminPagesPath, 0755, true);

            // Create .gitkeep file
            $this->files->put($adminPagesPath . '/.gitkeep', '');

            $this->safeInfo('Created admin-pages directory.');
        } else {
            $this->safeLine('Admin-pages directory already exists.');
        }

        return true;
    }

    /**
     * Create AdminPanelServiceProvider if it doesn't exist.
     */
    public function createAdminPanelServiceProvider(string $appPath): bool
    {
        $serviceProviderPath = $appPath . '/Providers/AdminPanelServiceProvider.php';

        if ($this->files->exists($serviceProviderPath)) {
            $this->safeLine('AdminPanelServiceProvider already exists.');
            return true;
        }

        if (!$this->files->exists($appPath . '/Providers')) {
            $this->files->makeDirectory($appPath . '/Providers', 0755, true);
        }

        $content = $this->generateServiceProviderContent();
        $this->files->put($serviceProviderPath, $content);

        $this->safeInfo('Created AdminPanelServiceProvider.');
        return true;
    }

    /**
     * Generate service provider content.
     */
    protected function generateServiceProviderContent(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Admin Panel Service Provider
 *
 * Registers custom admin panel pages and resources.
 * Generated by admin-panel:setup-custom-pages command.
 */
class AdminPanelServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom admin pages
        app(AdminPanel::class)->pages([
            // Add your custom page classes here
            // \\App\\Admin\\Pages\\YourPage::class,
        ]);

        // Register custom admin resources
        app(AdminPanel::class)->resources([
            // Add your custom resource classes here
            // \\App\\Admin\\Resources\\YourResource::class,
        ]);
    }
}
PHP;
    }

    /**
     * Install frontend dependencies required for admin pages.
     */
    public function installFrontendDependencies(string $basePath): bool
    {
        $packageManager = $this->detectPackageManager($basePath);

        if (!$packageManager) {
            $this->safeWarn('No package manager detected. Please install dependencies manually.');
            return false;
        }

        // Check if Vue plugin is already installed
        if ($this->isDependencyInstalled($basePath, '@vitejs/plugin-vue')) {
            $this->safeLine('Vue plugin already installed.');
        } else {
            // Install Vue plugin
            $installCommand = $this->getInstallCommand($packageManager, '@vitejs/plugin-vue');
            $this->safeInfo("Installing Vue plugin: {$installCommand}");

            $result = $this->executeShellCommand($installCommand, $basePath);
            if (!$result) {
                return false;
            }
        }

        // Check if Vue is installed (optional, as it might be a dev dependency)
        if (!$this->isDependencyInstalled($basePath, 'vue')) {
            $this->safeLine('Vue not found, but this might be intentional for admin-only setup.');
        }

        return true;
    }

    /**
     * Detect which package manager is being used.
     */
    public function detectPackageManager(string $basePath): ?string
    {
        if ($this->files->exists($basePath . '/pnpm-lock.yaml')) {
            return 'pnpm';
        }

        if ($this->files->exists($basePath . '/yarn.lock')) {
            return 'yarn';
        }

        if ($this->files->exists($basePath . '/package-lock.json') || $this->files->exists($basePath . '/package.json')) {
            return 'npm';
        }

        return null;
    }

    /**
     * Check if a dependency is already installed.
     */
    public function isDependencyInstalled(string $basePath, string $dependency): bool
    {
        $packageJsonPath = $basePath . '/package.json';

        if (!$this->files->exists($packageJsonPath)) {
            return false;
        }

        $packageJson = json_decode($this->files->get($packageJsonPath), true);

        return isset($packageJson['dependencies'][$dependency]) ||
               isset($packageJson['devDependencies'][$dependency]);
    }

    /**
     * Get the install command for a package manager.
     */
    protected function getInstallCommand(string $packageManager, string $dependency): string
    {
        return match ($packageManager) {
            'pnpm' => "pnpm add -D {$dependency}",
            'yarn' => "yarn add -D {$dependency}",
            'npm' => "npm install -D {$dependency}",
            default => "npm install -D {$dependency}",
        };
    }

    /**
     * Execute a shell command.
     */
    protected function executeShellCommand(string $command, string $workingDirectory): bool
    {
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],  // stdin
                1 => ['pipe', 'w'],  // stdout
                2 => ['pipe', 'w'],  // stderr
            ],
            $pipes,
            $workingDirectory
        );

        if (!is_resource($process)) {
            return false;
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $returnCode = proc_close($process);

        if ($returnCode !== 0) {
            $this->safeWarn("Command failed: {$command}");
            if ($error) {
                $this->safeWarn("Error: {$error}");
            }
            return false;
        }

        return true;
    }

    /**
     * Modify vite.config.js to include admin panel plugin.
     */
    public function modifyViteConfig(string $basePath): bool
    {
        $viteConfigPath = $basePath . '/vite.config.js';

        if (!$this->files->exists($viteConfigPath)) {
            return false;
        }

        $content = $this->files->get($viteConfigPath);

        // Check if already modified
        if (str_contains($content, 'adminPanel()')) {
            $this->safeLine('Vite config already includes adminPanel plugin.');
            return true;
        }

        // Add Vue import if not present
        if (!str_contains($content, "import vue from '@vitejs/plugin-vue'")) {
            $content = str_replace(
                "import laravel from 'laravel-vite-plugin';",
                "import laravel from 'laravel-vite-plugin';\nimport vue from '@vitejs/plugin-vue';",
                $content
            );
        }

        // Add admin panel import if not present
        if (!str_contains($content, "import { adminPanel } from")) {
            $content = str_replace(
                "import laravel from 'laravel-vite-plugin';",
                "import laravel from 'laravel-vite-plugin';\nimport { adminPanel } from './packages/jerthedev/admin-panel/vite/index.js';",
                $content
            );
        }

        // Add Vue plugin to plugins array if not present
        if (!str_contains($content, 'vue()')) {
            $content = preg_replace(
                '/plugins:\s*\[\s*laravel\([^)]*\),/s',
                '$0' . "\n        vue(),",
                $content
            );
        }

        // Add admin panel plugin to plugins array if not present
        if (!str_contains($content, 'adminPanel()')) {
            $content = preg_replace(
                '/plugins:\s*\[\s*laravel\([^)]*\),/s',
                '$0' . "\n        adminPanel(),",
                $content
            );
        }

        $this->files->put($viteConfigPath, $content);
        $this->safeInfo('Modified vite.config.js to include adminPanel plugin.');

        return true;
    }

    /**
     * Update package.json with admin build scripts.
     */
    public function updatePackageJsonScripts(string $basePath): bool
    {
        $packageJsonPath = $basePath . '/package.json';

        if (!$this->files->exists($packageJsonPath)) {
            return false;
        }

        $content = $this->files->get($packageJsonPath);
        $packageData = json_decode($content, true);

        if (!isset($packageData['scripts'])) {
            $packageData['scripts'] = [];
        }

        // Add admin scripts if they don't exist
        if (!isset($packageData['scripts']['admin:dev'])) {
            $packageData['scripts']['admin:dev'] = 'vite --mode admin-panel';
        }

        if (!isset($packageData['scripts']['admin:build'])) {
            $packageData['scripts']['admin:build'] = 'vite build --mode admin-panel';
        }

        $this->files->put($packageJsonPath, json_encode($packageData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->safeInfo('Updated package.json with admin build scripts.');

        return true;
    }

    /**
     * Register service provider in bootstrap/providers.php (Laravel 12+) or config/app.php (older versions).
     */
    public function registerServiceProvider(string $basePath): bool
    {
        // Try Laravel 12+ bootstrap/providers.php first
        $bootstrapPath = $basePath . '/bootstrap/providers.php';
        if ($this->files->exists($bootstrapPath)) {
            return $this->registerInBootstrapProviders($bootstrapPath);
        }

        // Fallback to config/app.php for older Laravel versions
        $configPath = $basePath . '/config/app.php';
        if ($this->files->exists($configPath)) {
            return $this->registerInConfigApp($configPath);
        }

        return false;
    }

    /**
     * Register service provider in bootstrap/providers.php (Laravel 12+).
     */
    protected function registerInBootstrapProviders(string $bootstrapPath): bool
    {
        $content = $this->files->get($bootstrapPath);

        // Check if already registered
        if (str_contains($content, 'AdminPanelServiceProvider::class')) {
            $this->safeLine('AdminPanelServiceProvider already registered.');
            return true;
        }

        // Add to providers array
        $content = str_replace(
            'App\Providers\AppServiceProvider::class,',
            "App\Providers\AppServiceProvider::class,\n    App\Providers\AdminPanelServiceProvider::class,",
            $content
        );

        $this->files->put($bootstrapPath, $content);
        $this->safeInfo('Registered AdminPanelServiceProvider in bootstrap/providers.php.');

        return true;
    }

    /**
     * Register service provider in config/app.php (older Laravel versions).
     */
    protected function registerInConfigApp(string $configPath): bool
    {
        $content = $this->files->get($configPath);

        // Check if already registered
        if (str_contains($content, 'AdminPanelServiceProvider::class')) {
            $this->safeLine('AdminPanelServiceProvider already registered.');
            return true;
        }

        // Add to providers array
        $content = str_replace(
            'App\Providers\AuthServiceProvider::class,',
            "App\Providers\AuthServiceProvider::class,\n        App\Providers\AdminPanelServiceProvider::class,",
            $content
        );

        $this->files->put($configPath, $content);
        $this->safeInfo('Registered AdminPanelServiceProvider in config/app.php.');

        return true;
    }

    /**
     * Create an example welcome page.
     */
    public function createExamplePage(string $appPath, string $resourcesPath): bool
    {
        $phpPath = $appPath . '/Admin/Pages/WelcomePage.php';
        $vuePath = $resourcesPath . '/js/admin-pages/Welcome.vue';

        // Create directories
        if (!$this->files->exists($appPath . '/Admin/Pages')) {
            $this->files->makeDirectory($appPath . '/Admin/Pages', 0755, true);
        }

        // Create PHP page
        $phpContent = $this->generateExamplePageContent();
        $this->files->put($phpPath, $phpContent);

        // Create Vue component
        $vueContent = $this->generateExampleVueContent();
        $this->files->put($vuePath, $vueContent);

        return true;
    }

    /**
     * Generate example page PHP content.
     */
    protected function generateExamplePageContent(): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Admin\Pages;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Pages\Page;

/**
 * Welcome Page
 *
 * Example custom admin panel page demonstrating the basic structure
 * and functionality of custom pages.
 *
 * @author Generated by admin-panel:setup-custom-pages
 */
class WelcomePage extends Page
{
    /**
     * The Vue components for this page.
     */
    public static array \$components = ['Pages/Welcome'];

    /**
     * The menu group this page belongs to.
     */
    public static ?string \$group = 'Getting Started';

    /**
     * The display title for this page.
     */
    public static ?string \$title = 'Welcome to Admin Panel';

    /**
     * The icon for this page (Heroicon name).
     */
    public static ?string \$icon = 'home';

    /**
     * Get the fields for this page.
     */
    public function fields(Request \$request): array
    {
        return [
            Text::make('Welcome Message')
                ->default('Welcome to your custom admin panel!')
                ->help('This is an example field to demonstrate page functionality.'),
        ];
    }

    /**
     * Get custom data for this page.
     */
    public function data(Request \$request): array
    {
        return [
            'setup_complete' => true,
            'version' => '1.0.0',
            'features' => [
                'Custom Pages',
                'Multi-Component Support',
                'Auto-Registration',
                'Vite Integration',
            ],
        ];
    }

    /**
     * Get actions for this page.
     */
    public function actions(Request \$request): array
    {
        return [];
    }

    /**
     * Determine if the user can view this page.
     */
    public static function authorizedToViewAny(Request \$request): bool
    {
        return \$request->user() !== null;
    }
}
PHP;
    }

    /**
     * Generate example Vue component content.
     */
    protected function generateExampleVueContent(): string
    {
        return <<<VUE
<template>
    <div class="p-6 space-y-8">
        <!-- Welcome Header -->
        <div class="text-center">
            <div class="mx-auto h-12 w-12 text-green-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="mt-4 text-3xl font-bold text-gray-900">{{ page.title }}</h1>
            <p class="mt-2 text-lg text-gray-600">Your custom pages development environment is ready!</p>
        </div>

        <!-- Setup Status -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-green-900 mb-4">✅ Setup Complete</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="feature in data.features" :key="feature"
                     class="flex items-center text-green-800">
                    <svg class="h-5 w-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    {{ feature }}
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-blue-900 mb-4">🚀 Next Steps</h2>
            <div class="space-y-3 text-blue-800">
                <div class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">1</span>
                    <div>
                        <p class="font-medium">Create your first custom page</p>
                        <code class="text-sm bg-blue-100 px-2 py-1 rounded">php artisan admin-panel:make-page MyDashboard</code>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">2</span>
                    <div>
                        <p class="font-medium">Start the development server</p>
                        <code class="text-sm bg-blue-100 px-2 py-1 rounded">npm run admin:dev</code>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">3</span>
                    <div>
                        <p class="font-medium">Build for production</p>
                        <code class="text-sm bg-blue-100 px-2 py-1 rounded">npm run admin:build</code>
                    </div>
                </div>
            </div>
        </div>

        <!-- Field Display -->
        <div v-if="fields && fields.length > 0" class="bg-white border border-gray-200 rounded-lg p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">📝 Example Fields</h2>
            <div class="space-y-4">
                <div v-for="field in fields" :key="field.attribute" class="border-l-4 border-blue-500 pl-4">
                    <h3 class="font-medium text-gray-900">{{ field.name }}</h3>
                    <p class="text-sm text-gray-600">{{ field.help }}</p>
                    <p class="text-sm text-blue-600 font-mono">{{ field.value || field.default || 'No value' }}</p>
                </div>
            </div>
        </div>

        <!-- Development Info -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-gray-900 mb-2">Development Info</h3>
            <div class="text-xs text-gray-600 space-y-1">
                <p><strong>Page Class:</strong> App\\Admin\\Pages\\WelcomePage</p>
                <p><strong>Vue Component:</strong> resources/js/admin-pages/Welcome.vue</p>
                <p><strong>Version:</strong> {{ data.version }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    fields: {
        type: Array,
        default: () => []
    },
    data: {
        type: Object,
        default: () => ({})
    }
})

// Log successful setup
console.log('🎉 Welcome page loaded successfully!')
console.log('✅ Custom pages setup is complete!')
</script>
VUE;
    }

    /**
     * Safe console output methods for testing.
     */
    protected function safeInfo(string $message): void
    {
        if ($this->output) {
            $this->info($message);
        }
    }

    protected function safeLine(string $message): void
    {
        if ($this->output) {
            $this->line($message);
        }
    }

    protected function safeWarn(string $message): void
    {
        if ($this->output) {
            $this->warn($message);
        }
    }

    protected function safeError(string $message): void
    {
        if ($this->output) {
            $this->error($message);
        }
    }

    /**
     * Validate that the setup is working correctly.
     */
    public function validateSetup(string $basePath): bool
    {
        // Check if package.json has the required dependencies
        if (!$this->isDependencyInstalled($basePath, '@vitejs/plugin-vue')) {
            $this->safeWarn('Validation failed: @vitejs/plugin-vue not found in package.json');
            return false;
        }

        // Check if vite.config.js has the required plugins
        $viteConfigPath = $basePath . '/vite.config.js';
        if ($this->files->exists($viteConfigPath)) {
            $content = $this->files->get($viteConfigPath);

            if (!str_contains($content, 'vue()')) {
                $this->safeWarn('Validation failed: vue() plugin not found in vite.config.js');
                return false;
            }

            if (!str_contains($content, 'adminPanel()')) {
                $this->safeWarn('Validation failed: adminPanel() plugin not found in vite.config.js');
                return false;
            }
        }

        // Check if admin pages directory exists
        $adminPagesPath = $basePath . '/resources/js/admin-pages';
        if (!$this->files->exists($adminPagesPath)) {
            $this->safeWarn('Validation failed: admin-pages directory not found');
            return false;
        }

        // Check if AdminPanelServiceProvider exists
        $serviceProviderPath = $basePath . '/app/Providers/AdminPanelServiceProvider.php';
        if (!$this->files->exists($serviceProviderPath)) {
            $this->safeWarn('Validation failed: AdminPanelServiceProvider not found');
            return false;
        }

        $this->safeInfo('✅ Setup validation passed!');
        return true;
    }
}
