<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Vite;

use Illuminate\Filesystem\Filesystem;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Admin Panel Vite Plugin (PHP Wrapper)
 *
 * PHP wrapper for testing the Vite plugin functionality.
 * The actual plugin is implemented in JavaScript.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Vite
 */
class AdminPanelPlugin
{
    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    /**
     * Plugin configuration options.
     */
    protected array $config;

    /**
     * Create a new plugin instance.
     */
    public function __construct(Filesystem $files, array $config = [])
    {
        $this->files = $files;
        $this->config = array_merge([
            'adminPagesPath' => 'resources/js/admin-pages',
            'adminCardsPath' => 'resources/js/admin-cards',
            'manifestPath' => 'public/admin-pages-manifest.json',
            'hotReload' => true,
        ], $config);
    }

    /**
     * Detect all Vue components in the admin pages directory.
     */
    public function detectAdminPageComponents(string $basePath): array
    {
        $adminPagesPath = $basePath . '/' . $this->config['adminPagesPath'];

        if (!$this->files->exists($adminPagesPath)) {
            return [];
        }

        $components = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($adminPagesPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() === 'vue' && $file->getSize() > 0) {
                    $components[] = $file->getPathname();
                }
            }
        } catch (\Exception $e) {
            // Handle directory access errors gracefully
            return [];
        }

        return $components;
    }

    /**
     * Detect all Vue components in the admin cards directory.
     */
    public function detectAdminCardComponents(string $basePath): array
    {
        $adminCardsPath = $basePath . '/' . $this->config['adminCardsPath'];

        if (!$this->files->exists($adminCardsPath)) {
            return [];
        }

        $components = [];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($adminCardsPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->getExtension() === 'vue' && $file->getSize() > 0) {
                    $components[] = $file->getPathname();
                }
            }
        } catch (\Exception $e) {
            // Handle directory access errors gracefully
            return [];
        }

        return $components;
    }

    /**
     * Generate Vite build entries for admin page components.
     */
    public function generateBuildEntries(string $basePath): array
    {
        $pageComponents = $this->detectAdminPageComponents($basePath);
        $cardComponents = $this->detectAdminCardComponents($basePath);
        $entries = [];

        $adminPagesPath = $basePath . '/' . $this->config['adminPagesPath'];
        $adminCardsPath = $basePath . '/' . $this->config['adminCardsPath'];

        // Process page components
        foreach ($pageComponents as $componentPath) {
            // Get relative path from admin pages directory
            $relativePath = str_replace($adminPagesPath . '/', '', $componentPath);

            // Remove .vue extension and create entry name
            $entryName = 'admin-pages/' . str_replace('.vue', '', $relativePath);

            // Store the full path for the entry
            $entries[$entryName] = $componentPath;
        }

        // Process card components
        foreach ($cardComponents as $componentPath) {
            // Get relative path from admin cards directory
            $relativePath = str_replace($adminCardsPath . '/', '', $componentPath);

            // Remove .vue extension and create entry name
            $entryName = 'admin-cards/' . str_replace('.vue', '', $relativePath);

            // Store the full path for the entry
            $entries[$entryName] = $componentPath;
        }

        return $entries;
    }

    /**
     * Generate manifest from built assets.
     */
    public function generateManifest(array $builtAssets, string $basePath): array
    {
        $manifest = [
            'admin-pages' => [],
            'admin-cards' => []
        ];

        foreach ($builtAssets as $entryName => $asset) {
            if (str_starts_with($entryName, 'admin-pages/')) {
                $componentName = str_replace('admin-pages/', '', $entryName);
                $manifest['admin-pages'][$componentName] = $asset;
            } elseif (str_starts_with($entryName, 'admin-cards/')) {
                $componentName = str_replace('admin-cards/', '', $entryName);
                $manifest['admin-cards'][$componentName] = $asset;
            }
        }

        return $manifest;
    }

    /**
     * Validate that a Vue component file is valid.
     */
    public function isValidVueComponent(string $filePath): bool
    {
        if (!$this->files->exists($filePath)) {
            return false;
        }

        // Check file extension
        if (!str_ends_with($filePath, '.vue')) {
            return false;
        }

        // Check file size (must not be empty)
        if ($this->files->size($filePath) === 0) {
            return false;
        }

        // Basic content validation (must contain <template> tag)
        $content = $this->files->get($filePath);
        if (!str_contains($content, '<template>')) {
            return false;
        }

        return true;
    }

    /**
     * Get the plugin configuration.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set plugin configuration option.
     */
    public function setConfig(string $key, mixed $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Get the admin pages path relative to base path.
     */
    public function getAdminPagesPath(): string
    {
        return $this->config['adminPagesPath'];
    }

    /**
     * Get the admin cards path relative to base path.
     */
    public function getAdminCardsPath(): string
    {
        return $this->config['adminCardsPath'];
    }

    /**
     * Get the manifest output path.
     */
    public function getManifestPath(): string
    {
        return $this->config['manifestPath'];
    }

    /**
     * Check if hot reloading is enabled.
     */
    public function isHotReloadEnabled(): bool
    {
        return $this->config['hotReload'];
    }

    /**
     * Generate component path for manifest resolution.
     */
    public function generateComponentPath(string $componentPath, string $basePath): string
    {
        $adminPagesPath = $basePath . '/' . $this->config['adminPagesPath'];
        $adminCardsPath = $basePath . '/' . $this->config['adminCardsPath'];

        if (str_starts_with($componentPath, $adminPagesPath)) {
            $relativePath = str_replace($adminPagesPath . '/', '', $componentPath);
            return 'Pages/' . str_replace('.vue', '', $relativePath);
        } elseif (str_starts_with($componentPath, $adminCardsPath)) {
            $relativePath = str_replace($adminCardsPath . '/', '', $componentPath);
            return 'Cards/' . str_replace('.vue', '', $relativePath);
        }

        // Fallback for unknown paths
        return str_replace('.vue', '', basename($componentPath));
    }

    /**
     * Get statistics about detected components.
     */
    public function getComponentStats(string $basePath): array
    {
        $components = $this->detectAdminPageComponents($basePath);
        $stats = [
            'total' => count($components),
            'by_directory' => [],
            'nested_levels' => 0,
        ];

        $adminPagesPath = $basePath . '/' . $this->config['adminPagesPath'];

        foreach ($components as $componentPath) {
            $relativePath = str_replace($adminPagesPath . '/', '', $componentPath);
            $directory = dirname($relativePath);
            
            if ($directory === '.') {
                $directory = 'root';
            }

            if (!isset($stats['by_directory'][$directory])) {
                $stats['by_directory'][$directory] = 0;
            }
            $stats['by_directory'][$directory]++;

            // Calculate nesting level
            $level = substr_count($relativePath, '/');
            $stats['nested_levels'] = max($stats['nested_levels'], $level);
        }

        return $stats;
    }
}
