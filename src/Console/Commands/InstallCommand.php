<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Install Command
 *
 * Handles the installation of the admin panel package including
 * publishing assets, running migrations, and setting up the environment.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Console\Commands
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:install
                            {--force : Overwrite existing files}
                            {--no-migrate : Skip running migrations}
                            {--no-assets : Skip publishing assets}
                            {--skip-config : Skip publishing configuration}
                            {--create-service-provider : Create AdminServiceProvider for manual resource registration}';

    /**
     * The console command description.
     */
    protected $description = 'Install the JTD AdminPanel package';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Installing JTD AdminPanel...');
        $this->newLine();

        // Ensure Inertia.js backend service is available
        $this->ensureInertiaInstalled();

        // Ensure Ziggy route helper is available
        $this->ensureZiggyInstalled();

        // Publish configuration (optional with --skip-config flag)
        if (!$this->option('skip-config')) {
            $this->publishConfiguration();
        }

        // Create admin directory structure (essential for customization)
        $this->createAdminDirectory();

        // Publish pre-built assets (self-contained approach)
        if (!$this->option('no-assets')) {
            $this->publishPreBuiltAssets();
        }

        // Set up admin authentication guard
        $this->setupAdminGuard();

        // Run migrations if not skipped
        if (!$this->option('no-migrate')) {
            $this->runMigrations();
        }

        $this->displaySuccessMessage();

        return self::SUCCESS;
    }

    /**
     * Publish the configuration file.
     */
    protected function publishConfiguration(): void
    {
        $this->info('📝 Publishing configuration...');

        $this->call('vendor:publish', [
            '--tag' => 'admin-panel-config',
            '--force' => $this->option('force'),
        ]);

        $this->info('✅ Configuration published to config/admin-panel.php');
        $this->displayEnvVariables();
    }

    /**
     * Display recommended ENV variables for the admin panel.
     */
    protected function displayEnvVariables(): void
    {
        $this->newLine();
        $this->info('📋 Recommended ENV Variables:');
        $this->line('   Add these to your .env file for optimal configuration:');
        $this->newLine();

        $envVars = [
            'ADMIN_PANEL_NAME="Admin Panel"',
            'ADMIN_PANEL_PATH=admin',
            'ADMIN_PANEL_GUARD=admin',
            'ADMIN_PANEL_THEME=default',
            'ADMIN_PANEL_PAGINATION_LIMIT=25',
            'ADMIN_PANEL_CACHE_TTL=3600',
        ];

        foreach ($envVars as $var) {
            $this->line("   {$var}");
        }

        $this->newLine();
        $this->comment('💡 These variables are optional - the package works with defaults');
    }

    /**
     * Publish package assets.
     */
    protected function publishAssets(): void
    {
        $this->info('Publishing assets...');

        $this->call('vendor:publish', [
            '--tag' => 'admin-panel-assets',
            '--force' => $this->option('force'),
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'admin-panel-views',
            '--force' => $this->option('force'),
        ]);
    }

    /**
     * Publish Vue components to main app.
     */
    protected function publishVueComponents(): void
    {
        $this->info('Publishing Vue components...');

        $packagePath = dirname(__DIR__, 3);
        $sourcePath = $packagePath . '/resources/js';
        $targetPath = base_path('resources/js/admin-panel');

        // Create target directory
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
        }

        // Copy Vue components
        $this->copyDirectory($sourcePath, $targetPath);

        $this->line('✅ Vue components published to resources/js/admin-panel');
    }

    /**
     * Copy directory recursively.
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * Create the admin resources directory.
     */
    protected function createAdminDirectory(): void
    {
        $this->info('📁 Creating admin directory structure...');

        $directories = [
            'app/Admin',
            'app/Admin/Resources',
            'app/Admin/Pages',
            'app/Admin/Metrics',
            'app/Admin/Actions',
            'app/Admin/Filters',
        ];

        foreach ($directories as $directory) {
            if (! File::exists(base_path($directory))) {
                File::makeDirectory(base_path($directory), 0755, true);
                $this->line("   Created: {$directory}");
            }
        }

        // Create AdminServiceProvider only if requested
        if ($this->option('create-service-provider')) {
            $this->createAdminServiceProvider();
        } else {
            $this->info('✅ Admin directory structure created');
            $this->comment('💡 Resources will be auto-discovered from app/Admin/Resources/');
            $this->comment('   Use --create-service-provider for manual resource registration');
        }
    }

    /**
     * Create the AdminServiceProvider for manual resource registration.
     */
    protected function createAdminServiceProvider(): void
    {
        $this->info('📝 Creating AdminServiceProvider for manual resource registration...');

        $providerPath = app_path('Providers/AdminServiceProvider.php');

        if (! File::exists($providerPath) || $this->option('force')) {
            $stub = File::get(__DIR__ . '/../stubs/AdminServiceProvider.stub');

            $content = str_replace(
                ['{{ namespace }}', '{{ class }}'],
                ['App\\Providers', 'AdminServiceProvider'],
                $stub
            );

            File::put($providerPath, $content);
            $this->info('✅ Created: app/Providers/AdminServiceProvider.php');

            $this->newLine();
            $this->warn('📋 Manual Step Required:');
            $this->line('   Add AdminServiceProvider to your config/app.php providers array:');
            $this->line('   App\\Providers\\AdminServiceProvider::class,');
            $this->newLine();
            $this->comment('💡 This provider allows manual resource registration alongside auto-discovery');
        } else {
            $this->line('   AdminServiceProvider already exists');
        }
    }

    /**
     * Run package migrations.
     */
    protected function runMigrations(): void
    {
        $this->info('Running migrations...');

        $this->call('migrate');
    }

    /**
     * Update package.json with required dependencies.
     */
    protected function updatePackageJson(): void
    {
        $this->info('Updating package.json...');

        $packageJsonPath = base_path('package.json');

        if (File::exists($packageJsonPath)) {
            $packageJson = json_decode(File::get($packageJsonPath), true);

            $dependencies = [
                '@inertiajs/vue3' => '^1.2.0',
                '@vitejs/plugin-vue' => '^6.0.0',
                'vue' => '^3.4.0',
                '@headlessui/vue' => '^1.7.0',
                '@heroicons/vue' => '^2.0.0',
                '@tailwindcss/vite' => '^4.0.0',
                'autoprefixer' => '^10.4.0',
                'postcss' => '^8.4.0',
            ];

            foreach ($dependencies as $package => $version) {
                $packageJson['devDependencies'][$package] = $version;
            }

            File::put($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $this->line('Updated package.json with required dependencies');
            $this->warn('Run "npm install" to install the new dependencies');
        }
    }

    /**
     * Update vite.config.js for admin panel assets.
     */
    protected function updateViteConfig(): void
    {
        $this->info('Please ensure your vite.config.js includes Vue and Tailwind v4:');
        $this->line('');
        $this->line("import { defineConfig } from 'vite';");
        $this->line("import laravel from 'laravel-vite-plugin';");
        $this->line("import vue from '@vitejs/plugin-vue';");
        $this->line("import tailwindcss from '@tailwindcss/vite';");
        $this->line('');
        $this->line("export default defineConfig({");
        $this->line("    plugins: [");
        $this->line("        laravel({");
        $this->line("            input: ['resources/css/app.css', 'resources/js/app.js'],");
        $this->line("            refresh: true,");
        $this->line("        }),");
        $this->line("        vue(),");
        $this->line("        tailwindcss(),");
        $this->line("    ],");
        $this->line("});");
    }

    /**
     * Set up admin authentication guard.
     */
    protected function setupAdminGuard(): void
    {
        $this->info('Setting up admin authentication guard...');

        $authConfigPath = config_path('auth.php');

        if (File::exists($authConfigPath)) {
            $authConfig = include $authConfigPath;

            // Add admin guard if it doesn't exist
            if (!isset($authConfig['guards']['admin'])) {
                $this->info('Adding admin guard to auth.php...');

                $authContent = File::get($authConfigPath);

                // Add admin guard after web guard
                $webGuardPattern = "/'web' => \[\s*'driver' => 'session',\s*'provider' => 'users',\s*\],/";

                if (preg_match($webGuardPattern, $authContent)) {
                    $adminGuard = "\n        'admin' => [\n            'driver' => 'session',\n            'provider' => 'users',\n        ],";

                    $authContent = preg_replace(
                        $webGuardPattern,
                        "'web' => [\n            'driver' => 'session',\n            'provider' => 'users',\n        ],$adminGuard",
                        $authContent
                    );

                    File::put($authConfigPath, $authContent);
                    $this->line('✅ Admin guard added to auth.php');
                } else {
                    $this->warn('⚠️  Could not automatically add admin guard. Please add manually:');
                    $this->line("'admin' => ['driver' => 'session', 'provider' => 'users'],");
                }
            } else {
                $this->line('✅ Admin guard already exists in auth.php');
            }
        } else {
            $this->warn('⚠️  auth.php not found. Please ensure Laravel is properly installed.');
        }
    }

    /**
     * Set up admin panel assets.
     */
    protected function setupAdminAssets(): void
    {
        $this->info('Setting up admin panel assets...');

        // Create admin panel entry files
        $this->createAdminAssetFiles();

        // Update vite config to include admin assets
        $this->updateViteConfigForAssets();
    }

    /**
     * Create admin panel asset entry files.
     */
    protected function createAdminAssetFiles(): void
    {
        // Create JS entry file
        $jsPath = resource_path('js/admin-panel.js');
        $jsContent = "// Admin Panel Entry Point\nimport '../../packages/jerthedev/admin-panel/resources/js/app.js';";

        if (!File::exists($jsPath)) {
            File::put($jsPath, $jsContent);
            $this->line('✅ Created resources/js/admin-panel.js');
        } else {
            $this->line('✅ resources/js/admin-panel.js already exists');
        }

        // Create CSS entry file
        $cssPath = resource_path('css/admin-panel.css');
        $cssContent = "/* Admin Panel Styles - Tailwind v4 Compatible */\n@config \"../../tailwind.config.js\";\n\n/* Import the admin panel styles from the package */\n@import '../../packages/jerthedev/admin-panel/resources/css/admin.css';";

        if (!File::exists($cssPath)) {
            File::put($cssPath, $cssContent);
            $this->line('✅ Created resources/css/admin-panel.css');
        } else {
            $this->line('✅ resources/css/admin-panel.css already exists');
        }
    }

    /**
     * Update vite config to include admin panel assets.
     */
    protected function updateViteConfigForAssets(): void
    {
        $viteConfigPath = base_path('vite.config.js');

        if (File::exists($viteConfigPath)) {
            $viteContent = File::get($viteConfigPath);

            // Check if admin assets are already included
            if (strpos($viteContent, 'admin-panel.js') === false) {
                $this->info('Updating vite.config.js to include admin panel assets...');

                // Add admin assets to input array
                $pattern = "/input:\s*\[(.*?)\]/s";

                if (preg_match($pattern, $viteContent, $matches)) {
                    $currentInputs = $matches[1];

                    // Add admin assets if not present
                    if (strpos($currentInputs, 'admin-panel') === false) {
                        $newInputs = trim($currentInputs, " \n\r\t,") . ",\n                'resources/css/admin-panel.css',\n                'resources/js/admin-panel.js'";

                        $viteContent = preg_replace($pattern, "input: [$newInputs]", $viteContent);
                        File::put($viteConfigPath, $viteContent);
                        $this->line('✅ Updated vite.config.js with admin panel assets');
                    } else {
                        $this->line('✅ Admin panel assets already in vite.config.js');
                    }
                } else {
                    $this->warn('⚠️  Could not automatically update vite.config.js');
                }
            } else {
                $this->line('✅ Admin panel assets already configured in vite.config.js');
            }
        } else {
            $this->warn('⚠️  vite.config.js not found');
        }
    }

    /**
     * Display success message with next steps.
     */
    protected function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->info('🎉 JTD AdminPanel installed successfully!');
        $this->newLine();
        $this->info('✅ Installation Complete - Ready to Use!');
        $this->line('   • Inertia.js backend service: Ready');
        $this->line('   • Admin directory structure: Created');
        $this->line('   • Resource auto-discovery: Enabled');
        $this->line('   • AdminServiceProvider: ' . ($this->option('create-service-provider') ? 'Created' : 'Not needed (auto-discovery)'));
        $this->line('   • Pre-built assets: ' . ($this->option('no-assets') ? 'Skipped' : 'Published'));
        $this->line('   • Authentication guard: Configured');
        $this->line('   • Database migrations: ' . ($this->option('no-migrate') ? 'Skipped' : 'Completed'));
        $this->newLine();

        $this->info('🚀 Next Steps:');
        $this->line('1. Create admin user: php artisan admin-panel:user');
        $this->line('2. Create your first resource: php artisan admin-panel:resource UserResource');
        $this->line('3. Visit ' . rtrim(config('app.url'), '/') . '/' . ltrim(config('admin-panel.path', 'admin'), '/') . ' to access the admin panel');
        $this->newLine();

        $this->comment('💡 Resources in app/Admin/Resources/ are automatically discovered!');
        $this->comment('💡 No frontend setup required - package includes pre-built assets!');
        $this->info('🔧 Run "php artisan admin-panel:doctor" to verify installation');
        $this->newLine();
        $this->info('📚 Documentation: https://jerthedev.com/docs/admin-panel');
    }

    /**
     * Ensure Inertia.js backend service is installed.
     */
    protected function ensureInertiaInstalled(): void
    {
        // Check if Inertia is already installed
        if (class_exists(\Inertia\Inertia::class)) {
            $this->info('✅ Inertia.js backend service already installed');
            return;
        }

        $this->info('📦 Installing Inertia.js backend service...');
        $this->warn('Note: This installs only the backend service, no frontend setup required.');

        // Install Inertia Laravel package
        $process = new \Symfony\Component\Process\Process([
            'composer', 'require', 'inertiajs/inertia-laravel'
        ], base_path());

        $process->setTimeout(300); // 5 minutes timeout

        try {
            $process->mustRun(function ($type, $buffer) {
                if (\Symfony\Component\Process\Process::ERR === $type) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

            $this->info('✅ Inertia.js backend service installed successfully');
        } catch (\Symfony\Component\Process\ProcessFailedException $exception) {
            $this->error('❌ Failed to install Inertia.js backend service');
            $this->error($exception->getMessage());
            $this->warn('Please install manually: composer require inertiajs/inertia-laravel');
        }
    }

    /**
     * Ensure Ziggy route helper is installed.
     */
    protected function ensureZiggyInstalled(): void
    {
        // Check if Ziggy is already installed
        if (class_exists(\Tightenco\Ziggy\ZiggyServiceProvider::class)) {
            $this->info('✅ Ziggy route helper already installed');
            return;
        }

        $this->info('📦 Installing Ziggy route helper...');
        $this->warn('Note: Required for JavaScript route generation in admin panel.');

        try {
            $process = new Process(['composer', 'require', 'tightenco/ziggy'], base_path());
            $process->setTimeout(300);

            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }
            });

            $this->info('✅ Ziggy route helper installed successfully');
            $this->info('💡 Ziggy routes available via @routes directive in admin panel');
        } catch (\Symfony\Component\Process\ProcessFailedException $exception) {
            $this->error('❌ Failed to install Ziggy route helper');
            $this->error($exception->getMessage());
            $this->warn('Please install manually: composer require tightenco/ziggy');
        }
    }



    /**
     * Publish pre-built assets from package.
     */
    protected function publishPreBuiltAssets(): void
    {
        $this->info('📦 Publishing pre-built assets...');

        try {
            $this->call('vendor:publish', [
                '--tag' => 'admin-panel-assets',
                '--force' => $this->option('force'),
            ]);

            $this->info('✅ Pre-built assets published successfully');
            $this->line('   Assets available at: public/vendor/admin-panel/');
        } catch (\Exception $exception) {
            $this->error('❌ Failed to publish pre-built assets');
            $this->error($exception->getMessage());
            $this->warn('Please publish manually: php artisan vendor:publish --tag=admin-panel-assets');
        }
    }
}
