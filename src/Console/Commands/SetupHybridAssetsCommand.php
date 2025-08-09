<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Support\ViteConfigHelper;

/**
 * Setup Hybrid Assets Command
 *
 * Sets up the hybrid asset system for custom admin pages,
 * creating necessary directories and configuration files.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class SetupHybridAssetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:setup-hybrid-assets 
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Set up the hybrid asset system for custom admin pages';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up hybrid asset system for JTD Admin Panel...');

        // Create admin-pages directory
        $this->createAdminPagesDirectory();

        // Create Vite configuration
        $this->createViteConfiguration();

        // Create entry point
        $this->createEntryPoint();

        // Create sample component (optional)
        if ($this->confirm('Create a sample custom page component?', true)) {
            $this->createSampleComponent();
        }

        // Update package.json scripts
        $this->updatePackageScripts();

        $this->info('✅ Hybrid asset system setup complete!');
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Run: npm install (if you haven\'t already)');
        $this->line('2. Run: npm run build-admin-pages (to build custom pages)');
        $this->line('3. Create custom pages in resources/js/admin-pages/');
        $this->line('4. Use: php artisan admin-panel:make-page <name> (to scaffold new pages)');

        return self::SUCCESS;
    }

    /**
     * Create the admin-pages directory structure.
     */
    protected function createAdminPagesDirectory(): void
    {
        $adminPagesPath = base_path('resources/js/admin-pages');

        if (!File::exists($adminPagesPath)) {
            File::makeDirectory($adminPagesPath, 0755, true);
            $this->info('✅ Created directory: resources/js/admin-pages');
        } else {
            $this->line('📁 Directory already exists: resources/js/admin-pages');
        }
    }

    /**
     * Create Vite configuration for admin pages.
     */
    protected function createViteConfiguration(): void
    {
        $configPath = base_path('vite.admin-pages.config.js');
        
        if (File::exists($configPath) && !$this->option('force')) {
            $this->line('📄 Vite config already exists: vite.admin-pages.config.js');
            return;
        }

        $helper = new ViteConfigHelper();
        $configContent = $helper->generateConfigFile([
            'include_entry' => true,
            'output_dir' => 'public/build/admin-pages',
        ]);

        File::put($configPath, $configContent);
        $this->info('✅ Created Vite configuration: vite.admin-pages.config.js');
    }

    /**
     * Create the admin pages entry point.
     */
    protected function createEntryPoint(): void
    {
        $entryPath = base_path('resources/js/admin-pages/app.js');
        
        if (File::exists($entryPath) && !$this->option('force')) {
            $this->line('📄 Entry point already exists: resources/js/admin-pages/app.js');
            return;
        }

        $helper = new ViteConfigHelper();
        $success = $helper->createAdminPagesEntry($entryPath);

        if ($success) {
            $this->info('✅ Created entry point: resources/js/admin-pages/app.js');
        } else {
            $this->error('❌ Failed to create entry point');
        }
    }

    /**
     * Create a sample component.
     */
    protected function createSampleComponent(): void
    {
        $componentPath = base_path('resources/js/admin-pages/SamplePage.vue');
        
        if (File::exists($componentPath) && !$this->option('force')) {
            $this->line('📄 Sample component already exists: resources/js/admin-pages/SamplePage.vue');
            return;
        }

        $componentContent = <<<VUE
<template>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ page.title }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                This is a sample custom page created by the hybrid asset system.
            </p>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">
                        Custom Page Working!
                    </h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p>This component is loaded from resources/js/admin-pages/SamplePage.vue</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display custom data if available -->
        <div v-if="data && Object.keys(data).length > 0" class="mt-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Custom Data</h2>
            <div class="bg-gray-50 rounded-lg p-4">
                <pre class="text-sm text-gray-800">{{ JSON.stringify(data, null, 2) }}</pre>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    page: Object,
    fields: Array,
    actions: Array,
    metrics: Array,
    data: Object,
})

console.log('Sample custom page component loaded from app directory!')
</script>
VUE;

        File::put($componentPath, $componentContent);
        $this->info('✅ Created sample component: resources/js/admin-pages/SamplePage.vue');
    }

    /**
     * Update package.json scripts.
     */
    protected function updatePackageScripts(): void
    {
        $packageJsonPath = base_path('package.json');
        
        if (!File::exists($packageJsonPath)) {
            $this->warn('⚠️  package.json not found. You\'ll need to add build scripts manually.');
            return;
        }

        $packageJson = json_decode(File::get($packageJsonPath), true);
        
        if (!isset($packageJson['scripts'])) {
            $packageJson['scripts'] = [];
        }

        $helper = new ViteConfigHelper();
        $newScripts = $helper->generatePackageScripts();
        
        $updated = false;
        foreach ($newScripts as $scriptName => $scriptCommand) {
            if (!isset($packageJson['scripts'][$scriptName])) {
                $packageJson['scripts'][$scriptName] = $scriptCommand;
                $updated = true;
            }
        }

        if ($updated) {
            File::put($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->info('✅ Updated package.json with admin pages build scripts');
        } else {
            $this->line('📄 package.json scripts already up to date');
        }
    }
}
