<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use JTD\AdminPanel\Resources\Resource;
use ReflectionClass;

/**
 * Resource Discovery Service
 *
 * Automatically discovers and registers admin panel resources from the
 * configured discovery path with caching for performance optimization.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Support
 */
class ResourceDiscovery
{
    /**
     * The cache key for discovered resources.
     */
    protected const CACHE_KEY = 'admin_panel_discovered_resources';

    /**
     * Discover all resources in the configured path.
     */
    public function discover(): Collection
    {
        if (! config('admin-panel.resources.auto_discovery', true)) {
            return collect();
        }

        $cacheKey = $this->getCacheKey();
        $cacheTtl = config('admin-panel.performance.cache_ttl', 3600);

        if (config('admin-panel.performance.cache_resources', true)) {
            return Cache::remember($cacheKey, $cacheTtl, function () {
                return $this->performDiscovery();
            });
        }

        return $this->performDiscovery();
    }

    /**
     * Perform the actual resource discovery.
     */
    protected function performDiscovery(): Collection
    {
        $discoveryPath = $this->getDiscoveryPath();

        if (! File::exists($discoveryPath)) {
            return collect();
        }

        $resources = collect();
        $files = File::allFiles($discoveryPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file);

            if ($this->isValidResourceClass($className)) {
                $resources->push($className);
            }
        }

        return $resources;
    }

    /**
     * Get the discovery path for resources.
     */
    protected function getDiscoveryPath(): string
    {
        $path = config('admin-panel.resources.discovery_path', 'app/Admin/Resources');

        return base_path($path);
    }

    /**
     * Get the class name from a file path.
     */
    protected function getClassNameFromFile($file): string
    {
        $relativePath = str_replace(base_path() . '/', '', $file->getPathname());
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace('/', '\\', $relativePath);

        // Convert app/Admin/Resources/UserResource to App\Admin\Resources\UserResource
        $className = str_replace('app\\', 'App\\', $relativePath);

        return $className;
    }

    /**
     * Check if a class is a valid resource class.
     */
    protected function isValidResourceClass(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        try {
            $reflection = new ReflectionClass($className);

            // Must be a subclass of Resource and not abstract
            return $reflection->isSubclassOf(Resource::class) && ! $reflection->isAbstract();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the cache key for discovered resources.
     */
    protected function getCacheKey(): string
    {
        $discoveryPath = $this->getDiscoveryPath();
        $pathHash = md5($discoveryPath);

        return self::CACHE_KEY . '_' . $pathHash;
    }

    /**
     * Clear the resource discovery cache.
     */
    public function clearCache(): void
    {
        $cacheKey = $this->getCacheKey();
        Cache::forget($cacheKey);
    }

    /**
     * Get all discovered resource instances.
     */
    public function getResourceInstances(): Collection
    {
        return $this->discover()->map(function (string $resourceClass) {
            return new $resourceClass();
        });
    }

    /**
     * Get a specific resource by its URI key.
     */
    public function findByUriKey(string $uriKey): ?Resource
    {
        $resources = $this->getResourceInstances();

        return $resources->first(function (Resource $resource) use ($uriKey) {
            return $resource::uriKey() === $uriKey;
        });
    }

    /**
     * Get resources grouped by their logical group.
     */
    public function getGroupedResources(): Collection
    {
        return $this->getResourceInstances()
            ->groupBy(function (Resource $resource) {
                return $resource::$group ?? 'Default';
            })
            ->map(function (Collection $groupResources) {
                // Sort resources alphabetically within each group
                return $groupResources->sortBy(function (Resource $resource) {
                    return $resource::label();
                })->values();
            });
    }

    /**
     * Get resources available for navigation.
     */
    public function getNavigationResources(): Collection
    {
        return $this->getResourceInstances()->filter(function (Resource $resource) {
            return $resource::availableForNavigation(request());
        });
    }

    /**
     * Get globally searchable resources.
     */
    public function getSearchableResources(): Collection
    {
        return $this->getResourceInstances()->filter(function (Resource $resource) {
            return $resource::$globallySearchable;
        });
    }
}
