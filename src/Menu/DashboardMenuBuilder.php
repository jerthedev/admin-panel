<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Menu;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Dashboard Menu Builder
 * 
 * Builds menu items and sections for dashboards with support for
 * grouping, categorization, and Nova v5 compatibility.
 */
class DashboardMenuBuilder
{
    /**
     * Build dashboard menu items from registered dashboards.
     */
    public static function buildDashboardMenuItems(Request $request): Collection
    {
        $adminPanel = app(AdminPanel::class);
        $dashboards = $adminPanel->getAllDashboardInstances();
        
        return $dashboards
            ->filter(fn($dashboard) => $dashboard->authorizedToSee($request))
            ->map(fn($dashboard) => static::buildDashboardMenuItem($dashboard, $request))
            ->filter()
            ->values();
    }

    /**
     * Build a single dashboard menu item.
     */
    public static function buildDashboardMenuItem(Dashboard $dashboard, Request $request): ?MenuItem
    {
        try {
            $menuItem = $dashboard->menu($request);
            
            // Enhance with dashboard metadata
            return $menuItem
                ->withIcon($dashboard->icon() ?? 'chart-bar')
                ->meta('dashboard', true)
                ->meta('dashboard_uri_key', $dashboard->uriKey())
                ->meta('dashboard_name', $dashboard->name())
                ->meta('dashboard_description', $dashboard->description())
                ->meta('dashboard_category', $dashboard->category())
                ->canSee(fn($req) => $dashboard->authorizedToSee($req));
                
        } catch (\Exception $e) {
            // Log error and skip this dashboard
            logger()->warning("Failed to build menu item for dashboard: {$dashboard->uriKey()}", [
                'error' => $e->getMessage(),
                'dashboard' => get_class($dashboard)
            ]);
            
            return null;
        }
    }

    /**
     * Build dashboard menu sections grouped by category.
     */
    public static function buildDashboardMenuSections(Request $request): Collection
    {
        $adminPanel = app(AdminPanel::class);
        $dashboards = $adminPanel->getAllDashboardInstances();
        
        // Group dashboards by category
        $grouped = $dashboards
            ->filter(fn($dashboard) => $dashboard->authorizedToSee($request))
            ->groupBy(fn($dashboard) => $dashboard->category() ?? 'General');

        return $grouped->map(function ($dashboards, $category) use ($request) {
            $menuItems = $dashboards
                ->map(fn($dashboard) => static::buildDashboardMenuItem($dashboard, $request))
                ->filter()
                ->values();

            if ($menuItems->isEmpty()) {
                return null;
            }

            return MenuSection::make($category, $menuItems->toArray())
                ->icon(static::getCategoryIcon($category))
                ->meta('dashboard_category', true)
                ->meta('category_name', $category);
        })->filter()->values();
    }

    /**
     * Build a single dashboard menu section.
     */
    public static function buildDashboardMenuSection(
        string $title, 
        array $dashboardClasses, 
        Request $request,
        array $options = []
    ): ?MenuSection {
        $menuItems = collect($dashboardClasses)
            ->map(function ($dashboardClass) use ($request) {
                $dashboard = app($dashboardClass);
                return static::buildDashboardMenuItem($dashboard, $request);
            })
            ->filter()
            ->values();

        if ($menuItems->isEmpty()) {
            return null;
        }

        $section = MenuSection::make($title, $menuItems->toArray());

        // Apply options
        if (isset($options['icon'])) {
            $section->icon($options['icon']);
        }

        if (isset($options['collapsible']) && $options['collapsible']) {
            $section->collapsible();
        }

        if (isset($options['badge'])) {
            $section->withBadge($options['badge']['value'], $options['badge']['type'] ?? 'primary');
        }

        if (isset($options['canSee'])) {
            $section->canSee($options['canSee']);
        }

        return $section->meta('dashboard_section', true);
    }

    /**
     * Build main dashboard menu item (special handling for main dashboard).
     */
    public static function buildMainDashboardMenuItem(Request $request): ?MenuItem
    {
        $adminPanel = app(AdminPanel::class);
        $mainDashboard = $adminPanel->getAllDashboardInstances()
            ->first(fn($dashboard) => $dashboard->uriKey() === 'main');

        if (!$mainDashboard || !$mainDashboard->authorizedToSee($request)) {
            return null;
        }

        return MenuItem::make($mainDashboard->name(), route('admin-panel.dashboard'))
            ->withIcon($mainDashboard->icon() ?? 'home')
            ->meta('main_dashboard', true)
            ->meta('dashboard', true)
            ->meta('dashboard_uri_key', 'main')
            ->canSee(fn($req) => $mainDashboard->authorizedToSee($req));
    }

    /**
     * Build dashboard quick access menu items.
     */
    public static function buildQuickAccessMenuItems(Request $request, int $limit = 5): Collection
    {
        // Get recently accessed dashboards from session or user preferences
        $recentDashboards = static::getRecentDashboards($request, $limit);
        
        return $recentDashboards
            ->map(fn($dashboard) => static::buildDashboardMenuItem($dashboard, $request))
            ->filter()
            ->values();
    }

    /**
     * Build dashboard favorites menu items.
     */
    public static function buildFavoritesMenuItems(Request $request): Collection
    {
        $favoriteDashboards = static::getFavoriteDashboards($request);
        
        return $favoriteDashboards
            ->map(fn($dashboard) => static::buildDashboardMenuItem($dashboard, $request))
            ->filter()
            ->values();
    }

    /**
     * Get category icon based on category name.
     */
    protected static function getCategoryIcon(string $category): string
    {
        $icons = [
            'Analytics' => 'chart-bar',
            'Reports' => 'document-text',
            'Overview' => 'home',
            'General' => 'view-grid',
            'Business' => 'briefcase',
            'Financial' => 'currency-dollar',
            'Users' => 'users',
            'Content' => 'document-duplicate',
            'System' => 'cog',
            'Monitoring' => 'eye',
            'Security' => 'shield-check',
            'Marketing' => 'megaphone',
            'Sales' => 'trending-up',
            'Support' => 'support',
            'Admin' => 'user-circle',
        ];

        return $icons[$category] ?? 'view-grid';
    }

    /**
     * Get recent dashboards from session or user preferences.
     */
    protected static function getRecentDashboards(Request $request, int $limit): Collection
    {
        // Get from session
        $recentUriKeys = session('dashboard_recent', []);
        
        if (empty($recentUriKeys)) {
            // Fallback to main dashboard
            $adminPanel = app(AdminPanel::class);
            $mainDashboard = $adminPanel->getAllDashboardInstances()
                ->first(fn($dashboard) => $dashboard->uriKey() === 'main');
                
            return $mainDashboard ? collect([$mainDashboard]) : collect();
        }

        $adminPanel = app(AdminPanel::class);
        return collect($recentUriKeys)
            ->take($limit)
            ->map(function ($uriKey) use ($adminPanel) {
                return $adminPanel->getAllDashboardInstances()
                    ->first(fn($dashboard) => $dashboard->uriKey() === $uriKey);
            })
            ->filter();
    }

    /**
     * Get favorite dashboards from user preferences.
     */
    protected static function getFavoriteDashboards(Request $request): Collection
    {
        $user = $request->user();
        
        if (!$user) {
            return collect();
        }

        // Get from user preferences (assuming a preferences system)
        $favoriteUriKeys = $user->preferences['dashboard_favorites'] ?? [];
        
        $adminPanel = app(AdminPanel::class);
        return collect($favoriteUriKeys)
            ->map(function ($uriKey) use ($adminPanel) {
                return $adminPanel->getAllDashboardInstances()
                    ->first(fn($dashboard) => $dashboard->uriKey() === $uriKey);
            })
            ->filter();
    }

    /**
     * Build dashboard menu with smart grouping.
     */
    public static function buildSmartDashboardMenu(Request $request, array $options = []): array
    {
        $menu = [];
        
        // Main dashboard (always first)
        $mainMenuItem = static::buildMainDashboardMenuItem($request);
        if ($mainMenuItem) {
            $menu[] = $mainMenuItem;
        }

        // Quick access section (if enabled)
        if ($options['show_quick_access'] ?? true) {
            $quickAccessItems = static::buildQuickAccessMenuItems($request, $options['quick_access_limit'] ?? 3);
            if ($quickAccessItems->isNotEmpty()) {
                $menu[] = MenuSection::make('Quick Access', $quickAccessItems->toArray())
                    ->icon('lightning-bolt')
                    ->meta('quick_access', true);
            }
        }

        // Favorites section (if enabled and has favorites)
        if ($options['show_favorites'] ?? true) {
            $favoriteItems = static::buildFavoritesMenuItems($request);
            if ($favoriteItems->isNotEmpty()) {
                $menu[] = MenuSection::make('Favorites', $favoriteItems->toArray())
                    ->icon('star')
                    ->meta('favorites', true);
            }
        }

        // Categorized sections
        if ($options['group_by_category'] ?? true) {
            $categorySections = static::buildDashboardMenuSections($request);
            $menu = array_merge($menu, $categorySections->toArray());
        } else {
            // All dashboards in one section
            $allItems = static::buildDashboardMenuItems($request);
            if ($allItems->isNotEmpty()) {
                $menu[] = MenuSection::make('Dashboards', $allItems->toArray())
                    ->icon('view-grid')
                    ->collapsible();
            }
        }

        return $menu;
    }

    /**
     * Build dashboard menu for Nova v5 compatibility.
     */
    public static function buildNovaCompatibleMenu(Request $request): array
    {
        return static::buildSmartDashboardMenu($request, [
            'show_quick_access' => true,
            'show_favorites' => true,
            'group_by_category' => true,
            'quick_access_limit' => 3,
        ]);
    }
}
