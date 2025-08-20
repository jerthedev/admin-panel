<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Http\Controllers\PageController;
use JTD\AdminPanel\Resources\Resource;

/**
 * AdminPanel Facade.
 *
 * Main facade for registering resources, pages, and managing
 * the admin panel configuration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
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
     * Registered cards.
     */
    protected array $cards = [];

    /**
     * Dashboard metrics.
     */
    protected array $metrics = [];

    /**
     * Registered dashboards (class names).
     */
    protected array $dashboards = [];

    /**
     * Registered dashboard instances.
     *
     * @var array<int, \JTD\AdminPanel\Dashboards\Dashboard>
     */
    protected array $dashboardInstances = [];

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
     * Card discovery service.
     */
    protected CardDiscovery $cardDiscovery;

    /**
     * The main menu closure.
     */
    protected static $mainMenuCallback = null;

    /**
     * The user menu closure.
     */
    protected static $userMenuCallback = null;

    /**
     * Create a new AdminPanel instance.
     */
    public function __construct()
    {
        $this->discovery = new ResourceDiscovery;
        $this->pageDiscovery = new PageDiscovery;
        $this->pageRegistry = new PageRegistry;
        $this->cardDiscovery = new CardDiscovery;
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
                "Resource [{$resource}] must extend ".Resource::class,
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
            return new $resource;
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
            Route::get($uriPath.'/{component}', [PageController::class, 'show'])
                ->name($shortRouteName.'.component')
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
     * Register cards with the admin panel (static version for AdminServiceProvider).
     */
    public static function cards(array $cards): void
    {
        $instance = app(static::class);
        $instance->registerCards($cards);
    }

    /**
     * Register cards with the admin panel (instance version).
     */
    public function registerCards(array $cards): static
    {
        foreach ($cards as $card) {
            $this->card($card);
        }

        return $this;
    }

    /**
     * Register a single card.
     */
    public function card(string $card): static
    {
        if (! is_subclass_of($card, Card::class)) {
            throw new \InvalidArgumentException(
                "Card [{$card}] must extend ".Card::class,
            );
        }

        $this->cards[] = $card;

        return $this;
    }

    /**
     * Automatically discover and register cards from a directory.
     */
    public static function cardsIn(string $path): void
    {
        $instance = app(static::class);
        $instance->discoverCardsIn($path);
    }

    /**
     * Discover cards in a specific directory (instance version).
     */
    public function discoverCardsIn(string $path): static
    {
        $discoveredCards = $this->cardDiscovery->discoverIn($path);

        if ($discoveredCards->isNotEmpty()) {
            $this->registerCards($discoveredCards->toArray());
        }

        return $this;
    }

    /**
     * Get all registered cards.
     */
    public function getCards(): Collection
    {
        // Combine manually registered cards with discovered cards
        $manualCards = collect($this->cards)->map(function (string $card) {
            return new $card;
        });

        $discoveredCards = $this->cardDiscovery->getCardInstances();

        return $manualCards->merge($discoveredCards)->unique(function (Card $card) {
            return get_class($card);
        });
    }

    /**
     * Get cards grouped by their group.
     */
    public function getGroupedCards(): Collection
    {
        return $this->getCards()
            ->groupBy(function (Card $card) {
                return $card->meta()['group'] ?? 'Default';
            })
            ->map(function (Collection $groupCards) {
                // Sort cards alphabetically within each group
                return $groupCards->sortBy(function (Card $card) {
                    return $card->name();
                })->values();
            });
    }

    /**
     * Find a card by its URI key.
     */
    public function findCard(string $uriKey): ?Card
    {
        // First check manually registered cards
        $manualCard = $this->getCards()->first(function (Card $card) use ($uriKey) {
            return $card->uriKey() === $uriKey;
        });

        if ($manualCard) {
            return $manualCard;
        }

        // Then check discovered cards
        return $this->cardDiscovery->findByUriKey($uriKey);
    }

    /**
     * Get cards available for the given request.
     */
    public function getAuthorizedCards(Request $request): Collection
    {
        return $this->getCards()->filter(function (Card $card) use ($request) {
            return $card->authorize($request);
        });
    }

    /**
     * Get cards grouped by their group for the given request.
     */
    public function getAuthorizedGroupedCards(Request $request): Collection
    {
        return $this->getAuthorizedCards($request)
            ->groupBy(function (Card $card) {
                return $card->meta()['group'] ?? 'Default';
            })
            ->map(function (Collection $groupCards) {
                // Sort cards alphabetically within each group
                return $groupCards->sortBy(function (Card $card) {
                    return $card->name();
                })->values();
            });
    }

    /**
     * Clear the card discovery cache.
     */
    public function clearCardCache(): void
    {
        $this->cardDiscovery->clearCache();
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
     * Register dashboards (static version for AdminServiceProvider).
     *
     * Nova v5 compatible method that accepts both dashboard class names
     * and dashboard instances with method chaining.
     *
     * @param array<int, string|\JTD\AdminPanel\Dashboards\Dashboard> $dashboards
     */
    public static function dashboards(array $dashboards): void
    {
        $instance = app(static::class);
        $instance->registerDashboards($dashboards);
    }

    /**
     * Register dashboards (instance version).
     *
     * Supports both dashboard class names and dashboard instances.
     *
     * @param array<int, string|\JTD\AdminPanel\Dashboards\Dashboard> $dashboards
     */
    public function registerDashboards(array $dashboards): static
    {
        foreach ($dashboards as $dashboard) {
            if (is_string($dashboard)) {
                // Legacy support: dashboard class name
                $this->dashboards[] = $dashboard;
            } elseif ($dashboard instanceof \JTD\AdminPanel\Dashboards\Dashboard) {
                // Nova v5 style: dashboard instance
                $this->dashboardInstances[] = $dashboard;
            }
        }

        return $this;
    }

    /**
     * Register a single dashboard.
     */
    public function dashboard(string|\JTD\AdminPanel\Dashboards\Dashboard $dashboard): static
    {
        if (is_string($dashboard)) {
            $this->dashboards[] = $dashboard;
        } else {
            $this->dashboardInstances[] = $dashboard;
        }

        return $this;
    }

    /**
     * Get all registered dashboard class names.
     */
    public function getDashboards(): Collection
    {
        return collect($this->dashboards);
    }

    /**
     * Get all registered dashboard instances.
     *
     * @return \Illuminate\Support\Collection<int, \JTD\AdminPanel\Dashboards\Dashboard>
     */
    public function getDashboardInstances(): Collection
    {
        return collect($this->dashboardInstances);
    }

    /**
     * Get all dashboard instances (both from class names and instances).
     *
     * @return \Illuminate\Support\Collection<int, \JTD\AdminPanel\Dashboards\Dashboard>
     */
    public function getAllDashboardInstances(): Collection
    {
        // Get instances from class names
        $classInstances = $this->getDashboards()->map(function (string $dashboardClass) {
            return new $dashboardClass;
        });

        // Merge with direct instances
        return $classInstances->merge($this->getDashboardInstances());
    }

    /**
     * Find a dashboard instance by URI key.
     */
    public function findDashboardByUriKey(string $uriKey): ?\JTD\AdminPanel\Dashboards\Dashboard
    {
        return $this->getAllDashboardInstances()->first(function (\JTD\AdminPanel\Dashboards\Dashboard $dashboard) use ($uriKey) {
            return $dashboard->uriKey() === $uriKey;
        });
    }

    /**
     * Get dashboards available for navigation.
     *
     * @return \Illuminate\Support\Collection<int, \JTD\AdminPanel\Dashboards\Dashboard>
     */
    public function getNavigationDashboards(?\Illuminate\Http\Request $request = null): Collection
    {
        $request = $request ?: request();

        return $this->getAllDashboardInstances()->filter(function (\JTD\AdminPanel\Dashboards\Dashboard $dashboard) use ($request) {
            return $dashboard->authorizedToSee($request);
        });
    }

    /**
     * Generate dashboard menu items for navigation.
     *
     * @return \Illuminate\Support\Collection<int, \JTD\AdminPanel\Menu\MenuItem>
     */
    public function getDashboardMenuItems(?\Illuminate\Http\Request $request = null): Collection
    {
        $request = $request ?: request();

        return $this->getNavigationDashboards($request)->map(function (\JTD\AdminPanel\Dashboards\Dashboard $dashboard) use ($request) {
            return $dashboard->menu($request);
        });
    }

    /**
     * Create a dashboard navigation section.
     */
    public function createDashboardNavigationSection(?\Illuminate\Http\Request $request = null): ?\JTD\AdminPanel\Menu\MenuSection
    {
        $request = $request ?: request();

        // Check if dashboard navigation is enabled
        if (! config('admin-panel.dashboard.dashboard_navigation.show_in_navigation', true)) {
            return null;
        }

        $dashboardMenuItems = $this->getDashboardMenuItems($request);

        if ($dashboardMenuItems->isEmpty()) {
            return null;
        }

        // Check if we should group multiple dashboards
        if (! config('admin-panel.dashboard.dashboard_navigation.group_multiple_dashboards', true)) {
            return null;
        }

        // If there's only one dashboard (Main), don't create a section
        if ($dashboardMenuItems->count() === 1) {
            $dashboard = $this->getNavigationDashboards($request)->first();
            if ($dashboard->uriKey() === 'main' && config('admin-panel.dashboard.dashboard_navigation.show_main_dashboard_separately', true)) {
                return null; // Main dashboard is handled separately
            }
        }

        $sectionIcon = config('admin-panel.dashboard.dashboard_navigation.section_icon', 'chart-bar');

        return \JTD\AdminPanel\Menu\MenuSection::make('Dashboards', $dashboardMenuItems->toArray())
            ->icon($sectionIcon);
    }

    /**
     * Get the main dashboard menu item.
     */
    public function getMainDashboardMenuItem(?\Illuminate\Http\Request $request = null): ?\JTD\AdminPanel\Menu\MenuItem
    {
        $request = $request ?: request();

        // Check if dashboard navigation is enabled
        if (! config('admin-panel.dashboard.dashboard_navigation.show_in_navigation', true)) {
            return null;
        }

        // Check if main dashboard should be shown separately
        if (! config('admin-panel.dashboard.dashboard_navigation.show_main_dashboard_separately', true)) {
            return null;
        }

        $mainDashboard = $this->findDashboardByUriKey('main');

        if (! $mainDashboard || ! $mainDashboard->authorizedToSee($request)) {
            return null;
        }

        $mainIcon = config('admin-panel.dashboard.dashboard_navigation.main_dashboard_icon', 'home');

        return $mainDashboard->menu($request)->withIcon($mainIcon);
    }

    /**
     * Get the admin panel path.
     */
    public function path(string $path = ''): string
    {
        $basePath = config('admin-panel.path', '/admin');

        return $basePath.($path ? '/'.ltrim($path, '/') : '');
    }

    /**
     * Generate a route to an admin panel page.
     */
    public function route(string $name, array $parameters = []): string
    {
        return route('admin-panel.'.$name, $parameters);
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
            return new $pageClass;
        });
    }

    /**
     * Get available app components from the resources/js/admin-pages directory.
     */
    public function getAvailableAppComponents(): array
    {
        $resolver = new \JTD\AdminPanel\Support\ComponentResolver;

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

    /**
     * Register a main menu callback.
     */
    public static function mainMenu(callable $callback): void
    {
        static::$mainMenuCallback = $callback;
    }

    /**
     * Check if a custom main menu has been registered.
     */
    public static function hasCustomMainMenu(): bool
    {
        return static::$mainMenuCallback !== null;
    }

    /**
     * Resolve the main menu using the registered callback.
     */
    public static function resolveMainMenu(\Illuminate\Http\Request $request): array
    {
        if (static::$mainMenuCallback === null) {
            return [];
        }

        $result = call_user_func(static::$mainMenuCallback, $request);

        return is_array($result) ? $result : [];
    }

    /**
     * Serialize the main menu for frontend consumption.
     */
    public static function serializeMainMenu(array $menu, \Illuminate\Http\Request $request): array
    {
        $filteredMenu = static::filterAuthorizedMenuItems($menu, $request);

        return array_map(function ($item) use ($request) {
            return $item->toArray($request);
        }, $filteredMenu);
    }

    /**
     * Filter menu items based on authorization.
     */
    protected static function filterAuthorizedMenuItems(array $menu, \Illuminate\Http\Request $request): array
    {
        $filtered = [];

        foreach ($menu as $item) {
            // Check if the item itself is visible
            if (! $item->isVisible($request)) {
                continue;
            }

            // If it's a section or group with items, filter the children
            if ($item instanceof \JTD\AdminPanel\Menu\MenuSection || $item instanceof \JTD\AdminPanel\Menu\MenuGroup) {
                $children = $item->items ?? [];

                if (! empty($children)) {
                    $filteredChildren = static::filterAuthorizedMenuItems($children, $request);

                    // Only include the section/group if it has visible children or is not collapsible
                    if (! empty($filteredChildren) || ! $item->collapsible) {
                        $item->items = $filteredChildren;
                        $filtered[] = $item;
                    }
                } else {
                    // Empty section/group - include if not collapsible
                    if (! $item->collapsible) {
                        $filtered[] = $item;
                    }
                }
            } else {
                // Regular menu item - include if visible
                $filtered[] = $item;
            }
        }

        return $filtered;
    }

    /**
     * Clear the main menu callback (for testing).
     */
    public static function clearMainMenu(): void
    {
        static::$mainMenuCallback = null;
    }

    /**
     * Register a user menu callback.
     */
    public static function userMenu(callable $callback): void
    {
        static::$userMenuCallback = $callback;
    }

    /**
     * Check if a custom user menu has been registered.
     */
    public static function hasCustomUserMenu(): bool
    {
        return static::$userMenuCallback !== null;
    }

    /**
     * Resolve the user menu using the registered callback.
     */
    public static function resolveUserMenu(\Illuminate\Http\Request $request): ?\JTD\AdminPanel\Menu\Menu
    {
        if (static::$userMenuCallback === null) {
            return null;
        }

        // Create a default menu instance
        $menu = new \JTD\AdminPanel\Menu\Menu;

        $result = call_user_func(static::$userMenuCallback, $request, $menu);
        $finalMenu = $result instanceof \JTD\AdminPanel\Menu\Menu ? $result : $menu;

        // Add default logout link at the end (unless one already exists)
        $hasLogout = false;
        foreach ($finalMenu->getItems() as $item) {
            if ($item->url === '/logout' || ($item->meta['default'] ?? false)) {
                $hasLogout = true;
                break;
            }
        }

        if (! $hasLogout) {
            $finalMenu->append(
                \JTD\AdminPanel\Menu\MenuItem::make('Sign out', '/logout')
                    ->withIcon('arrow-right-on-rectangle')
                    ->meta('method', 'post')
                    ->meta('default', true),
            );
        }

        return $finalMenu;
    }

    /**
     * Clear the user menu callback (for testing).
     */
    public static function clearUserMenu(): void
    {
        static::$userMenuCallback = null;
    }
}
