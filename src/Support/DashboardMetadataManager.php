<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use JTD\AdminPanel\Dashboards\Dashboard;

/**
 * Dashboard Metadata Manager
 * 
 * Manages dashboard metadata including icons, descriptions, categories,
 * ordering, visibility controls, and configuration validation.
 */
class DashboardMetadataManager
{
    /**
     * Cache key prefix for dashboard metadata.
     */
    protected const CACHE_PREFIX = 'admin_panel_dashboard_metadata';

    /**
     * Cache TTL for dashboard metadata (in seconds).
     */
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Valid dashboard metadata fields.
     */
    protected const VALID_METADATA_FIELDS = [
        'name',
        'description',
        'icon',
        'category',
        'tags',
        'priority',
        'visible',
        'enabled',
        'color',
        'background_color',
        'text_color',
        'author',
        'version',
        'created_at',
        'updated_at',
        'permissions',
        'dependencies',
        'configuration',
        'display_options',
    ];

    /**
     * Valid icon types.
     */
    protected const VALID_ICON_TYPES = [
        'heroicon',
        'fontawesome',
        'custom',
        'emoji',
        'svg',
        'image',
    ];

    /**
     * Valid category types.
     */
    protected const VALID_CATEGORIES = [
        'Overview',
        'Analytics',
        'Reports',
        'Business',
        'Financial',
        'Users',
        'Content',
        'System',
        'Monitoring',
        'Security',
        'Marketing',
        'Sales',
        'Support',
        'Admin',
        'General',
    ];

    /**
     * Get metadata for a dashboard.
     */
    public static function getMetadata(Dashboard $dashboard, bool $useCache = true): array
    {
        $cacheKey = static::getCacheKey($dashboard->uriKey());

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $metadata = static::extractMetadata($dashboard);
        $metadata = static::validateAndNormalizeMetadata($metadata);

        if ($useCache) {
            Cache::put($cacheKey, $metadata, static::CACHE_TTL);
        }

        return $metadata;
    }

    /**
     * Get metadata for multiple dashboards.
     */
    public static function getMultipleMetadata(Collection $dashboards, bool $useCache = true): Collection
    {
        return $dashboards->map(function ($dashboard) use ($useCache) {
            return [
                'dashboard' => $dashboard,
                'metadata' => static::getMetadata($dashboard, $useCache),
            ];
        });
    }

    /**
     * Set metadata for a dashboard.
     */
    public static function setMetadata(Dashboard $dashboard, array $metadata): void
    {
        $metadata = static::validateAndNormalizeMetadata($metadata);
        $cacheKey = static::getCacheKey($dashboard->uriKey());

        Cache::put($cacheKey, $metadata, static::CACHE_TTL);

        // Store in dashboard instance if possible
        if (method_exists($dashboard, 'setMetadata')) {
            $dashboard->setMetadata($metadata);
        }
    }

    /**
     * Clear metadata cache for a dashboard.
     */
    public static function clearCache(string $uriKey = null): void
    {
        if ($uriKey) {
            Cache::forget(static::getCacheKey($uriKey));
        } else {
            // Clear all dashboard metadata cache
            $pattern = static::CACHE_PREFIX . '*';
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }

    /**
     * Extract metadata from a dashboard instance.
     */
    protected static function extractMetadata(Dashboard $dashboard): array
    {
        $metadata = [
            'name' => $dashboard->name(),
            'description' => method_exists($dashboard, 'description') ? $dashboard->description() : null,
            'icon' => method_exists($dashboard, 'icon') ? $dashboard->icon() : null,
            'category' => method_exists($dashboard, 'category') ? $dashboard->category() : 'General',
            'uri_key' => $dashboard->uriKey(),
            'class' => get_class($dashboard),
        ];

        // Extract additional metadata if methods exist
        $additionalMethods = [
            'tags' => 'getTags',
            'priority' => 'getPriority',
            'visible' => 'isVisible',
            'enabled' => 'isEnabled',
            'color' => 'getColor',
            'background_color' => 'getBackgroundColor',
            'text_color' => 'getTextColor',
            'author' => 'getAuthor',
            'version' => 'getVersion',
            'permissions' => 'getPermissions',
            'dependencies' => 'getDependencies',
            'configuration' => 'getConfiguration',
            'display_options' => 'getDisplayOptions',
        ];

        foreach ($additionalMethods as $key => $method) {
            if (method_exists($dashboard, $method)) {
                $metadata[$key] = $dashboard->$method();
            }
        }

        // Add timestamps
        $metadata['created_at'] = now()->toISOString();
        $metadata['updated_at'] = now()->toISOString();

        return $metadata;
    }

    /**
     * Validate and normalize metadata.
     */
    protected static function validateAndNormalizeMetadata(array $metadata): array
    {
        // Validate metadata structure
        $validator = Validator::make($metadata, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:255',
            'category' => 'nullable|string|in:' . implode(',', static::VALID_CATEGORIES),
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'priority' => 'nullable|integer|min:0|max:1000',
            'visible' => 'nullable|boolean',
            'enabled' => 'nullable|boolean',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'text_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'author' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
            'permissions' => 'nullable|array',
            'dependencies' => 'nullable|array',
            'configuration' => 'nullable|array',
            'display_options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid dashboard metadata: ' . $validator->errors()->first());
        }

        // Normalize metadata
        $normalized = $validator->validated();

        // Set defaults
        $normalized['category'] = $normalized['category'] ?? 'General';
        $normalized['priority'] = $normalized['priority'] ?? 100;
        $normalized['visible'] = $normalized['visible'] ?? true;
        $normalized['enabled'] = $normalized['enabled'] ?? true;
        $normalized['tags'] = $normalized['tags'] ?? [];
        $normalized['permissions'] = $normalized['permissions'] ?? [];
        $normalized['dependencies'] = $normalized['dependencies'] ?? [];
        $normalized['configuration'] = $normalized['configuration'] ?? [];
        $normalized['display_options'] = $normalized['display_options'] ?? [];

        // Normalize icon
        if (!empty($normalized['icon'])) {
            $normalized['icon'] = static::normalizeIcon($normalized['icon']);
        }

        return $normalized;
    }

    /**
     * Normalize icon format.
     */
    protected static function normalizeIcon(string $icon): array
    {
        // If already an array format, return as is
        if (is_array($icon)) {
            return $icon;
        }

        // Detect icon type and normalize
        if (str_starts_with($icon, 'heroicon:')) {
            return [
                'type' => 'heroicon',
                'name' => str_replace('heroicon:', '', $icon),
            ];
        }

        if (str_starts_with($icon, 'fa:') || str_starts_with($icon, 'fas:') || str_starts_with($icon, 'far:')) {
            return [
                'type' => 'fontawesome',
                'name' => $icon,
            ];
        }

        if (str_starts_with($icon, 'data:image/') || str_starts_with($icon, 'http')) {
            return [
                'type' => 'image',
                'url' => $icon,
            ];
        }

        if (str_starts_with($icon, '<svg')) {
            return [
                'type' => 'svg',
                'content' => $icon,
            ];
        }

        if (preg_match('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]/u', $icon)) {
            return [
                'type' => 'emoji',
                'emoji' => $icon,
            ];
        }

        // Default to heroicon
        return [
            'type' => 'heroicon',
            'name' => $icon,
        ];
    }

    /**
     * Get dashboard ordering based on metadata.
     */
    public static function getOrderedDashboards(Collection $dashboards, string $orderBy = 'priority', string $direction = 'asc'): Collection
    {
        $dashboardsWithMetadata = static::getMultipleMetadata($dashboards);

        return $dashboardsWithMetadata->sortBy(function ($item) use ($orderBy) {
            return $item['metadata'][$orderBy] ?? 999;
        }, SORT_REGULAR, $direction === 'desc')
        ->map(fn($item) => $item['dashboard'])
        ->values();
    }

    /**
     * Filter dashboards by visibility and permissions.
     */
    public static function getVisibleDashboards(Collection $dashboards, Request $request = null): Collection
    {
        $request = $request ?? request();

        return $dashboards->filter(function ($dashboard) use ($request) {
            $metadata = static::getMetadata($dashboard);

            // Check if dashboard is enabled
            if (!($metadata['enabled'] ?? true)) {
                return false;
            }

            // Check if dashboard is visible
            if (!($metadata['visible'] ?? true)) {
                return false;
            }

            // Check dashboard authorization
            if (!$dashboard->authorizedToSee($request)) {
                return false;
            }

            // Check permissions if defined
            if (!empty($metadata['permissions'])) {
                $user = $request->user();
                if (!$user) {
                    return false;
                }

                foreach ($metadata['permissions'] as $permission) {
                    if (!$user->can($permission)) {
                        return false;
                    }
                }
            }

            return true;
        });
    }

    /**
     * Group dashboards by category.
     */
    public static function groupDashboardsByCategory(Collection $dashboards): Collection
    {
        $dashboardsWithMetadata = static::getMultipleMetadata($dashboards);

        return $dashboardsWithMetadata->groupBy(function ($item) {
            return $item['metadata']['category'] ?? 'General';
        })->map(function ($group) {
            return $group->map(fn($item) => $item['dashboard']);
        });
    }

    /**
     * Search dashboards by metadata.
     */
    public static function searchDashboards(Collection $dashboards, string $query): Collection
    {
        $query = strtolower(trim($query));
        if (empty($query)) {
            return $dashboards;
        }

        return $dashboards->filter(function ($dashboard) use ($query) {
            $metadata = static::getMetadata($dashboard);

            // Search in name
            if (str_contains(strtolower($metadata['name'] ?? ''), $query)) {
                return true;
            }

            // Search in description
            if (str_contains(strtolower($metadata['description'] ?? ''), $query)) {
                return true;
            }

            // Search in category
            if (str_contains(strtolower($metadata['category'] ?? ''), $query)) {
                return true;
            }

            // Search in tags
            foreach ($metadata['tags'] ?? [] as $tag) {
                if (str_contains(strtolower($tag), $query)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Get cache key for dashboard metadata.
     */
    protected static function getCacheKey(string $uriKey): string
    {
        return static::CACHE_PREFIX . ':' . $uriKey;
    }

    /**
     * Get valid metadata fields.
     */
    public static function getValidMetadataFields(): array
    {
        return static::VALID_METADATA_FIELDS;
    }

    /**
     * Get valid categories.
     */
    public static function getValidCategories(): array
    {
        return static::VALID_CATEGORIES;
    }

    /**
     * Get valid icon types.
     */
    public static function getValidIconTypes(): array
    {
        return static::VALID_ICON_TYPES;
    }
}
