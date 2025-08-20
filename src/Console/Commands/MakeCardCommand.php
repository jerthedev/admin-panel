<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Make Card Command.
 *
 * Artisan command for creating custom admin panel cards with
 * Vue components and auto-registration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MakeCardCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'admin-panel:make-card
                            {name : The name of the card}
                            {--group= : The group for the card}
                            {--icon= : The icon for the card}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new admin panel card with Vue component';

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
        $group = $this->option('group');
        $icon = $this->option('icon');
        $force = $this->option('force');

        // Validate card name
        if (! $this->isValidCardName($name)) {
            $this->error("Invalid card name: {$name}. Card names must be PascalCase and start with a letter.");

            return 1;
        }

        // Get paths
        $appPath = app_path();
        $resourcesPath = resource_path();

        // Create the card
        $result = $this->createCard($name, $appPath, $resourcesPath, $force, $group, $icon);

        if (! $result) {
            $this->error("Failed to create card: {$name}");

            return 1;
        }

        // Auto-register the card
        $registrationResult = $this->autoRegisterCard($name, $appPath);
        if (! $registrationResult) {
            $this->warn('Card created but auto-registration failed. You may need to manually register the card.');
        }

        $this->info("Card {$name} created successfully!");
        $this->info("Created Vue component: {$name}.vue");

        return 0;
    }

    /**
     * Create a card with PHP class and Vue component.
     */
    public function createCard(
        string $name,
        string $appPath,
        string $resourcesPath,
        bool $force = false,
        ?string $group = null,
        ?string $icon = null,
    ): bool {
        $cardClassName = $name.'Card';
        $phpFile = $appPath.'/Admin/Cards/'.$cardClassName.'.php';

        // Check if card already exists
        if ($this->files->exists($phpFile) && ! $force) {
            return false;
        }

        // Ensure directories exist
        if (! $this->files->exists(dirname($phpFile))) {
            $this->files->makeDirectory(dirname($phpFile), 0755, true);
        }
        if (! $this->files->exists($resourcesPath.'/js/admin-cards')) {
            $this->files->makeDirectory($resourcesPath.'/js/admin-cards', 0755, true);
        }

        // Generate PHP class using stub
        $phpContent = $this->generatePhpClass($name, $group, $icon);
        $this->files->put($phpFile, $phpContent);

        // Generate Vue component
        $vueFile = $resourcesPath.'/js/admin-cards/'.$name.'.vue';
        $vueContent = $this->generateVueComponent($name);
        $this->files->put($vueFile, $vueContent);

        return true;
    }

    /**
     * Validate card name.
     */
    public function isValidCardName(string $name): bool
    {
        return ! empty($name) &&
               preg_match('/^[A-Z][a-zA-Z0-9]*$/', $name) &&
               ! is_numeric($name[0]);
    }

    /**
     * Generate PHP card class content using stub template.
     */
    public function generatePhpClass(string $name, ?string $group, ?string $icon): string
    {
        $stubPath = __DIR__.'/../stubs/Card.stub';
        $stubContent = $this->files->get($stubPath);

        $replacements = [
            '{{ namespace }}' => 'App\\Admin\\Cards',
            '{{ class }}' => $name.'Card',
            '{{ group }}' => $group ?? 'Default',
            '{{ icon }}' => $icon ?? 'square-3-stack-3d',
        ];

        $processedContent = $stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processedContent = str_replace($placeholder, $value, $processedContent);
        }

        return $processedContent;
    }

    /**
     * Generate Vue component content.
     */
    public function generateVueComponent(string $cardName): string
    {
        $title = Str::title(Str::snake($cardName, ' '));
        $componentName = $cardName.'Card';

        return <<<VUE
<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <!-- Card Header -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center">
                <div v-if="card.meta?.icon" class="mr-3">
                    <component :is="card.meta.icon" class="h-6 w-6 text-gray-500" />
                </div>
                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ card.meta?.title || card.name }}</h3>
                    <p v-if="card.meta?.description" class="text-sm text-gray-600">{{ card.meta.description }}</p>
                </div>
            </div>
            <div v-if="card.meta?.refreshable" class="flex items-center">
                <button @click="refresh" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Card Content -->
        <div class="space-y-4">
            <div v-if="card.meta?.data" class="space-y-3">
                <div v-for="(value, key) in card.meta.data" :key="key" class="flex justify-between">
                    <span class="text-sm font-medium text-gray-600 capitalize">{{ key.replace('_', ' ') }}</span>
                    <span class="text-sm text-gray-900">{{ value }}</span>
                </div>
            </div>
            
            <div v-else class="text-center py-8">
                <p class="text-gray-500">No data available</p>
            </div>
        </div>

        <!-- Development Info -->
        <div class="mt-6 pt-4 border-t border-gray-100">
            <div class="text-xs text-gray-500 space-y-1">
                <p><strong>Card:</strong> {$cardName}</p>
                <p><strong>Component:</strong> {$componentName}</p>
                <p><strong>Location:</strong> resources/js/admin-cards/{$cardName}.vue</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    card: {
        type: Object,
        required: true,
        validator: (card) => {
            return !!(card && typeof card === 'object' &&
                      card.name && card.component && card.uriKey)
        }
    }
})

const emit = defineEmits(['refresh'])

// Reactive data
const isRefreshing = ref(false)

// Methods
const refresh = async () => {
    if (isRefreshing.value) return
    
    isRefreshing.value = true
    emit('refresh')
    
    // Simulate refresh delay
    setTimeout(() => {
        isRefreshing.value = false
    }, 1000)
}

// Log component loading for development
console.log('🎯 {$title} card component loaded successfully!')
console.log('📍 Component location: resources/js/admin-cards/{$cardName}.vue')
</script>
VUE;
    }

    /**
     * Auto-register the card in AdminPanelServiceProvider.
     */
    public function autoRegisterCard(string $name, string $appPath): bool
    {
        $serviceProviderPath = $appPath.'/Providers/AdminPanelServiceProvider.php';

        if (! $this->files->exists($serviceProviderPath)) {
            return false;
        }

        $content = $this->files->get($serviceProviderPath);
        $cardClassName = "\\App\\Admin\\Cards\\{$name}Card::class";

        // Check if already registered
        if (str_contains($content, $cardClassName)) {
            return true; // Already registered
        }

        // Check if cards registration already exists
        if (str_contains($content, 'app(AdminPanel::class)->cards([')) {
            // Add to existing registration
            $pattern = '/(app\(AdminPanel::class\)->cards\(\[\s*)(.*?)(\s*\]\);)/s';
            if (preg_match($pattern, $content, $matches)) {
                $existingCards = trim($matches[2]);

                // Check if it's just comments
                if (empty($existingCards) || str_contains($existingCards, '// Add your custom card classes here')) {
                    $newCards = "\n            {$cardClassName},\n        ";
                } else {
                    // Remove trailing comma if present, then add new card
                    $existingCards = rtrim($existingCards, ',');
                    $newCards = $existingCards.",\n            {$cardClassName},";
                }

                $content = str_replace($matches[0], $matches[1].$newCards.$matches[3], $content);
            }
        } else {
            // Add new cards registration to boot method
            $bootPattern = '/(public function boot\(\): void\s*\{\s*)(.*?)(\s*\})/s';
            if (preg_match($bootPattern, $content, $matches)) {
                $bootContent = trim($matches[2]);

                $newBootContent = $bootContent;
                if (! empty($bootContent)) {
                    $newBootContent .= "\n\n        ";
                }

                $newBootContent .= "// Register custom admin cards\n        app(AdminPanel::class)->cards([\n            {$cardClassName},\n        ]);";

                $content = str_replace($matches[0], $matches[1].$newBootContent.$matches[3], $content);
            }
        }

        $this->files->put($serviceProviderPath, $content);

        return true;
    }
}
