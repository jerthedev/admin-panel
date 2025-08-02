<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Admin Panel Doctor Command
 *
 * Comprehensive diagnostic tool for the admin panel installation,
 * similar to "flutter doctor" - checks all aspects of the setup.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class DoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:doctor {--detailed : Show detailed information}';

    /**
     * The console command description.
     */
    protected $description = 'Run comprehensive diagnostics on admin panel installation';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🩺 Admin Panel Doctor');
        $this->info('Running comprehensive diagnostics...');
        $this->newLine();

        $checks = [
            'Package Installation' => $this->checkPackageInstallation(),
            'Configuration' => $this->checkConfiguration(),
            'Authentication Setup' => $this->checkAuthentication(),
            'Database Connection' => $this->checkDatabase(),
            'Routes Registration' => $this->checkRoutes(),
            'Assets & Frontend' => $this->checkAssets(),
            'Permissions & Security' => $this->checkPermissions(),
            'Cache & Storage' => $this->checkCacheStorage(),
            'Admin Users' => $this->checkAdminUsers(),
        ];

        $passed = 0;
        $warnings = 0;
        $errors = 0;

        foreach ($checks as $category => $results) {
            $this->displayCategory($category, $results);

            foreach ($results as $result) {
                if ($result['status'] === 'pass') $passed++;
                elseif ($result['status'] === 'warning') $warnings++;
                elseif ($result['status'] === 'info') $passed++; // Info counts as passed
                else $errors++;
            }
        }

        $this->newLine();
        $this->displaySummary($passed, $warnings, $errors);

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Check package installation.
     */
    protected function checkPackageInstallation(): array
    {
        $checks = [];

        // Check if package is installed
        $composerLock = base_path('composer.lock');
        $packageInstalled = false;

        if (File::exists($composerLock)) {
            $lockData = json_decode(File::get($composerLock), true);
            $packages = array_merge($lockData['packages'] ?? [], $lockData['packages-dev'] ?? []);

            foreach ($packages as $package) {
                if ($package['name'] === 'jerthedev/admin-panel') {
                    $packageInstalled = true;
                    $checks[] = [
                        'name' => 'Package installed via Composer',
                        'status' => 'pass',
                        'message' => "Version: {$package['version']}",
                    ];
                    break;
                }
            }
        }

        if (!$packageInstalled) {
            $checks[] = [
                'name' => 'Package installation',
                'status' => 'error',
                'message' => 'Package not found in composer.lock',
                'fix' => 'Run: composer require jerthedev/admin-panel',
            ];
        }

        // Check service provider registration (package discovery is preferred)
        $providers = config('app.providers', []);
        $manuallyRegistered = in_array('JTD\\AdminPanel\\AdminPanelServiceProvider', $providers);

        $checks[] = [
            'name' => 'Service provider registration',
            'status' => 'pass', // Package discovery is the correct approach
            'message' => $manuallyRegistered ? 'Manually registered (unnecessary)' : 'Using package discovery (recommended)',
            'fix' => $manuallyRegistered ? 'Remove from config/app.php (package discovery handles this)' : null,
        ];

        // Check admin directory structure
        $adminPath = base_path('app/Admin');
        $checks[] = [
            'name' => 'Admin directory structure',
            'status' => File::exists($adminPath) ? 'pass' : 'warning',
            'message' => File::exists($adminPath) ? 'Admin directory exists' : 'Admin directory missing',
            'fix' => 'Run: php artisan admin-panel:install',
        ];

        // Check resource auto-discovery
        $resourcesPath = base_path('app/Admin/Resources');
        $autoDiscovery = config('admin-panel.resources.auto_discovery', true);
        $checks[] = [
            'name' => 'Resource auto-discovery',
            'status' => $autoDiscovery ? 'pass' : 'info',
            'message' => $autoDiscovery ? 'Auto-discovery enabled' : 'Auto-discovery disabled',
            'fix' => $autoDiscovery ? null : 'Enable in config/admin-panel.php',
        ];

        // Check AdminServiceProvider (optional)
        $adminProviderPath = base_path('app/Providers/AdminServiceProvider.php');
        $checks[] = [
            'name' => 'AdminServiceProvider',
            'status' => File::exists($adminProviderPath) ? 'info' : 'info',
            'message' => File::exists($adminProviderPath) ? 'Manual registration available' : 'Using auto-discovery (recommended)',
            'fix' => null,
        ];

        return $checks;
    }

    /**
     * Check configuration.
     */
    protected function checkConfiguration(): array
    {
        $checks = [];

        // Check config file
        $configPath = config_path('admin-panel.php');
        $checks[] = [
            'name' => 'Configuration file',
            'status' => File::exists($configPath) ? 'pass' : 'error',
            'message' => File::exists($configPath) ? 'Published' : 'Not published',
            'fix' => 'Run: php artisan vendor:publish --tag="admin-panel-config"',
        ];

        // Check critical config values
        $path = config('admin-panel.path');
        $checks[] = [
            'name' => 'Admin panel path',
            'status' => $path ? 'pass' : 'warning',
            'message' => $path ? "Path: {$path}" : 'Using default path',
        ];

        return $checks;
    }

    /**
     * Check authentication setup.
     */
    protected function checkAuthentication(): array
    {
        $checks = [];

        // Check admin guard
        $guards = config('auth.guards', []);
        $adminGuard = config('admin-panel.auth.guard', 'admin');

        $checks[] = [
            'name' => 'Admin authentication guard',
            'status' => isset($guards[$adminGuard]) ? 'pass' : 'error',
            'message' => isset($guards[$adminGuard]) ? "Guard '{$adminGuard}' configured" : "Guard '{$adminGuard}' not found",
            'fix' => 'Add admin guard to config/auth.php or run install command',
        ];

        // Check user model
        $userModel = config('admin-panel.auth.user_model');
        $checks[] = [
            'name' => 'User model',
            'status' => class_exists($userModel) ? 'pass' : 'error',
            'message' => class_exists($userModel) ? "Model: {$userModel}" : "Model not found: {$userModel}",
        ];

        return $checks;
    }

    /**
     * Check database connection.
     */
    protected function checkDatabase(): array
    {
        $checks = [];

        try {
            DB::connection()->getPdo();
            $checks[] = [
                'name' => 'Database connection',
                'status' => 'pass',
                'message' => 'Connected to ' . DB::connection()->getDatabaseName(),
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'name' => 'Database connection',
                'status' => 'error',
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }

        // Check users table
        try {
            $userModel = config('admin-panel.auth.user_model');
            if (class_exists($userModel)) {
                $model = new $userModel();
                $tableName = $model->getTable();

                if (DB::getSchemaBuilder()->hasTable($tableName)) {
                    $checks[] = [
                        'name' => 'Users table',
                        'status' => 'pass',
                        'message' => "Table '{$tableName}' exists",
                    ];
                } else {
                    $checks[] = [
                        'name' => 'Users table',
                        'status' => 'error',
                        'message' => "Table '{$tableName}' not found",
                        'fix' => 'Run: php artisan migrate',
                    ];
                }
            }
        } catch (\Exception $e) {
            $checks[] = [
                'name' => 'Users table check',
                'status' => 'warning',
                'message' => 'Could not verify users table',
            ];
        }

        return $checks;
    }

    /**
     * Check routes registration.
     */
    protected function checkRoutes(): array
    {
        $checks = [];

        $adminRoutes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->getName() ?? '', 'admin-panel.');
        });

        $checks[] = [
            'name' => 'Admin panel routes',
            'status' => $adminRoutes->count() > 0 ? 'pass' : 'error',
            'message' => "Found {$adminRoutes->count()} admin panel routes",
        ];

        // Check specific important routes
        $importantRoutes = ['admin-panel.login', 'admin-panel.dashboard'];
        foreach ($importantRoutes as $routeName) {
            $routeExists = Route::has($routeName);
            $checks[] = [
                'name' => "Route: {$routeName}",
                'status' => $routeExists ? 'pass' : 'error',
                'message' => $routeExists ? 'Registered' : 'Not found',
            ];
        }

        return $checks;
    }

    /**
     * Check self-contained package assets.
     */
    protected function checkAssets(): array
    {
        $checks = [];

        // Check if pre-built assets are published
        $assetsPath = public_path('vendor/admin-panel/assets');
        $checks[] = [
            'name' => 'Published assets',
            'status' => File::exists($assetsPath) ? 'pass' : 'error',
            'message' => File::exists($assetsPath) ? 'Pre-built assets published' : 'Assets not published',
            'fix' => 'Run: php artisan vendor:publish --tag=admin-panel-assets',
        ];

        // Check package manifest
        $manifestPath = public_path('vendor/admin-panel/.vite/manifest.json');
        $checks[] = [
            'name' => 'Asset manifest',
            'status' => File::exists($manifestPath) ? 'pass' : 'warning',
            'message' => File::exists($manifestPath) ? 'Manifest available' : 'Manifest missing',
            'fix' => 'Run: php artisan vendor:publish --tag=admin-panel-assets --force',
        ];

        // Check main JS asset
        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true);
            $mainJs = $manifest['resources/js/app.js']['file'] ?? null;

            if ($mainJs) {
                $jsPath = public_path("vendor/admin-panel/{$mainJs}");
                $checks[] = [
                    'name' => 'Main JavaScript',
                    'status' => File::exists($jsPath) ? 'pass' : 'error',
                    'message' => File::exists($jsPath) ? "JS asset available ({$mainJs})" : 'Main JS missing',
                    'fix' => 'Run: php artisan vendor:publish --tag=admin-panel-assets --force',
                ];
            }
        }

        // Check main CSS asset
        if (File::exists($manifestPath)) {
            $manifest = json_decode(File::get($manifestPath), true);
            $mainCss = $manifest['resources/css/admin.css']['file'] ?? null;

            if ($mainCss) {
                $cssPath = public_path("vendor/admin-panel/{$mainCss}");
                $checks[] = [
                    'name' => 'Main Stylesheet',
                    'status' => File::exists($cssPath) ? 'pass' : 'error',
                    'message' => File::exists($cssPath) ? "CSS asset available ({$mainCss})" : 'Main CSS missing',
                    'fix' => 'Run: php artisan vendor:publish --tag=admin-panel-assets --force',
                ];
            }
        }

        return $checks;
    }

    /**
     * Check permissions and security.
     */
    protected function checkPermissions(): array
    {
        $checks = [];

        // Check storage permissions
        $storagePath = storage_path('app');
        $checks[] = [
            'name' => 'Storage permissions',
            'status' => is_writable($storagePath) ? 'pass' : 'error',
            'message' => is_writable($storagePath) ? 'Writable' : 'Not writable',
            'fix' => 'Fix storage permissions: chmod -R 755 storage/',
        ];

        return $checks;
    }

    /**
     * Check cache and storage.
     */
    protected function checkCacheStorage(): array
    {
        $checks = [];

        try {
            $testKey = 'admin_panel_doctor_' . time();
            Cache::put($testKey, 'test', 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            $checks[] = [
                'name' => 'Cache functionality',
                'status' => $retrieved === 'test' ? 'pass' : 'error',
                'message' => $retrieved === 'test' ? 'Working' : 'Not working',
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'name' => 'Cache functionality',
                'status' => 'error',
                'message' => 'Cache error: ' . $e->getMessage(),
            ];
        }

        return $checks;
    }

    /**
     * Check admin users.
     */
    protected function checkAdminUsers(): array
    {
        $checks = [];

        try {
            $userModel = config('admin-panel.auth.user_model');
            if (class_exists($userModel)) {
                $adminUsers = $userModel::where('is_admin', true)->count();

                $checks[] = [
                    'name' => 'Admin users',
                    'status' => $adminUsers > 0 ? 'pass' : 'warning',
                    'message' => "Found {$adminUsers} admin users",
                    'fix' => 'Create admin user: php artisan admin-panel:user',
                ];
            }
        } catch (\Exception $e) {
            $checks[] = [
                'name' => 'Admin users check',
                'status' => 'warning',
                'message' => 'Could not check admin users',
            ];
        }

        return $checks;
    }

    /**
     * Display category results.
     */
    protected function displayCategory(string $category, array $results): void
    {
        $this->info("📋 {$category}");

        foreach ($results as $result) {
            $icon = match($result['status']) {
                'pass' => '✅',
                'warning' => '⚠️ ',
                'error' => '❌',
                default => '❓',
            };

            $this->line("  {$icon} {$result['name']}: {$result['message']}");

            if ($this->option('detailed') && isset($result['fix'])) {
                $this->line("     💡 Fix: {$result['fix']}");
            }
        }

        $this->newLine();
    }

    /**
     * Display summary.
     */
    protected function displaySummary(int $passed, int $warnings, int $errors): void
    {
        $total = $passed + $warnings + $errors;

        $this->info('📊 Summary');
        $this->line("✅ Passed: {$passed}");
        $this->line("⚠️  Warnings: {$warnings}");
        $this->line("❌ Errors: {$errors}");
        $this->line("📈 Total: {$total}");

        if ($errors === 0 && $warnings === 0) {
            $this->info('🎉 All checks passed! Your admin panel is ready to use.');
        } elseif ($errors === 0) {
            $this->warn('⚠️  Some warnings found, but admin panel should work.');
        } else {
            $this->error('❌ Critical errors found. Please fix them before using the admin panel.');
        }
    }
}
