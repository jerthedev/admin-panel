<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use JTD\AdminPanel\Dashboards\Dashboard;

/**
 * Dashboard Ordering Manager
 * 
 * Manages dashboard ordering, visibility controls, and display logic
 * with support for user preferences and dynamic sorting.
 */
class DashboardOrderingManager
{
    /**
     * Cache key prefix for dashboard ordering.
     */
    protected const CACHE_PREFIX = 'admin_panel_dashboard_ordering';

    /**
     * Cache TTL for dashboard ordering (in seconds).
     */
    protected const CACHE_TTL = 1800; // 30 minutes

    /**
     * Available sorting options.
     */
    protected const SORT_OPTIONS = [
        'priority' => 'Priority',
        'name' => 'Name',
        'category' => 'Category',
        'created_at' => 'Created Date',
        'updated_at' => 'Updated Date',
        'usage_count' => 'Usage Count',
        'last_accessed' => 'Last Accessed',
        'custom' => 'Custom Order',
    ];

    /**
     * Available sort directions.
     */
    protected const SORT_DIRECTIONS = ['asc', 'desc'];

    /**
     * Get ordered dashboards based on criteria.
     */
    public static function getOrderedDashboards(
        Collection $dashboards,
        string $sortBy = 'priority',
        string $direction = 'asc',
        Request $request = null
    ): Collection {
        $request = $request ?? request();
        $cacheKey = static::getOrderingCacheKey($sortBy, $direction, $request->user()?->id);

        if (Cache::has($cacheKey)) {
            $orderedUriKeys = Cache::get($cacheKey);
            return static::sortDashboardsByUriKeys($dashboards, $orderedUriKeys);
        }

        $ordered = static::sortDashboards($dashboards, $sortBy, $direction, $request);
        
        // Cache the ordering
        $orderedUriKeys = $ordered->map(fn($dashboard) => $dashboard->uriKey())->toArray();
        Cache::put($cacheKey, $orderedUriKeys, static::CACHE_TTL);

        return $ordered;
    }

    /**
     * Get visible dashboards based on user permissions and settings.
     */
    public static function getVisibleDashboards(Collection $dashboards, Request $request = null): Collection
    {
        $request = $request ?? request();
        
        return $dashboards->filter(function ($dashboard) use ($request) {
            return static::isDashboardVisible($dashboard, $request);
        });
    }

    /**
     * Get dashboards grouped by category with ordering.
     */
    public static function getGroupedDashboards(
        Collection $dashboards,
        string $sortBy = 'priority',
        string $direction = 'asc',
        Request $request = null
    ): Collection {
        $request = $request ?? request();
        
        // First filter visible dashboards
        $visibleDashboards = static::getVisibleDashboards($dashboards, $request);
        
        // Group by category
        $grouped = $visibleDashboards->groupBy(function ($dashboard) {
            $metadata = DashboardMetadataManager::getMetadata($dashboard);
            return $metadata['category'] ?? 'General';
        });

        // Sort each group
        return $grouped->map(function ($categoryDashboards) use ($sortBy, $direction, $request) {
            return static::sortDashboards($categoryDashboards, $sortBy, $direction, $request);
        });
    }

    /**
     * Set custom dashboard order for a user.
     */
    public static function setCustomOrder(array $dashboardUriKeys, Request $request = null): void
    {
        $request = $request ?? request();
        $user = $request->user();

        if (!$user) {
            return;
        }

        // Store custom order in user preferences
        $preferences = $user->preferences ?? [];
        $preferences['dashboard_custom_order'] = $dashboardUriKeys;
        $user->preferences = $preferences;
        $user->save();

        // Clear ordering cache for this user
        static::clearOrderingCache($user->id);
    }

    /**
     * Get custom dashboard order for a user.
     */
    public static function getCustomOrder(Request $request = null): array
    {
        $request = $request ?? request();
        $user = $request->user();

        if (!$user) {
            return [];
        }

        $preferences = $user->preferences ?? [];
        return $preferences['dashboard_custom_order'] ?? [];
    }

    /**
     * Set dashboard visibility for a user.
     */
    public static function setDashboardVisibility(string $dashboardUriKey, bool $visible, Request $request = null): void
    {
        $request = $request ?? request();
        $user = $request->user();

        if (!$user) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $preferences['dashboard_visibility'][$dashboardUriKey] = $visible;
        $user->preferences = $preferences;
        $user->save();

        // Clear visibility cache for this user
        static::clearVisibilityCache($user->id);
    }

    /**
     * Get dashboard visibility for a user.
     */
    public static function getDashboardVisibility(string $dashboardUriKey, Request $request = null): ?bool
    {
        $request = $request ?? request();
        $user = $request->user();

        if (!$user) {
            return null;
        }

        $preferences = $user->preferences ?? [];
        return $preferences['dashboard_visibility'][$dashboardUriKey] ?? null;
    }

    /**
     * Get user's dashboard sorting preferences.
     */
    public static function getUserSortingPreferences(Request $request = null): array
    {
        $request = $request ?? request();
        $user = $request->user();

        if (!$user) {
            return ['sort_by' => 'priority', 'direction' => 'asc'];
        }

        $preferences = $user->preferences ?? [];
        return [
            'sort_by' => $preferences['dashboard_sort_by'] ?? 'priority',
            'direction' => $preferences['dashboard_sort_direction'] ?? 'asc',
        ];
    }

    /**
     * Set user's dashboard sorting preferences.
     */
    public static function setUserSortingPreferences(string $sortBy, string $direction, Request $request = null): void
    {
        $request = $request ?? request();
        $user = $request->user();

        if (!$user || !in_array($sortBy, array_keys(static::SORT_OPTIONS)) || !in_array($direction, static::SORT_DIRECTIONS)) {
            return;
        }

        $preferences = $user->preferences ?? [];
        $preferences['dashboard_sort_by'] = $sortBy;
        $preferences['dashboard_sort_direction'] = $direction;
        $user->preferences = $preferences;
        $user->save();

        // Clear ordering cache for this user
        static::clearOrderingCache($user->id);
    }

    /**
     * Sort dashboards by specified criteria.
     */
    protected static function sortDashboards(Collection $dashboards, string $sortBy, string $direction, Request $request): Collection
    {
        switch ($sortBy) {
            case 'priority':
                return static::sortByPriority($dashboards, $direction);
            
            case 'name':
                return static::sortByName($dashboards, $direction);
            
            case 'category':
                return static::sortByCategory($dashboards, $direction);
            
            case 'created_at':
                return static::sortByCreatedAt($dashboards, $direction);
            
            case 'updated_at':
                return static::sortByUpdatedAt($dashboards, $direction);
            
            case 'usage_count':
                return static::sortByUsageCount($dashboards, $direction, $request);
            
            case 'last_accessed':
                return static::sortByLastAccessed($dashboards, $direction, $request);
            
            case 'custom':
                return static::sortByCustomOrder($dashboards, $request);
            
            default:
                return static::sortByPriority($dashboards, $direction);
        }
    }

    /**
     * Sort dashboards by priority.
     */
    protected static function sortByPriority(Collection $dashboards, string $direction): Collection
    {
        return $dashboards->sortBy(function ($dashboard) {
            $metadata = DashboardMetadataManager::getMetadata($dashboard);
            return $metadata['priority'] ?? 100;
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Sort dashboards by name.
     */
    protected static function sortByName(Collection $dashboards, string $direction): Collection
    {
        return $dashboards->sortBy(function ($dashboard) {
            return $dashboard->name();
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Sort dashboards by category.
     */
    protected static function sortByCategory(Collection $dashboards, string $direction): Collection
    {
        return $dashboards->sortBy(function ($dashboard) {
            $metadata = DashboardMetadataManager::getMetadata($dashboard);
            return $metadata['category'] ?? 'General';
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Sort dashboards by created date.
     */
    protected static function sortByCreatedAt(Collection $dashboards, string $direction): Collection
    {
        return $dashboards->sortBy(function ($dashboard) {
            $metadata = DashboardMetadataManager::getMetadata($dashboard);
            return $metadata['created_at'] ?? now();
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Sort dashboards by updated date.
     */
    protected static function sortByUpdatedAt(Collection $dashboards, string $direction): Collection
    {
        return $dashboards->sortBy(function ($dashboard) {
            $metadata = DashboardMetadataManager::getMetadata($dashboard);
            return $metadata['updated_at'] ?? now();
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Sort dashboards by usage count.
     */
    protected static function sortByUsageCount(Collection $dashboards, string $direction, Request $request): Collection
    {
        return $dashboards->sortBy(function ($dashboard) use ($request) {
            return static::getDashboardUsageCount($dashboard->uriKey(), $request);
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Sort dashboards by last accessed date.
     */
    protected static function sortByLastAccessed(Collection $dashboards, string $direction, Request $request): Collection
    {
        return $dashboards->sortBy(function ($dashboard) use ($request) {
            return static::getDashboardLastAccessed($dashboard->uriKey(), $request);
        }, SORT_REGULAR, $direction === 'desc')->values();
    }

    /**
     * Sort dashboards by custom order.
     */
    protected static function sortByCustomOrder(Collection $dashboards, Request $request): Collection
    {
        $customOrder = static::getCustomOrder($request);
        
        if (empty($customOrder)) {
            return static::sortByPriority($dashboards, 'asc');
        }

        return static::sortDashboardsByUriKeys($dashboards, $customOrder);
    }

    /**
     * Sort dashboards by URI key order.
     */
    protected static function sortDashboardsByUriKeys(Collection $dashboards, array $uriKeys): Collection
    {
        $indexed = $dashboards->keyBy(fn($dashboard) => $dashboard->uriKey());
        $sorted = collect();

        // Add dashboards in the specified order
        foreach ($uriKeys as $uriKey) {
            if ($indexed->has($uriKey)) {
                $sorted->push($indexed->get($uriKey));
                $indexed->forget($uriKey);
            }
        }

        // Add any remaining dashboards
        $sorted = $sorted->merge($indexed->values());

        return $sorted;
    }

    /**
     * Check if dashboard is visible to user.
     */
    protected static function isDashboardVisible(Dashboard $dashboard, Request $request): bool
    {
        // Check dashboard metadata visibility
        $metadata = DashboardMetadataManager::getMetadata($dashboard);
        if (!($metadata['visible'] ?? true) || !($metadata['enabled'] ?? true)) {
            return false;
        }

        // Check dashboard authorization
        if (!$dashboard->authorizedToSee($request)) {
            return false;
        }

        // Check user-specific visibility setting
        $userVisibility = static::getDashboardVisibility($dashboard->uriKey(), $request);
        if ($userVisibility !== null) {
            return $userVisibility;
        }

        // Check dependencies
        if (method_exists($dashboard, 'checkDependencies') && !$dashboard->checkDependencies()) {
            return false;
        }

        // Check permissions
        if (method_exists($dashboard, 'checkPermissions') && !$dashboard->checkPermissions($request)) {
            return false;
        }

        return true;
    }

    /**
     * Get dashboard usage count for user.
     */
    protected static function getDashboardUsageCount(string $dashboardUriKey, Request $request): int
    {
        $user = $request->user();
        if (!$user) {
            return 0;
        }

        $preferences = $user->preferences ?? [];
        return $preferences['dashboard_usage'][$dashboardUriKey] ?? 0;
    }

    /**
     * Get dashboard last accessed date for user.
     */
    protected static function getDashboardLastAccessed(string $dashboardUriKey, Request $request): ?string
    {
        $user = $request->user();
        if (!$user) {
            return null;
        }

        $preferences = $user->preferences ?? [];
        return $preferences['dashboard_last_accessed'][$dashboardUriKey] ?? null;
    }

    /**
     * Get ordering cache key.
     */
    protected static function getOrderingCacheKey(string $sortBy, string $direction, $userId = null): string
    {
        $key = static::CACHE_PREFIX . ':ordering:' . $sortBy . ':' . $direction;
        if ($userId) {
            $key .= ':user:' . $userId;
        }
        return $key;
    }

    /**
     * Clear ordering cache.
     */
    public static function clearOrderingCache($userId = null): void
    {
        $pattern = static::CACHE_PREFIX . ':ordering:*';
        if ($userId) {
            $pattern .= ':user:' . $userId;
        }

        $keys = Cache::getRedis()->keys($pattern);
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }

    /**
     * Clear visibility cache.
     */
    public static function clearVisibilityCache($userId = null): void
    {
        $pattern = static::CACHE_PREFIX . ':visibility:*';
        if ($userId) {
            $pattern .= ':user:' . $userId;
        }

        $keys = Cache::getRedis()->keys($pattern);
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }

    /**
     * Get available sort options.
     */
    public static function getSortOptions(): array
    {
        return static::SORT_OPTIONS;
    }

    /**
     * Get available sort directions.
     */
    public static function getSortDirections(): array
    {
        return static::SORT_DIRECTIONS;
    }
}
