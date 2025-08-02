<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Support\ResourceRegistry;

/**
 * List Resources Command
 * 
 * Lists all discovered and registered admin panel resources with
 * validation and statistics.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Console\Commands
 */
class ListResourcesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:resources 
                            {--validate : Validate all resources}
                            {--stats : Show resource statistics}
                            {--group= : Filter by resource group}';

    /**
     * The console command description.
     */
    protected $description = 'List all admin panel resources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $adminPanel = app(AdminPanel::class);
        $registry = new ResourceRegistry();
        $resources = $adminPanel->getResources();

        if ($resources->isEmpty()) {
            $this->warn('No resources found.');
            $this->line('');
            $this->line('To create a resource, run:');
            $this->line('php artisan admin-panel:resource UserResource');
            return self::SUCCESS;
        }

        // Filter by group if specified
        if ($group = $this->option('group')) {
            $resources = $resources->filter(function ($resource) use ($group) {
                return ($resource::$group ?? 'Default') === $group;
            });
        }

        $this->displayResources($resources);

        if ($this->option('validate')) {
            $this->validateResources($resources, $registry);
        }

        if ($this->option('stats')) {
            $this->displayStatistics($resources, $registry);
        }

        return self::SUCCESS;
    }

    /**
     * Display the resources table.
     */
    protected function displayResources($resources): void
    {
        $this->info('Admin Panel Resources:');
        $this->line('');

        $headers = ['Resource', 'URI Key', 'Model', 'Group', 'Searchable', 'Navigation'];
        $rows = [];

        foreach ($resources as $resource) {
            $rows[] = [
                $resource::singularLabel(),
                $resource::uriKey(),
                class_basename($resource::model()),
                $resource::$group ?? 'Default',
                $resource::$globallySearchable ? '✓' : '✗',
                $resource::availableForNavigation(request()) ? '✓' : '✗',
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * Validate all resources and display errors.
     */
    protected function validateResources($resources, ResourceRegistry $registry): void
    {
        $this->line('');
        $this->info('Validating resources...');

        $errors = $registry->validateAllResources($resources);

        if (empty($errors)) {
            $this->info('✅ All resources are valid!');
        } else {
            $this->error('❌ Found validation errors:');
            $this->line('');

            foreach ($errors as $resourceClass => $resourceErrors) {
                $this->line("<fg=red>Resource:</fg=red> {$resourceClass}");
                foreach ($resourceErrors as $error) {
                    $this->line("  • {$error}");
                }
                $this->line('');
            }
        }
    }

    /**
     * Display resource statistics.
     */
    protected function displayStatistics($resources, ResourceRegistry $registry): void
    {
        $this->line('');
        $this->info('Resource Statistics:');

        $stats = $registry->getResourceStatistics($resources);

        $this->line('');
        $this->line("Total Resources: <fg=green>{$stats['total']}</fg=green>");
        $this->line("Searchable Resources: <fg=green>{$stats['searchable']}</fg=green>");
        $this->line("Navigation Resources: <fg=green>{$stats['navigable']}</fg=green>");
        $this->line("Total Groups: <fg=green>" . count($stats['groups']) . "</fg=green>");
        $this->line("Total Models: <fg=green>" . count($stats['models']) . "</fg=green>");

        if (! empty($stats['groupCounts'])) {
            $this->line('');
            $this->line('Resources by Group:');
            foreach ($stats['groupCounts'] as $group => $count) {
                $this->line("  {$group}: <fg=green>{$count}</fg=green>");
            }
        }

        if (! empty($stats['models'])) {
            $this->line('');
            $this->line('Associated Models:');
            foreach ($stats['models'] as $model) {
                $this->line("  • " . class_basename($model));
            }
        }
    }
}
