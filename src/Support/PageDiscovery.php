<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Pages\Page;
use ReflectionClass;

/**
 * Page Discovery Service
 *
 * Automatically discovers and registers admin panel pages from the
 * configured discovery path with caching for performance optimization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Support
 */
class PageDiscovery
{
    /**
     * The cache key for discovered pages.
     */
    protected const CACHE_KEY = 'admin_panel_discovered_pages';

    /**
     * Discover all pages in the configured path.
     */
    public function discover(): Collection
    {
        if (! config('admin-panel.pages.auto_discovery', true)) {
            return collect();
        }

        $cacheKey = $this->getCacheKey();
        $cacheTtl = config('admin-panel.performance.cache_ttl', 3600);

        if (config('admin-panel.performance.cache_pages', true)) {
            try {
                return Cache::remember($cacheKey, $cacheTtl, function () {
                    return $this->performDiscovery();
                });
            } catch (\Exception $e) {
                // If caching fails (e.g., cache table doesn't exist during migration),
                // fall back to performing discovery without caching
                return $this->performDiscovery();
            }
        }

        return $this->performDiscovery();
    }

    /**
     * Discover pages in a specific path.
     */
    public function discoverIn(string $path): Collection
    {
        if (! File::exists($path)) {
            return collect();
        }

        $pages = collect();
        $files = File::allFiles($path);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file, $path);

            if ($this->isValidPageClass($className)) {
                $pages->push($className);
            }
        }

        return $pages;
    }

    /**
     * Perform the actual page discovery.
     */
    protected function performDiscovery(): Collection
    {
        $discoveryPath = $this->getDiscoveryPath();

        if (! File::exists($discoveryPath)) {
            return collect();
        }

        return $this->discoverIn($discoveryPath);
    }

    /**
     * Get the discovery path for pages.
     */
    protected function getDiscoveryPath(): string
    {
        $path = config('admin-panel.pages.discovery_path', 'app/Admin/Pages');

        return base_path($path);
    }

    /**
     * Get the class name from a file path.
     */
    protected function getClassNameFromFile($file, string $basePath = null): string
    {
        $basePath = $basePath ?? base_path();
        $relativePath = str_replace($basePath . '/', '', $file->getPathname());
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);

        // Convert app/Admin/Pages/DashboardPage to App\Admin\Pages\DashboardPage
        $className = str_replace('app\\', 'App\\', $relativePath);

        return $className;
    }

    /**
     * Check if a class is a valid page class.
     */
    protected function isValidPageClass(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);

            // Must be a subclass of Page and not abstract
            return $reflection->isSubclassOf(Page::class) && ! $reflection->isAbstract();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the cache key for discovered pages.
     */
    protected function getCacheKey(): string
    {
        $discoveryPath = $this->getDiscoveryPath();
        $pathHash = md5($discoveryPath);

        return self::CACHE_KEY . '_' . $pathHash;
    }

    /**
     * Clear the page discovery cache.
     */
    public function clearCache(): void
    {
        try {
            $cacheKey = $this->getCacheKey();
            Cache::forget($cacheKey);
        } catch (\Exception $e) {
            // If cache clearing fails (e.g., cache table doesn't exist),
            // silently continue as there's nothing to clear anyway
        }
    }

    /**
     * Get all discovered page instances.
     */
    public function getPageInstances(): Collection
    {
        return $this->discover()->map(function (string $pageClass) {
            return new $pageClass();
        });
    }

    /**
     * Get pages grouped by their menu group.
     */
    public function getGroupedPages(): Collection
    {
        return $this->getPageInstances()
            ->groupBy(function (Page $page) {
                return $page::group() ?? 'Default';
            })
            ->map(function (Collection $groupPages) {
                // Sort pages alphabetically within each group
                return $groupPages->sortBy(function (Page $page) {
                    return $page::label();
                })->values();
            });
    }

    /**
     * Find a page by its route name.
     */
    public function findByRouteName(string $routeName): ?Page
    {
        $pages = $this->getPageInstances();

        return $pages->first(function (Page $page) use ($routeName) {
            return $page::routeName() === $routeName;
        });
    }
}
