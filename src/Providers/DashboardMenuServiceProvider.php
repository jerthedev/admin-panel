<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Providers;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Menu\DashboardMenuBuilder;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Dashboard Menu Service Provider
 * 
 * Registers dashboard menu integration with the admin panel menu system.
 * Provides automatic dashboard menu generation and Nova v5 compatibility.
 */
class DashboardMenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register dashboard menu builder
        $this->app->singleton(DashboardMenuBuilder::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerDashboardMenuMacros();
        $this->registerDefaultDashboardMenu();
    }

    /**
     * Register dashboard menu helper methods (Nova-compatible approach).
     */
    protected function registerDashboardMenuMacros(): void
    {
        // Register dashboard menu builder as a singleton service
        // This provides Nova-compatible functionality without using macros
        $this->app->singleton('admin-panel.dashboard-menu', function ($app) {
            return new DashboardMenuBuilder();
        });
    }

    /**
     * Register default dashboard menu if no custom menu is defined.
     */
    protected function registerDefaultDashboardMenu(): void
    {
        // Only register if no main menu callback is already set
        if (!AdminPanel::hasCustomMainMenu()) {
            AdminPanel::mainMenu(function (Request $request) {
                return $this->buildDefaultDashboardMenu($request);
            });
        }
    }

    /**
     * Build the default dashboard menu structure.
     */
    protected function buildDefaultDashboardMenu(Request $request): array
    {
        $menu = [];

        // Main dashboard (always first)
        $mainMenuItem = DashboardMenuBuilder::buildMainDashboardMenuItem($request);
        if ($mainMenuItem) {
            $menu[] = $mainMenuItem;
        }

        // Get dashboard configuration
        $config = config('admin-panel.dashboard.menu', []);
        $showQuickAccess = $config['show_quick_access'] ?? true;
        $showFavorites = $config['show_favorites'] ?? true;
        $groupByCategory = $config['group_by_category'] ?? true;
        $quickAccessLimit = $config['quick_access_limit'] ?? 3;

        // Quick access section
        if ($showQuickAccess) {
            $quickAccessItems = DashboardMenuBuilder::buildQuickAccessMenuItems($request, $quickAccessLimit);
            if ($quickAccessItems->isNotEmpty()) {
                $menu[] = MenuSection::make('Quick Access', $quickAccessItems->toArray())
                    ->icon('lightning-bolt')
                    ->collapsible()
                    ->meta('quick_access', true);
            }
        }

        // Favorites section
        if ($showFavorites) {
            $favoriteItems = DashboardMenuBuilder::buildFavoritesMenuItems($request);
            if ($favoriteItems->isNotEmpty()) {
                $menu[] = MenuSection::make('Favorites', $favoriteItems->toArray())
                    ->icon('star')
                    ->collapsible()
                    ->meta('favorites', true);
            }
        }

        // Dashboard sections
        if ($groupByCategory) {
            // Group by category
            $categorySections = DashboardMenuBuilder::buildDashboardMenuSections($request);
            foreach ($categorySections as $section) {
                if ($section) {
                    $menu[] = $section->collapsible();
                }
            }
        } else {
            // All dashboards in one section
            $allItems = DashboardMenuBuilder::buildDashboardMenuItems($request);
            if ($allItems->isNotEmpty()) {
                $menu[] = MenuSection::make('Dashboards', $allItems->toArray())
                    ->icon('view-grid')
                    ->collapsible();
            }
        }

        // Add resources section if any resources are registered
        $adminPanel = app(AdminPanel::class);
        $resources = $adminPanel->getResources();
        if ($resources->isNotEmpty()) {
            $resourceItems = $resources
                ->filter(fn($resource) => $resource->authorizedToViewAny($request))
                ->map(fn($resource) => MenuItem::resource(get_class($resource)))
                ->values()
                ->toArray();

            if (!empty($resourceItems)) {
                $menu[] = MenuSection::make('Resources', $resourceItems)
                    ->icon('collection')
                    ->collapsible();
            }
        }

        return $menu;
    }

    /**
     * Get dashboard menu configuration.
     */
    protected function getDashboardMenuConfig(): array
    {
        return config('admin-panel.dashboard.menu', [
            'show_quick_access' => true,
            'show_favorites' => true,
            'group_by_category' => true,
            'quick_access_limit' => 3,
            'enable_badges' => true,
            'cache_badges' => true,
            'badge_cache_ttl' => 300,
        ]);
    }

    /**
     * Register dashboard menu event listeners.
     */
    protected function registerDashboardMenuEventListeners(): void
    {
        // Listen for dashboard access to update recent dashboards
        $this->app['events']->listen(
            'dashboard.accessed',
            function ($event) {
                $this->updateRecentDashboards($event->dashboard, $event->request);
            }
        );

        // Listen for dashboard favorite changes
        $this->app['events']->listen(
            'dashboard.favorited',
            function ($event) {
                $this->updateFavoriteDashboards($event->dashboard, $event->user, $event->favorited);
            }
        );
    }

    /**
     * Update recent dashboards in session.
     */
    protected function updateRecentDashboards($dashboard, Request $request): void
    {
        $recent = session('dashboard_recent', []);
        $uriKey = $dashboard->uriKey();

        // Remove if already exists
        $recent = array_filter($recent, fn($key) => $key !== $uriKey);

        // Add to beginning
        array_unshift($recent, $uriKey);

        // Limit to 10 recent items
        $recent = array_slice($recent, 0, 10);

        session(['dashboard_recent' => $recent]);
    }

    /**
     * Update favorite dashboards for user.
     */
    protected function updateFavoriteDashboards($dashboard, $user, bool $favorited): void
    {
        if (!$user) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $favorites = $preferences['dashboard_favorites'] ?? [];
        $uriKey = $dashboard->uriKey();

        if ($favorited) {
            if (!in_array($uriKey, $favorites)) {
                $favorites[] = $uriKey;
            }
        } else {
            $favorites = array_filter($favorites, fn($key) => $key !== $uriKey);
        }

        $preferences['dashboard_favorites'] = array_values($favorites);
        $user->preferences = $preferences;
        $user->save();
    }
}
