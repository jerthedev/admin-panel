<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Facades\File;

/**
 * Component Resolver
 *
 * Handles dynamic resolution of Vue components for custom pages,
 * supporting both package components and application components
 * with graceful fallbacks.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Support
 */
class ComponentResolver
{
    /**
     * Package components directory (relative to package root).
     */
    protected const PACKAGE_COMPONENTS_PATH = 'resources/js/pages';

    /**
     * Application components directory (relative to app root).
     */
    protected const APP_COMPONENTS_PATH = 'resources/js/admin-pages';

    /**
     * Resolve a component by name using manifest-based resolution.
     *
     * @param string $componentName
     * @return array
     */
    public function resolve(string $componentName): array
    {
        // Try manifest-based resolution first
        $manifestResult = $this->resolveFromManifest($componentName);
        if ($manifestResult['type'] !== 'not_found') {
            return $manifestResult;
        }

        // Fallback to file-based resolution for development
        if (str_starts_with($componentName, 'Pages/')) {
            return $this->resolveCustomPageComponent($componentName);
        }

        // For non-custom pages, resolve from package
        return $this->resolvePackageComponent($componentName);
    }

    /**
     * Resolve component from manifest files.
     *
     * @param string $componentName
     * @return array
     */
    protected function resolveFromManifest(string $componentName): array
    {
        // Get all registered manifests (this will be implemented when we add manifest registration)
        $manifests = $this->getRegisteredManifests();

        foreach ($manifests as $manifest) {
            if (isset($manifest['components'][$componentName])) {
                return [
                    'type' => 'manifest',
                    'component_name' => $componentName,
                    'asset_path' => $manifest['components'][$componentName],
                    'base_url' => $manifest['base_url'] ?? '',
                    'source' => $manifest['source'] ?? 'unknown',
                ];
            }
        }

        return ['type' => 'not_found'];
    }

    /**
     * Get registered manifests from the registry.
     *
     * @return array
     */
    protected function getRegisteredManifests(): array
    {
        $registry = app(CustomPageManifestRegistry::class);
        return $registry->getAggregatedManifest();
    }

    /**
     * Resolve a custom page component (Pages/ prefix).
     *
     * @param string $componentName
     * @return array
     */
    protected function resolveCustomPageComponent(string $componentName): array
    {
        // Remove 'Pages/' prefix to get the actual component name
        $actualComponentName = substr($componentName, 6);

        // First, try to resolve from application directory
        $appResult = $this->resolveAppComponent($actualComponentName);
        if ($appResult['type'] === 'app') {
            return $appResult;
        }

        // If not found in app, try package directory
        $packageResult = $this->resolvePackageComponent($componentName);
        if ($packageResult['type'] === 'package') {
            return $packageResult;
        }

        // If not found anywhere, return fallback
        return $this->createFallback($componentName);
    }

    /**
     * Resolve a component from the application directory.
     *
     * @param string $componentName
     * @return array
     */
    protected function resolveAppComponent(string $componentName): array
    {
        $appPath = base_path(self::APP_COMPONENTS_PATH);
        $componentPath = $appPath . '/' . $componentName . '.vue';

        if (File::exists($componentPath)) {
            return [
                'type' => 'app',
                'path' => $componentPath,
                'relative_path' => self::APP_COMPONENTS_PATH . '/' . $componentName . '.vue',
                'component_name' => $componentName,
                'import_path' => '../../../' . self::APP_COMPONENTS_PATH . '/' . $componentName . '.vue',
            ];
        }

        return [
            'type' => 'not_found',
            'path' => $componentPath,
        ];
    }

    /**
     * Resolve a component from the package directory.
     *
     * @param string $componentName
     * @return array
     */
    protected function resolvePackageComponent(string $componentName): array
    {
        $packagePath = __DIR__ . '/../../' . self::PACKAGE_COMPONENTS_PATH;

        // Handle both direct components and Pages/ prefixed components
        $possiblePaths = [
            $packagePath . '/' . $componentName . '.vue',
            $packagePath . '/Pages/' . $componentName . '.vue',
        ];

        // If componentName starts with Pages/, also try without the prefix
        if (str_starts_with($componentName, 'Pages/')) {
            $withoutPrefix = substr($componentName, 6);
            $possiblePaths[] = $packagePath . '/' . $withoutPrefix . '.vue';
        }

        foreach ($possiblePaths as $componentPath) {
            if (File::exists($componentPath)) {
                return [
                    'type' => 'package',
                    'path' => $componentPath,
                    'relative_path' => str_replace(__DIR__ . '/../../', '', $componentPath),
                    'component_name' => $componentName,
                ];
            }
        }

        return [
            'type' => 'not_found',
            'component_name' => $componentName,
        ];
    }

    /**
     * Create a fallback response for missing components.
     *
     * @param string $componentName
     * @return array
     */
    protected function createFallback(string $componentName): array
    {
        $expectedPath = base_path(self::APP_COMPONENTS_PATH . '/' . substr($componentName, 6) . '.vue');

        return [
            'type' => 'fallback',
            'component_name' => $componentName,
            'expected_path' => $expectedPath,
            'suggestions' => $this->generateSuggestions($componentName),
            'error_message' => "Custom page component '{$componentName}' not found.",
            'help_text' => "Create the component at: " . $expectedPath,
        ];
    }

    /**
     * Generate helpful suggestions for missing components.
     *
     * @param string $componentName
     * @return array
     */
    protected function generateSuggestions(string $componentName): array
    {
        $suggestions = [];

        // Suggest creating the component
        $actualName = str_starts_with($componentName, 'Pages/')
            ? substr($componentName, 6)
            : $componentName;

        $suggestions[] = "Create {$actualName}.vue in " . self::APP_COMPONENTS_PATH;

        // Suggest using artisan command (when available)
        $suggestions[] = "Use: php artisan admin-panel:make-page {$actualName}";

        // Suggest checking existing components
        $existingComponents = $this->getAvailableAppComponents();
        if (!empty($existingComponents)) {
            $suggestions[] = "Available components: " . implode(', ', $existingComponents);
        }

        return $suggestions;
    }

    /**
     * Get all available app components.
     *
     * @return array
     */
    public function getAvailableAppComponents(): array
    {
        $appPath = base_path(self::APP_COMPONENTS_PATH);

        if (!File::exists($appPath)) {
            return [];
        }

        $components = [];
        $files = File::allFiles($appPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'vue') {
                $relativePath = str_replace($appPath . '/', '', $file->getPathname());
                $componentName = str_replace('.vue', '', $relativePath);
                $components[] = 'Pages/' . $componentName;
            }
        }

        return $components;
    }

    /**
     * Check if a component exists in the app directory.
     *
     * @param string $componentName
     * @return bool
     */
    public function appComponentExists(string $componentName): bool
    {
        $actualName = str_starts_with($componentName, 'Pages/')
            ? substr($componentName, 6)
            : $componentName;

        $appPath = base_path(self::APP_COMPONENTS_PATH);
        $componentPath = $appPath . '/' . $actualName . '.vue';

        return File::exists($componentPath);
    }

    /**
     * Get the import path for a component (for use in JavaScript).
     *
     * @param string $componentName
     * @return string|null
     */
    public function getImportPath(string $componentName): ?string
    {
        $result = $this->resolve($componentName);

        return $result['import_path'] ?? null;
    }

    /**
     * Get component resolution statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        $appComponents = $this->getAvailableAppComponents();

        return [
            'app_components_count' => count($appComponents),
            'app_components_path' => base_path(self::APP_COMPONENTS_PATH),
            'package_components_path' => __DIR__ . '/../../' . self::PACKAGE_COMPONENTS_PATH,
            'app_components' => $appComponents,
        ];
    }
}
