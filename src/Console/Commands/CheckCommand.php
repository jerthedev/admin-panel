<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Check Admin Panel Installation Command
 *
 * Verifies that the admin panel is properly installed and configured.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class CheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:check';

    /**
     * The console command description.
     */
    protected $description = 'Check admin panel installation and configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking Admin Panel Installation...');
        $this->newLine();

        $checks = [
            'Configuration' => $this->checkConfiguration(),
            'Database' => $this->checkDatabase(),
            'Cache' => $this->checkCache(),
            'Storage' => $this->checkStorage(),
            'Assets' => $this->checkAssets(),
            'Resources' => $this->checkResources(),
            'Routes' => $this->checkRoutes(),
        ];

        $passed = 0;
        $total = count($checks);

        foreach ($checks as $name => $result) {
            if ($result['status']) {
                $this->line("✅ {$name}: {$result['message']}");
                $passed++;
            } else {
                $this->line("❌ {$name}: {$result['message']}");
            }
        }

        $this->newLine();

        if ($passed === $total) {
            $this->info("🎉 All checks passed! ({$passed}/{$total})");
            $this->info('Your admin panel is ready to use.');
            return self::SUCCESS;
        } else {
            $this->error("⚠️  Some checks failed. ({$passed}/{$total} passed)");
            $this->error('Please fix the issues above before using the admin panel.');
            return self::FAILURE;
        }
    }

    /**
     * Check configuration.
     */
    protected function checkConfiguration(): array
    {
        $configPath = config_path('admin-panel.php');
        
        if (!File::exists($configPath)) {
            return [
                'status' => false,
                'message' => 'Configuration file not found. Run: php artisan vendor:publish --tag="admin-panel-config"'
            ];
        }

        return [
            'status' => true,
            'message' => 'Configuration file exists'
        ];
    }

    /**
     * Check database connection.
     */
    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return [
                'status' => true,
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check cache functionality.
     */
    protected function checkCache(): array
    {
        try {
            $testKey = 'admin_panel_check_' . time();
            $testValue = 'test';
            
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            if ($retrieved === $testValue) {
                return [
                    'status' => true,
                    'message' => 'Cache is working properly'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'Cache test failed'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Cache error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check storage permissions.
     */
    protected function checkStorage(): array
    {
        $storagePath = storage_path('app');
        
        if (!File::exists($storagePath)) {
            return [
                'status' => false,
                'message' => 'Storage directory does not exist'
            ];
        }

        if (!File::isWritable($storagePath)) {
            return [
                'status' => false,
                'message' => 'Storage directory is not writable'
            ];
        }

        return [
            'status' => true,
            'message' => 'Storage is accessible and writable'
        ];
    }

    /**
     * Check compiled assets.
     */
    protected function checkAssets(): array
    {
        $manifestPath = public_path('build/manifest.json');
        
        if (!File::exists($manifestPath)) {
            return [
                'status' => false,
                'message' => 'Assets not compiled. Run: npm run build'
            ];
        }

        return [
            'status' => true,
            'message' => 'Assets are compiled'
        ];
    }

    /**
     * Check resources.
     */
    protected function checkResources(): array
    {
        $adminPanel = app(AdminPanel::class);
        $resources = $adminPanel->getResources();
        
        return [
            'status' => true,
            'message' => "Found {$resources->count()} registered resources"
        ];
    }

    /**
     * Check routes.
     */
    protected function checkRoutes(): array
    {
        $routes = collect(\Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->getName() ?? '', 'admin-panel.');
        });

        if ($routes->isEmpty()) {
            return [
                'status' => false,
                'message' => 'Admin panel routes not registered'
            ];
        }

        return [
            'status' => true,
            'message' => "Found {$routes->count()} admin panel routes"
        ];
    }
}
