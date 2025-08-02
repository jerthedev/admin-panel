<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Clear Cache Command
 * 
 * Clears the admin panel resource discovery cache and other cached data.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Console\Commands
 */
class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:clear-cache 
                            {--resources : Clear only resource discovery cache}
                            {--all : Clear all admin panel caches}';

    /**
     * The console command description.
     */
    protected $description = 'Clear admin panel caches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $adminPanel = app(AdminPanel::class);

        if ($this->option('resources') || $this->option('all')) {
            $this->clearResourceCache($adminPanel);
        }

        if ($this->option('all')) {
            $this->clearAllCaches();
        }

        if (! $this->option('resources') && ! $this->option('all')) {
            // Default: clear resource cache
            $this->clearResourceCache($adminPanel);
        }

        $this->info('✅ Admin panel caches cleared successfully!');

        return self::SUCCESS;
    }

    /**
     * Clear the resource discovery cache.
     */
    protected function clearResourceCache(AdminPanel $adminPanel): void
    {
        $adminPanel->clearResourceCache();
        $this->line('Resource discovery cache cleared');
    }

    /**
     * Clear all admin panel caches.
     */
    protected function clearAllCaches(): void
    {
        // Clear Laravel's general cache
        $this->call('cache:clear');
        $this->line('General application cache cleared');

        // Clear view cache
        $this->call('view:clear');
        $this->line('View cache cleared');

        // Clear config cache
        $this->call('config:clear');
        $this->line('Configuration cache cleared');
    }
}
