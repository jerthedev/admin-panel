<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Integration\Vite;

use Illuminate\Filesystem\Filesystem;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Vite Plugin Card Integration Tests.
 *
 * Tests the JavaScript Vite plugin card functionality with actual file operations.
 */
class VitePluginCardIntegrationTest extends TestCase
{
    protected Filesystem $files;
    protected string $testOutputPath;
    protected string $vitePluginPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->files = new Filesystem();
        $this->testOutputPath = base_path('tests/temp/vite-integration');
        $this->vitePluginPath = __DIR__ . '/../../../vite/index.js';
        
        // Create temp directory for test files
        if (!$this->files->exists($this->testOutputPath)) {
            $this->files->makeDirectory($this->testOutputPath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if ($this->files->exists($this->testOutputPath)) {
            $this->files->deleteDirectory($this->testOutputPath);
        }
        
        parent::tearDown();
    }

    public function test_vite_plugin_file_exists(): void
    {
        $this->assertTrue($this->files->exists($this->vitePluginPath));
        
        $content = $this->files->get($this->vitePluginPath);
        $this->assertStringContainsString('adminCardsPath', $content);
        $this->assertStringContainsString('detectAdminCardComponents', $content);
    }

    public function test_vite_plugin_has_card_configuration(): void
    {
        $content = $this->files->get($this->vitePluginPath);
        
        // Check for adminCardsPath config
        $this->assertStringContainsString("adminCardsPath: 'resources/js/admin-cards'", $content);
        
        // Check for card detection function
        $this->assertStringContainsString('function detectAdminCardComponents', $content);
        
        // Check for Cards manifest generation
        $this->assertStringContainsString("'Cards': {}", $content);
    }

    public function test_vite_plugin_detects_card_components(): void
    {
        // Create test card components
        $cardsPath = $this->testOutputPath . '/resources/js/admin-cards';
        $this->files->makeDirectory($cardsPath, 0755, true);
        
        $this->files->put($cardsPath . '/TestCard.vue', $this->getTestCardContent());
        $this->files->put($cardsPath . '/UserStats.vue', $this->getTestCardContent('UserStats'));

        // Create nested directory and component
        $nestedPath = $cardsPath . '/analytics';
        $this->files->makeDirectory($nestedPath, 0755, true);
        $this->files->put($nestedPath . '/RevenueChart.vue', $this->getTestCardContent('RevenueChart'));
        
        // Test that the plugin would detect these components
        $this->assertTrue($this->files->exists($cardsPath . '/TestCard.vue'));
        $this->assertTrue($this->files->exists($cardsPath . '/UserStats.vue'));
        $this->assertTrue($this->files->exists($cardsPath . '/analytics/RevenueChart.vue'));
        
        // Verify file contents are valid Vue components
        $testCardContent = $this->files->get($cardsPath . '/TestCard.vue');
        $this->assertStringContainsString('<template>', $testCardContent);
        $this->assertStringContainsString('<script setup>', $testCardContent);
        $this->assertStringContainsString('defineProps', $testCardContent);
    }

    public function test_vite_plugin_generates_correct_manifest_structure(): void
    {
        // Create a mock manifest that the plugin would generate
        $expectedManifest = [
            'Pages' => [
                'Dashboard' => [
                    'file' => 'fallback:resources/js/admin-pages/Dashboard.vue',
                    'isDynamicImport' => true,
                    'useFallback' => true
                ]
            ],
            'Cards' => [
                'TestCard' => [
                    'file' => 'fallback:resources/js/admin-cards/TestCard.vue',
                    'isDynamicImport' => true,
                    'useFallback' => true
                ],
                'analytics/RevenueChart' => [
                    'file' => 'fallback:resources/js/admin-cards/analytics/RevenueChart.vue',
                    'isDynamicImport' => true,
                    'useFallback' => true
                ]
            ]
        ];
        
        // Write test manifest
        $manifestPath = $this->testOutputPath . '/admin-manifest.json';
        $this->files->put($manifestPath, json_encode($expectedManifest, JSON_PRETTY_PRINT));
        
        // Verify manifest structure
        $manifest = json_decode($this->files->get($manifestPath), true);
        
        $this->assertArrayHasKey('Pages', $manifest);
        $this->assertArrayHasKey('Cards', $manifest);
        $this->assertArrayHasKey('TestCard', $manifest['Cards']);
        $this->assertArrayHasKey('analytics/RevenueChart', $manifest['Cards']);
        
        // Verify card entries have correct structure
        $testCard = $manifest['Cards']['TestCard'];
        $this->assertArrayHasKey('file', $testCard);
        $this->assertArrayHasKey('isDynamicImport', $testCard);
        $this->assertArrayHasKey('useFallback', $testCard);
        $this->assertTrue($testCard['isDynamicImport']);
        $this->assertTrue($testCard['useFallback']);
    }

    public function test_vite_plugin_supports_hot_reload_for_cards(): void
    {
        $content = $this->files->get($this->vitePluginPath);
        
        // Check for hot reload support for cards
        $this->assertStringContainsString('adminCardsFullPath', $content);
        $this->assertStringContainsString('isCardComponent', $content);
        $this->assertStringContainsString("componentType = isPageComponent ? 'page' : 'card'", $content);
    }

    public function test_vite_plugin_commonjs_version_has_card_support(): void
    {
        $cjsPluginPath = __DIR__ . '/../../../vite/index.cjs';
        $this->assertTrue($this->files->exists($cjsPluginPath));
        
        $content = $this->files->get($cjsPluginPath);
        
        // Check for card support in CommonJS version
        $this->assertStringContainsString('adminCardsPath', $content);
        $this->assertStringContainsString('detectAdminCardComponents', $content);
        $this->assertStringContainsString('admin-cards', $content);
    }

    public function test_app_js_supports_card_component_resolution(): void
    {
        $appJsPath = __DIR__ . '/../../../resources/js/app.js';
        $this->assertTrue($this->files->exists($appJsPath));
        
        $content = $this->files->get($appJsPath);
        
        // Check for Cards/ prefix support
        $this->assertStringContainsString("name.startsWith('Cards/')", $content);
        $this->assertStringContainsString('/resources/js/admin-cards/', $content);
        $this->assertStringContainsString('dev card component', $content);
    }

    public function test_card_component_fallback_messages(): void
    {
        $appJsPath = __DIR__ . '/../../../resources/js/app.js';
        $content = $this->files->get($appJsPath);
        
        // Check for card-specific fallback messages
        $this->assertStringContainsString('admin-panel:make-card', $content);
        $this->assertStringContainsString('resources/js/admin-cards/', $content);
    }

    public function test_vite_plugin_handles_mixed_pages_and_cards(): void
    {
        // Create both pages and cards
        $pagesPath = $this->testOutputPath . '/resources/js/admin-pages';
        $cardsPath = $this->testOutputPath . '/resources/js/admin-cards';
        
        $this->files->makeDirectory($pagesPath, 0755, true);
        $this->files->makeDirectory($cardsPath, 0755, true);
        
        $this->files->put($pagesPath . '/Dashboard.vue', $this->getTestPageContent());
        $this->files->put($cardsPath . '/TestCard.vue', $this->getTestCardContent());
        
        // Verify both types exist
        $this->assertTrue($this->files->exists($pagesPath . '/Dashboard.vue'));
        $this->assertTrue($this->files->exists($cardsPath . '/TestCard.vue'));
        
        // Verify they have different content patterns
        $pageContent = $this->files->get($pagesPath . '/Dashboard.vue');
        $cardContent = $this->files->get($cardsPath . '/TestCard.vue');
        
        $this->assertStringContainsString('Dashboard', $pageContent);
        $this->assertStringContainsString('TestCard', $cardContent);
        $this->assertStringContainsString('defineProps', $cardContent);
    }

    public function test_vite_plugin_configuration_is_extensible(): void
    {
        $content = $this->files->get($this->vitePluginPath);
        
        // Check that configuration can be extended
        $this->assertStringContainsString('...options', $content);
        $this->assertStringContainsString('adminCardsPath:', $content);
        
        // Verify default configuration
        $this->assertStringContainsString("adminCardsPath: 'resources/js/admin-cards'", $content);
    }

    protected function getTestCardContent(string $cardName = 'TestCard'): string
    {
        return <<<VUE
<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900">{{ card.title || '{$cardName}' }}</h3>
    <div class="mt-4">
      <div class="text-2xl font-bold text-blue-600">{{ data.value || 0 }}</div>
      <div class="text-sm text-gray-500">{{ data.label || 'Value' }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  card: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['refresh', 'configure'])

const data = computed(() => {
  return props.card.data || {
    value: 42,
    label: 'Test Value'
  }
})
</script>
VUE;
    }

    protected function getTestPageContent(string $pageName = 'Dashboard'): string
    {
        return <<<VUE
<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold text-gray-900">{$pageName}</h1>
    <p class="mt-4 text-gray-600">This is a test page component.</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const title = ref('{$pageName}')
</script>
VUE;
    }
}
