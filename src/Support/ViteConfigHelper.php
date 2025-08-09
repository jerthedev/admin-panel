<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Facades\File;

/**
 * Vite Configuration Helper
 *
 * Generates Vite configuration for building custom admin pages
 * in the main application alongside the package assets.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Support
 */
class ViteConfigHelper
{
    /**
     * Generate Vite configuration for admin pages.
     *
     * @param array $options
     * @return array
     */
    public function generateConfig(array $options = []): array
    {
        $config = [
            'input' => $this->generateInputConfig($options),
            'output' => $this->generateOutputConfig($options),
            'resolve' => $this->generateResolveConfig($options),
            'build' => $this->generateBuildConfig($options),
        ];

        return array_merge($config, $options['additional'] ?? []);
    }

    /**
     * Generate input configuration.
     *
     * @param array $options
     * @return array
     */
    protected function generateInputConfig(array $options): array
    {
        $inputs = [];

        // Main admin pages entry point
        $adminPagesPath = 'resources/js/admin-pages';
        
        if ($this->shouldIncludeAdminPagesEntry($options)) {
            $inputs['admin-pages'] = $adminPagesPath . '/app.js';
        }

        // Auto-discover individual page components
        if ($options['auto_discover'] ?? true) {
            $discoveredInputs = $this->discoverPageInputs($adminPagesPath);
            $inputs = array_merge($inputs, $discoveredInputs);
        }

        // Include any additional inputs
        if (isset($options['additional_inputs'])) {
            $inputs = array_merge($inputs, $options['additional_inputs']);
        }

        return $inputs;
    }

    /**
     * Generate output configuration.
     *
     * @param array $options
     * @return array
     */
    protected function generateOutputConfig(array $options): array
    {
        return [
            'dir' => $options['output_dir'] ?? 'public/build/admin-pages',
            'format' => 'es',
            'entryFileNames' => '[name]-[hash].js',
            'chunkFileNames' => 'chunks/[name]-[hash].js',
            'assetFileNames' => 'assets/[name]-[hash].[ext]',
        ];
    }

    /**
     * Generate resolve configuration.
     *
     * @param array $options
     * @return array
     */
    protected function generateResolveConfig(array $options): array
    {
        return [
            'alias' => [
                '@admin-pages' => '/resources/js/admin-pages',
                '@admin-panel' => '/vendor/jerthedev/admin-panel/resources/js',
            ],
            'extensions' => ['.js', '.vue', '.ts'],
        ];
    }

    /**
     * Generate build configuration.
     *
     * @param array $options
     * @return array
     */
    protected function generateBuildConfig(array $options): array
    {
        return [
            'manifest' => true,
            'outDir' => $options['output_dir'] ?? 'public/build/admin-pages',
            'rollupOptions' => [
                'external' => $options['externals'] ?? [],
                'output' => [
                    'globals' => $options['globals'] ?? [],
                ],
            ],
        ];
    }

    /**
     * Check if admin pages entry should be included.
     *
     * @param array $options
     * @return bool
     */
    protected function shouldIncludeAdminPagesEntry(array $options): bool
    {
        if (isset($options['include_entry'])) {
            return $options['include_entry'];
        }

        // Include entry if app.js exists in admin-pages directory
        return File::exists(base_path('resources/js/admin-pages/app.js'));
    }

    /**
     * Discover page inputs automatically.
     *
     * @param string $basePath
     * @return array
     */
    protected function discoverPageInputs(string $basePath): array
    {
        $inputs = [];
        $fullPath = base_path($basePath);

        if (!File::exists($fullPath)) {
            return $inputs;
        }

        $files = File::allFiles($fullPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'vue') {
                $relativePath = str_replace($fullPath . '/', '', $file->getPathname());
                $componentName = str_replace('.vue', '', $relativePath);
                
                // Create input entry for each Vue component
                $inputKey = 'page-' . str_replace('/', '-', strtolower($componentName));
                $inputs[$inputKey] = $basePath . '/' . $relativePath;
            }
        }

        return $inputs;
    }

    /**
     * Generate a complete Vite config file content.
     *
     * @param array $options
     * @return string
     */
    public function generateConfigFile(array $options = []): string
    {
        $config = $this->generateConfig($options);
        
        $configJson = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<JS
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [vue()],
    
    build: {
        lib: false,
        rollupOptions: {$configJson}
    },
    
    resolve: {
        alias: {
            '@admin-pages': resolve(__dirname, 'resources/js/admin-pages'),
            '@admin-panel': resolve(__dirname, 'vendor/jerthedev/admin-panel/resources/js'),
        }
    },
    
    server: {
        hmr: {
            host: 'localhost',
        },
    },
})
JS;
    }

    /**
     * Create the admin pages app.js entry point.
     *
     * @param string $path
     * @return bool
     */
    public function createAdminPagesEntry(string $path = null): bool
    {
        $path = $path ?? base_path('resources/js/admin-pages/app.js');
        
        // Create directory if it doesn't exist
        $directory = dirname($path);
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $entryContent = <<<JS
/**
 * Admin Pages Entry Point
 * 
 * This file is the entry point for custom admin pages.
 * It dynamically imports and registers custom page components.
 */

// Auto-import all Vue components in this directory
const components = import.meta.glob('./**/*.vue', { eager: true })

// Export components for the admin panel to use
export const customPageComponents = {}

Object.entries(components).forEach(([path, component]) => {
    // Convert ./ComponentName.vue to ComponentName
    const componentName = path
        .replace('./', '')
        .replace('.vue', '')
        .replace(/\//g, '/')
    
    customPageComponents[`Pages/\${componentName}`] = component.default || component
})

// Export for use by the admin panel
window.AdminPanelCustomComponents = customPageComponents

console.log('Admin Panel Custom Components loaded:', Object.keys(customPageComponents))
JS;

        return File::put($path, $entryContent) !== false;
    }

    /**
     * Get available page components in the admin-pages directory.
     *
     * @return array
     */
    public function getAvailableComponents(): array
    {
        $resolver = new ComponentResolver();
        return $resolver->getAvailableAppComponents();
    }

    /**
     * Generate package.json scripts for building admin pages.
     *
     * @return array
     */
    public function generatePackageScripts(): array
    {
        return [
            'build-admin-pages' => 'vite build --config vite.admin-pages.config.js',
            'dev-admin-pages' => 'vite --config vite.admin-pages.config.js',
            'watch-admin-pages' => 'vite build --config vite.admin-pages.config.js --watch',
        ];
    }

    /**
     * Check if the admin pages setup is properly configured.
     *
     * @return array
     */
    public function validateSetup(): array
    {
        $issues = [];
        $warnings = [];

        // Check if admin-pages directory exists
        $adminPagesPath = base_path('resources/js/admin-pages');
        if (!File::exists($adminPagesPath)) {
            $issues[] = 'Admin pages directory does not exist: ' . $adminPagesPath;
        }

        // Check if entry point exists
        $entryPath = $adminPagesPath . '/app.js';
        if (!File::exists($entryPath)) {
            $warnings[] = 'Admin pages entry point does not exist: ' . $entryPath;
        }

        // Check for Vue components
        $components = $this->getAvailableComponents();
        if (empty($components)) {
            $warnings[] = 'No custom page components found in admin-pages directory';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'components_found' => count($components),
            'components' => $components,
        ];
    }
}
