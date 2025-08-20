<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use JTD\AdminPanel\Dashboards\Dashboard;

/**
 * Dashboard Configuration Manager
 * 
 * Manages dashboard configuration options, display preferences,
 * user-specific settings, and global dashboard configuration.
 */
class DashboardConfigurationManager
{
    /**
     * Cache key prefix for dashboard configuration.
     */
    protected const CACHE_PREFIX = 'admin_panel_dashboard_config';

    /**
     * Cache TTL for dashboard configuration (in seconds).
     */
    protected const CACHE_TTL = 1800; // 30 minutes

    /**
     * Default configuration schema.
     */
    protected const DEFAULT_CONFIG_SCHEMA = [
        'display' => [
            'layout' => 'grid', // grid, list, cards
            'columns' => 3,
            'card_size' => 'medium', // small, medium, large
            'show_descriptions' => true,
            'show_icons' => true,
            'show_categories' => true,
            'compact_mode' => false,
        ],
        'behavior' => [
            'auto_refresh' => false,
            'refresh_interval' => 300, // seconds
            'lazy_loading' => true,
            'preload_data' => false,
            'cache_data' => true,
            'cache_ttl' => 600, // seconds
        ],
        'navigation' => [
            'show_breadcrumbs' => true,
            'show_back_button' => true,
            'enable_keyboard_shortcuts' => true,
            'enable_quick_switcher' => true,
            'remember_last_dashboard' => true,
        ],
        'accessibility' => [
            'high_contrast' => false,
            'large_text' => false,
            'reduce_motion' => false,
            'screen_reader_optimized' => false,
        ],
        'advanced' => [
            'debug_mode' => false,
            'performance_monitoring' => false,
            'error_reporting' => true,
            'analytics_tracking' => true,
        ],
    ];

    /**
     * Get configuration for a dashboard.
     */
    public static function getConfiguration(Dashboard $dashboard, Request $request = null): array
    {
        $request = $request ?? request();
        $cacheKey = static::getConfigCacheKey($dashboard->uriKey(), $request->user()?->id);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $config = static::buildConfiguration($dashboard, $request);
        Cache::put($cacheKey, $config, static::CACHE_TTL);

        return $config;
    }

    /**
     * Set configuration for a dashboard.
     */
    public static function setConfiguration(Dashboard $dashboard, array $config, Request $request = null): void
    {
        $request = $request ?? request();
        $config = static::validateConfiguration($config);
        
        $cacheKey = static::getConfigCacheKey($dashboard->uriKey(), $request->user()?->id);
        Cache::put($cacheKey, $config, static::CACHE_TTL);

        // Store user-specific configuration if user is authenticated
        if ($user = $request->user()) {
            static::storeUserConfiguration($user, $dashboard->uriKey(), $config);
        }
    }

    /**
     * Get global dashboard configuration.
     */
    public static function getGlobalConfiguration(): array
    {
        $cacheKey = static::CACHE_PREFIX . ':global';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $config = static::buildGlobalConfiguration();
        Cache::put($cacheKey, $config, static::CACHE_TTL);

        return $config;
    }

    /**
     * Set global dashboard configuration.
     */
    public static function setGlobalConfiguration(array $config): void
    {
        $config = static::validateConfiguration($config);
        
        $cacheKey = static::CACHE_PREFIX . ':global';
        Cache::put($cacheKey, $config, static::CACHE_TTL);

        // Store in config file or database
        static::storeGlobalConfiguration($config);
    }

    /**
     * Get user-specific dashboard preferences.
     */
    public static function getUserPreferences($user, string $dashboardUriKey = null): array
    {
        if (!$user) {
            return [];
        }

        $cacheKey = static::CACHE_PREFIX . ':user:' . $user->id;
        if ($dashboardUriKey) {
            $cacheKey .= ':' . $dashboardUriKey;
        }

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $preferences = static::loadUserPreferences($user, $dashboardUriKey);
        Cache::put($cacheKey, $preferences, static::CACHE_TTL);

        return $preferences;
    }

    /**
     * Set user-specific dashboard preferences.
     */
    public static function setUserPreferences($user, array $preferences, string $dashboardUriKey = null): void
    {
        if (!$user) {
            return;
        }

        $preferences = static::validateConfiguration($preferences);
        
        $cacheKey = static::CACHE_PREFIX . ':user:' . $user->id;
        if ($dashboardUriKey) {
            $cacheKey .= ':' . $dashboardUriKey;
        }

        Cache::put($cacheKey, $preferences, static::CACHE_TTL);
        static::storeUserPreferences($user, $preferences, $dashboardUriKey);
    }

    /**
     * Build configuration for a dashboard.
     */
    protected static function buildConfiguration(Dashboard $dashboard, Request $request): array
    {
        // Start with default configuration
        $config = static::DEFAULT_CONFIG_SCHEMA;

        // Merge with global configuration
        $globalConfig = static::getGlobalConfiguration();
        $config = array_merge_recursive($config, $globalConfig);

        // Merge with dashboard-specific configuration
        if (method_exists($dashboard, 'getConfiguration')) {
            $dashboardConfig = $dashboard->getConfiguration();
            $config = array_merge_recursive($config, $dashboardConfig);
        }

        // Merge with user preferences
        if ($user = $request->user()) {
            $userPreferences = static::getUserPreferences($user, $dashboard->uriKey());
            $config = array_merge_recursive($config, $userPreferences);
        }

        // Apply runtime configuration
        $config = static::applyRuntimeConfiguration($config, $dashboard, $request);

        return $config;
    }

    /**
     * Build global dashboard configuration.
     */
    protected static function buildGlobalConfiguration(): array
    {
        $config = static::DEFAULT_CONFIG_SCHEMA;

        // Load from config file
        $fileConfig = config('admin-panel.dashboard.configuration', []);
        $config = array_merge_recursive($config, $fileConfig);

        // Load from database if available
        $dbConfig = static::loadGlobalConfigurationFromDatabase();
        if (!empty($dbConfig)) {
            $config = array_merge_recursive($config, $dbConfig);
        }

        return $config;
    }

    /**
     * Apply runtime configuration based on request context.
     */
    protected static function applyRuntimeConfiguration(array $config, Dashboard $dashboard, Request $request): array
    {
        // Apply mobile-specific configuration
        if (static::isMobileRequest($request)) {
            $config['display']['layout'] = 'list';
            $config['display']['columns'] = 1;
            $config['display']['compact_mode'] = true;
            $config['behavior']['lazy_loading'] = true;
        }

        // Apply accessibility preferences
        if (static::hasAccessibilityNeeds($request)) {
            $config['accessibility']['high_contrast'] = true;
            $config['accessibility']['large_text'] = true;
            $config['accessibility']['reduce_motion'] = true;
        }

        // Apply performance optimizations for slow connections
        if (static::isSlowConnection($request)) {
            $config['behavior']['lazy_loading'] = true;
            $config['behavior']['preload_data'] = false;
            $config['display']['show_icons'] = false;
        }

        return $config;
    }

    /**
     * Validate configuration structure.
     */
    protected static function validateConfiguration(array $config): array
    {
        $validator = Validator::make($config, [
            'display.layout' => 'nullable|string|in:grid,list,cards',
            'display.columns' => 'nullable|integer|min:1|max:6',
            'display.card_size' => 'nullable|string|in:small,medium,large',
            'display.show_descriptions' => 'nullable|boolean',
            'display.show_icons' => 'nullable|boolean',
            'display.show_categories' => 'nullable|boolean',
            'display.compact_mode' => 'nullable|boolean',
            
            'behavior.auto_refresh' => 'nullable|boolean',
            'behavior.refresh_interval' => 'nullable|integer|min:30|max:3600',
            'behavior.lazy_loading' => 'nullable|boolean',
            'behavior.preload_data' => 'nullable|boolean',
            'behavior.cache_data' => 'nullable|boolean',
            'behavior.cache_ttl' => 'nullable|integer|min:60|max:7200',
            
            'navigation.show_breadcrumbs' => 'nullable|boolean',
            'navigation.show_back_button' => 'nullable|boolean',
            'navigation.enable_keyboard_shortcuts' => 'nullable|boolean',
            'navigation.enable_quick_switcher' => 'nullable|boolean',
            'navigation.remember_last_dashboard' => 'nullable|boolean',
            
            'accessibility.high_contrast' => 'nullable|boolean',
            'accessibility.large_text' => 'nullable|boolean',
            'accessibility.reduce_motion' => 'nullable|boolean',
            'accessibility.screen_reader_optimized' => 'nullable|boolean',
            
            'advanced.debug_mode' => 'nullable|boolean',
            'advanced.performance_monitoring' => 'nullable|boolean',
            'advanced.error_reporting' => 'nullable|boolean',
            'advanced.analytics_tracking' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException('Invalid dashboard configuration: ' . $validator->errors()->first());
        }

        return $validator->validated();
    }

    /**
     * Store user configuration.
     */
    protected static function storeUserConfiguration($user, string $dashboardUriKey, array $config): void
    {
        $preferences = $user->preferences ?? [];
        $preferences['dashboard_config'][$dashboardUriKey] = $config;
        
        $user->preferences = $preferences;
        $user->save();
    }

    /**
     * Store user preferences.
     */
    protected static function storeUserPreferences($user, array $preferences, string $dashboardUriKey = null): void
    {
        $userPreferences = $user->preferences ?? [];
        
        if ($dashboardUriKey) {
            $userPreferences['dashboard_preferences'][$dashboardUriKey] = $preferences;
        } else {
            $userPreferences['dashboard_preferences']['global'] = $preferences;
        }
        
        $user->preferences = $userPreferences;
        $user->save();
    }

    /**
     * Load user preferences.
     */
    protected static function loadUserPreferences($user, string $dashboardUriKey = null): array
    {
        $preferences = $user->preferences ?? [];
        
        if ($dashboardUriKey) {
            return $preferences['dashboard_preferences'][$dashboardUriKey] ?? [];
        }
        
        return $preferences['dashboard_preferences']['global'] ?? [];
    }

    /**
     * Store global configuration.
     */
    protected static function storeGlobalConfiguration(array $config): void
    {
        // This would typically store in database or config file
        // For now, we'll just cache it
    }

    /**
     * Load global configuration from database.
     */
    protected static function loadGlobalConfigurationFromDatabase(): array
    {
        // This would typically load from database
        // For now, return empty array
        return [];
    }

    /**
     * Check if request is from mobile device.
     */
    protected static function isMobileRequest(Request $request): bool
    {
        $userAgent = $request->userAgent();
        return preg_match('/Mobile|Android|iPhone|iPad/', $userAgent);
    }

    /**
     * Check if user has accessibility needs.
     */
    protected static function hasAccessibilityNeeds(Request $request): bool
    {
        // Check for accessibility preferences in request or user settings
        return $request->has('accessibility') || 
               $request->user()?->preferences['accessibility']['enabled'] ?? false;
    }

    /**
     * Check if connection is slow.
     */
    protected static function isSlowConnection(Request $request): bool
    {
        // This could check connection speed indicators
        // For now, return false
        return false;
    }

    /**
     * Get configuration cache key.
     */
    protected static function getConfigCacheKey(string $dashboardUriKey, $userId = null): string
    {
        $key = static::CACHE_PREFIX . ':' . $dashboardUriKey;
        if ($userId) {
            $key .= ':user:' . $userId;
        }
        return $key;
    }

    /**
     * Clear configuration cache.
     */
    public static function clearCache(string $dashboardUriKey = null, $userId = null): void
    {
        if ($dashboardUriKey) {
            $key = static::getConfigCacheKey($dashboardUriKey, $userId);
            Cache::forget($key);
        } else {
            // Clear all configuration cache
            $pattern = static::CACHE_PREFIX . '*';
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                Cache::getRedis()->del($keys);
            }
        }
    }

    /**
     * Get default configuration schema.
     */
    public static function getDefaultConfigurationSchema(): array
    {
        return static::DEFAULT_CONFIG_SCHEMA;
    }
}
