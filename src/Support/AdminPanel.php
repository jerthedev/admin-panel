<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Support\Collection;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Support\ResourceDiscovery;

/**
 * AdminPanel Facade
 *
 * Main facade for registering resources, pages, and managing
 * the admin panel configuration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Support
 */
class AdminPanel
{
    /**
     * Registered resources.
     */
    protected array $resources = [];

    /**
     * Registered pages.
     */
    protected array $pages = [];

    /**
     * Dashboard metrics.
     */
    protected array $metrics = [];

    /**
     * Resource discovery service.
     */
    protected ResourceDiscovery $discovery;

    /**
     * Create a new AdminPanel instance.
     */
    public function __construct()
    {
        $this->discovery = new ResourceDiscovery();
    }

    /**
     * Register resources with the admin panel (static version for AdminServiceProvider).
     */
    public static function resources(array $resources): void
    {
        $instance = app(static::class);

        foreach ($resources as $resource) {
            $instance->resource($resource);
        }
    }

    /**
     * Register resources with the admin panel (instance version).
     */
    public function registerResources(array $resources): static
    {
        foreach ($resources as $resource) {
            $this->resource($resource);
        }

        return $this;
    }

    /**
     * Register resources with the admin panel (alias for registerResources).
     */
    public function register(array $resources): static
    {
        return $this->registerResources($resources);
    }

    /**
     * Register a single resource.
     */
    public function resource(string $resource): static
    {
        if (! is_subclass_of($resource, Resource::class)) {
            throw new \InvalidArgumentException(
                "Resource [{$resource}] must extend " . Resource::class
            );
        }

        $this->resources[] = $resource;

        return $this;
    }

    /**
     * Get all registered resources.
     */
    public function getResources(): Collection
    {
        $manualResources = collect($this->resources)->map(function (string $resource) {
            return new $resource();
        });

        $discoveredResources = $this->discovery->getResourceInstances();

        return $manualResources->merge($discoveredResources)->unique(function (Resource $resource) {
            return get_class($resource);
        });
    }

    /**
     * Register pages with the admin panel.
     */
    public function pages(array $pages): static
    {
        $this->pages = array_merge($this->pages, $pages);

        return $this;
    }

    /**
     * Register a single page.
     */
    public function page(string $page): static
    {
        $this->pages[] = $page;

        return $this;
    }

    /**
     * Get all registered pages.
     */
    public function getPages(): Collection
    {
        return collect($this->pages);
    }

    /**
     * Register dashboard metrics.
     */
    public function metrics(array $metrics): static
    {
        $this->metrics = array_merge($this->metrics, $metrics);

        return $this;
    }

    /**
     * Register a single metric.
     */
    public function metric(string $metric): static
    {
        $this->metrics[] = $metric;

        return $this;
    }

    /**
     * Get all registered metrics.
     */
    public function getMetrics(): Collection
    {
        return collect($this->metrics);
    }

    /**
     * Get the admin panel path.
     */
    public function path(string $path = ''): string
    {
        $basePath = config('admin-panel.path', '/admin');

        return $basePath . ($path ? '/' . ltrim($path, '/') : '');
    }

    /**
     * Generate a route to an admin panel page.
     */
    public function route(string $name, array $parameters = []): string
    {
        return route('admin-panel.' . $name, $parameters);
    }

    /**
     * Check if the current user can access the admin panel.
     */
    public function check(): bool
    {
        $guard = config('admin-panel.auth.guard', 'web');

        return auth($guard)->check();
    }

    /**
     * Get the current admin user.
     */
    public function user()
    {
        $guard = config('admin-panel.auth.guard', 'web');

        return auth($guard)->user();
    }

    /**
     * Get the admin panel version.
     */
    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * Find a resource by its URI key.
     */
    public function findResource(string $uriKey): ?Resource
    {
        // First check manually registered resources
        $manualResource = $this->getResources()->first(function (Resource $resource) use ($uriKey) {
            return $resource::uriKey() === $uriKey;
        });

        if ($manualResource) {
            return $manualResource;
        }

        // Then check discovered resources
        return $this->discovery->findByUriKey($uriKey);
    }

    /**
     * Get resources available for navigation.
     */
    public function getNavigationResources(): Collection
    {
        return $this->getResources()->filter(function (Resource $resource) {
            return $resource::availableForNavigation(request());
        });
    }

    /**
     * Get resources grouped by their logical group.
     */
    public function getGroupedResources(): Collection
    {
        return $this->getResources()->groupBy(function (Resource $resource) {
            return $resource::$group ?? 'Default';
        });
    }

    /**
     * Get globally searchable resources.
     */
    public function getSearchableResources(): Collection
    {
        return $this->getResources()->filter(function (Resource $resource) {
            return $resource::$globallySearchable;
        });
    }

    /**
     * Clear the resource discovery cache.
     */
    public function clearResourceCache(): void
    {
        $this->discovery->clearCache();
    }
}
