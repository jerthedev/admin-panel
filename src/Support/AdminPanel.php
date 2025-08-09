<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use JTD\AdminPanel\Http\Controllers\PageController;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Support\ResourceDiscovery;
use JTD\AdminPanel\Support\PageDiscovery;
use JTD\AdminPanel\Support\PageRegistry;

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
     * Page discovery service.
     */
    protected PageDiscovery $pageDiscovery;

    /**
     * Page registry service.
     */
    protected PageRegistry $pageRegistry;

    /**
     * Create a new AdminPanel instance.
     */
    public function __construct()
    {
        $this->discovery = new ResourceDiscovery();
        $this->pageDiscovery = new PageDiscovery();
        $this->pageRegistry = new PageRegistry();
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
     * Register pages with the admin panel (static version for AdminServiceProvider).
     */
    public static function pages(array $pages): void
    {
        $instance = app(static::class);
        $instance->registerPages($pages);
    }

    /**
     * Register pages with the admin panel (instance version).
     */
    public function registerPages(array $pages): static
    {
        $this->pageRegistry->register($pages);
        $this->pages = array_merge($this->pages, $pages);

        return $this;
    }

    /**
     * Register a single page.
     */
    public function page(string $page): static
    {
        $this->pageRegistry->page($page);
        $this->pages[] = $page;

        return $this;
    }

    /**
     * Automatically discover and register pages from a directory.
     */
    public static function pagesIn(string $path): void
    {
        $instance = app(static::class);
        $instance->discoverPagesIn($path);
    }

    /**
     * Discover pages in a specific directory (instance version).
     */
    public function discoverPagesIn(string $path): static
    {
        $discoveredPages = $this->pageDiscovery->discoverIn($path);

        if ($discoveredPages->isNotEmpty()) {
            $this->registerPages($discoveredPages->toArray());
        }

        return $this;
    }

    /**
     * Get all registered pages.
     */
    public function getPages(): Collection
    {
        // Combine manually registered pages with discovered pages
        $manualPages = collect($this->pages);
        $discoveredPages = $this->pageDiscovery->discover();

        return $manualPages->merge($discoveredPages)->unique();
    }

    /**
     * Get the page registry instance.
     */
    public function getPageRegistry(): PageRegistry
    {
        return $this->pageRegistry;
    }

    /**
     * Register routes for all registered pages.
     */
    public function registerPageRoutes(): void
    {
        $pages = $this->getPages();

        foreach ($pages as $pageClass) {
            $this->registerPageRoute($pageClass);
        }
    }

    /**
     * Register a route for a specific page.
     */
    protected function registerPageRoute(string $pageClass): void
    {
        $routeName = $pageClass::routeName();
        $uriPath = $pageClass::uriPath();

        // Remove the 'admin-panel.' prefix for the route name since it will be added by the route group
        $shortRouteName = str_replace('admin-panel.', '', $routeName);

        // Register primary route (no component parameter)
        Route::get($uriPath, [PageController::class, 'show'])
            ->name($shortRouteName);

        // Register multi-component routes if page has multiple components
        if ($pageClass::hasMultipleComponents()) {
            Route::get($uriPath . '/{component}', [PageController::class, 'show'])
                ->name($shortRouteName . '.component')
                ->where('component', '[a-zA-Z0-9_-]+');
        }
    }

    /**
     * Get all page routes that should be registered.
     */
    public function getPageRoutes(): Collection
    {
        return $this->getPages()->map(function (string $pageClass) {
            return [
                'name' => $pageClass::routeName(),
                'uri' => $pageClass::uriPath(),
                'class' => $pageClass,
            ];
        });
    }

    /**
     * Register dashboard metrics (static version for AdminServiceProvider).
     */
    public static function metrics(array $metrics): void
    {
        $instance = app(static::class);
        $instance->registerMetrics($metrics);
    }

    /**
     * Register dashboard metrics (instance version).
     */
    public function registerMetrics(array $metrics): static
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
     * Get pages available for navigation.
     */
    public function getNavigationPages(?Request $request = null): Collection
    {
        $request = $request ?: request();

        return $this->getPageInstances()->filter(function ($page) use ($request) {
            return $page::availableForNavigation($request);
        });
    }

    /**
     * Get page instances from all registered pages.
     */
    public function getPageInstances(): Collection
    {
        return $this->getPages()->map(function (string $pageClass) {
            return new $pageClass();
        });
    }

    /**
     * Get available app components from the resources/js/admin-pages directory.
     */
    public function getAvailableAppComponents(): array
    {
        $resolver = new \JTD\AdminPanel\Support\ComponentResolver();
        return $resolver->getAvailableAppComponents();
    }

    /**
     * Register a custom page manifest for multi-package support (static version for service providers).
     */
    public static function registerCustomPageManifest(array $config): void
    {
        $instance = app(static::class);
        $instance->getManifestRegistry()->register($config);
    }

    /**
     * Register a custom page manifest for multi-package support (instance version).
     */
    public function registerCustomPageManifestInstance(array $config): void
    {
        $this->getManifestRegistry()->register($config);
    }

    /**
     * Get the manifest registry instance.
     */
    public function getManifestRegistry(): \JTD\AdminPanel\Support\CustomPageManifestRegistry
    {
        return app(\JTD\AdminPanel\Support\CustomPageManifestRegistry::class);
    }

    /**
     * Get aggregated manifest for all registered custom pages.
     */
    public function getAggregatedManifest(): array
    {
        return $this->getManifestRegistry()->getAggregatedManifest();
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
