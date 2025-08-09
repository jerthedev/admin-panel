<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Make Page Command
 *
 * Enhanced artisan command for creating custom admin panel pages with
 * multi-component support and auto-registration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Console\Commands
 */
class MakePageCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:make-page
                            {name : The name of the page}
                            {--components= : Comma-separated list of component names for multi-component pages}
                            {--group= : The menu group for the page}
                            {--icon= : The icon for the page}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new admin panel page with Vue components';

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
        $name = $this->argument('name');
        $componentsOption = $this->option('components');
        $group = $this->option('group');
        $icon = $this->option('icon');
        $force = $this->option('force');

        // Validate page name
        if (!$this->isValidPageName($name)) {
            $this->error("Invalid page name: {$name}. Page names must be PascalCase and start with a letter.");
            return 1;
        }

        // Parse components
        $components = [];
        if ($componentsOption) {
            $components = array_map('trim', explode(',', $componentsOption));

            // Validate component names
            foreach ($components as $component) {
                if (!$this->isValidComponentName($component)) {
                    $this->error("Invalid component name: {$component}. Component names must be PascalCase and start with a letter.");
                    return 1;
                }
            }
        }

        // Get paths
        $appPath = app_path();
        $resourcesPath = resource_path();

        // Create the page
        $result = $this->createPage($name, $appPath, $resourcesPath, $components, $force, $group, $icon);

        if (!$result) {
            $this->error("Failed to create page: {$name}");
            return 1;
        }

        // Auto-register the page
        $registrationResult = $this->autoRegisterPage($name, $appPath);
        if (!$registrationResult) {
            $this->warn("Page created but auto-registration failed. You may need to manually register the page.");
        }

        $this->info("Page {$name} created successfully!");

        if (!empty($components)) {
            $this->info("Created " . count($components) . " Vue components:");
            foreach ($components as $component) {
                $this->line("  - {$name}{$component}.vue");
            }
        } else {
            $this->info("Created Vue component: {$name}.vue");
        }

        return 0;
    }

    /**
     * Create a page with PHP class and Vue components.
     */
    public function createPage(
        string $name,
        string $appPath,
        string $resourcesPath,
        array $components = [],
        bool $force = false,
        ?string $group = null,
        ?string $icon = null
    ): bool {
        $pageClassName = $name . 'Page';
        $phpFile = $appPath . '/Admin/Pages/' . $pageClassName . '.php';

        // Check if page already exists
        if ($this->files->exists($phpFile) && !$force) {
            return false;
        }

        // Ensure directories exist
        if (!$this->files->exists(dirname($phpFile))) {
            $this->files->makeDirectory(dirname($phpFile), 0755, true);
        }
        if (!$this->files->exists($resourcesPath . '/js/admin-pages')) {
            $this->files->makeDirectory($resourcesPath . '/js/admin-pages', 0755, true);
        }

        // Generate PHP class
        $phpContent = $this->generatePhpClass($name, $components, $group, $icon);
        $this->files->put($phpFile, $phpContent);

        // Generate Vue components
        if (empty($components)) {
            // Single component
            $vueFile = $resourcesPath . '/js/admin-pages/' . $name . '.vue';
            $vueContent = $this->generateVueComponent($name, $name);
            $this->files->put($vueFile, $vueContent);
        } else {
            // Multi-component
            foreach ($components as $component) {
                $vueFile = $resourcesPath . '/js/admin-pages/' . $name . $component . '.vue';
                $vueContent = $this->generateVueComponent($name, $component);
                $this->files->put($vueFile, $vueContent);
            }
        }

        return true;
    }

    /**
     * Validate page name.
     */
    public function isValidPageName(string $name): bool
    {
        return !empty($name) &&
               preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) &&
               !is_numeric($name[0]);
    }

    /**
     * Validate component name.
     */
    public function isValidComponentName(string $name): bool
    {
        return !empty($name) &&
               preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) &&
               !is_numeric($name[0]);
    }

    /**
     * Generate PHP page class content.
     */
    protected function generatePhpClass(string $name, array $components, ?string $group, ?string $icon): string
    {
        $className = $name . 'Page';
        $title = Str::title(Str::snake($name, ' '));

        // Generate components array
        if (empty($components)) {
            $componentsArray = "['Pages/{$name}']";
        } else {
            $componentsList = array_map(fn($c) => "'Pages/{$name}{$c}'", $components);
            $componentsArray = '[' . implode(', ', $componentsList) . ']';
        }

        $groupProperty = $group ? "'{$group}'" : 'null';
        $iconProperty = $icon ? "'{$icon}'" : 'null';

        return <<<PHP
<?php

declare(strict_types=1);

namespace App\Admin\Pages;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Pages\Page;

/**
 * {$title} Page
 *
 * Custom admin panel page for {$title} management.
 *
 * @author Generated by admin-panel:make-page
 */
class {$className} extends Page
{
    /**
     * The Vue components for this page.
     * First component is primary, others available via routing.
     */
    public static array \$components = {$componentsArray};

    /**
     * The menu group this page belongs to.
     */
    public static ?string \$group = {$groupProperty};

    /**
     * The display title for this page.
     */
    public static ?string \$title = '{$title}';

    /**
     * The icon for this page (Heroicon name).
     */
    public static ?string \$icon = {$iconProperty};

    /**
     * Get the fields for this page.
     */
    public function fields(Request \$request): array
    {
        return [
            Text::make('Example Field')
                ->help('This is an example field. Replace with your own fields.'),
        ];
    }

    /**
     * Get custom data for this page.
     */
    public function data(Request \$request): array
    {
        return [
            'example_data' => 'This is example data for the page.',
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get actions for this page.
     */
    public function actions(Request \$request): array
    {
        return [
            // Add custom actions here
        ];
    }

    /**
     * Determine if the user can view this page.
     */
    public static function authorizedToViewAny(Request \$request): bool
    {
        // Customize authorization logic here
        return \$request->user() !== null;
    }
}
PHP;
    }

    /**
     * Generate Vue component content.
     */
    protected function generateVueComponent(string $pageName, string $componentName): string
    {
        $title = Str::title(Str::snake($pageName, ' '));
        $componentTitle = $componentName === $pageName ? $title : "{$title} - {$componentName}";

        return <<<VUE
<template>
    <div class="p-6 space-y-6">
        <!-- Page Header -->
        <div class="border-b border-gray-200 pb-6">
            <h1 class="text-2xl font-bold text-gray-900">{{ page.title }}</h1>
            <p class="text-sm text-gray-600 mt-1">{$componentTitle} component</p>
        </div>

        <!-- Page Content -->
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">Welcome to {$componentTitle}</h2>

            <div class="space-y-4">
                <p class="text-gray-600">
                    This is a generated {$componentTitle} component. You can customize this template
                    to build your custom page functionality.
                </p>

                <!-- Example Field Display -->
                <div v-if="fields && fields.length > 0" class="space-y-3">
                    <h3 class="text-md font-medium text-gray-800">Available Fields:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="field in fields" :key="field.attribute"
                             class="bg-gray-50 p-3 rounded border">
                            <dt class="text-sm font-medium text-gray-600">{{ field.name }}</dt>
                            <dd class="text-sm text-gray-900 mt-1">{{ field.help || 'No description' }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Example Data Display -->
                <div v-if="data" class="space-y-3">
                    <h3 class="text-md font-medium text-gray-800">Page Data:</h3>
                    <pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto">{{ JSON.stringify(data, null, 2) }}</pre>
                </div>
            </div>
        </div>

        <!-- Development Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-900 mb-2">Development Info</h3>
            <div class="text-xs text-blue-800 space-y-1">
                <p><strong>Component:</strong> {$componentName}</p>
                <p><strong>Page:</strong> {$pageName}</p>
                <p><strong>Location:</strong> resources/js/admin-pages/{$pageName}{$componentName}.vue</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    fields: {
        type: Array,
        default: () => []
    },
    actions: {
        type: Array,
        default: () => []
    },
    data: {
        type: Object,
        default: () => ({})
    }
})

// Log component loading for development
console.log('🎉 {$componentTitle} component loaded successfully!')
console.log('📍 Component location: resources/js/admin-pages/{$pageName}{$componentName}.vue')
</script>
VUE;
    }

    /**
     * Auto-register the page in AdminPanelServiceProvider.
     */
    public function autoRegisterPage(string $name, string $appPath): bool
    {
        $serviceProviderPath = $appPath . '/Providers/AdminPanelServiceProvider.php';

        if (!$this->files->exists($serviceProviderPath)) {
            return false;
        }

        $content = $this->files->get($serviceProviderPath);
        $pageClassName = "\\App\\Admin\\Pages\\{$name}Page::class";

        // Check if already registered
        if (str_contains($content, $pageClassName)) {
            return true; // Already registered
        }

        // AdminPanel import should already be present in AdminPanelServiceProvider
        // If not, something is wrong with the service provider structure

        // Check if pages registration already exists
        if (str_contains($content, 'app(AdminPanel::class)->pages([')) {
            // Add to existing registration
            $pattern = '/(app\(AdminPanel::class\)->pages\(\[\s*)(.*?)(\s*\]\);)/s';
            if (preg_match($pattern, $content, $matches)) {
                $existingPages = trim($matches[2]);

                // Check if it's just comments
                if (empty($existingPages) || str_contains($existingPages, '// Add your custom page classes here')) {
                    $newPages = "\n            {$pageClassName},\n        ";
                } else {
                    // Remove trailing comma if present, then add new page
                    $existingPages = rtrim($existingPages, ',');
                    $newPages = $existingPages . ",\n            {$pageClassName},";
                }

                $content = str_replace($matches[0], $matches[1] . $newPages . $matches[3], $content);
            }
        } else {
            // Add new pages registration to boot method
            $bootPattern = '/(public function boot\(\): void\s*\{\s*)(.*?)(\s*\})/s';
            if (preg_match($bootPattern, $content, $matches)) {
                $bootContent = trim($matches[2]);

                $newBootContent = $bootContent;
                if (!empty($bootContent)) {
                    $newBootContent .= "\n\n        ";
                }

                $newBootContent .= "// Register custom admin pages\n        app(AdminPanel::class)->pages([\n            {$pageClassName},\n        ]);";

                $content = str_replace($matches[0], $matches[1] . $newBootContent . $matches[3], $content);
            }
        }

        $this->files->put($serviceProviderPath, $content);
        return true;
    }
}
