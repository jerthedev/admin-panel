<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your admin panel. This value is used when the
    | framework needs to place the admin panel's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('ADMIN_PANEL_NAME', 'Admin Panel'),

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When debug mode is enabled, the admin panel will display additional
    | debugging information and detailed error messages. This should be
    | disabled in production environments.
    |
    */

    'debug' => env('ADMIN_PANEL_DEBUG', env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where the admin panel will be accessible from.
    | Feel free to change this path to anything you like. Note that the URI
    | will not affect the paths of its internal API that aren't exposed.
    |
    */

    'path' => env('ADMIN_PANEL_PATH', '/admin'),

    /*
    |--------------------------------------------------------------------------
    | Admin Panel Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every admin panel route, giving you
    | the chance to add your own middleware to this list or change any of the
    | existing middleware. Note: auth middleware is applied per route group,
    | not globally, to avoid conflicts with login routes.
    |
    */

    'middleware' => [
        'web',
        // Note: admin.auth and admin.authorize are applied per route group
        // in routes/web.php to avoid applying auth to login routes
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the authentication system for the admin panel.
    | You can specify the guard to use, login route, and authorization callback.
    |
    */

    'auth' => [
        'guard' => env('ADMIN_PANEL_GUARD', 'web'),
        'login_route' => '/login',
        'allow_all_authenticated' => env('ADMIN_PANEL_ALLOW_ALL', true),
        'user_model' => \App\Models\User::class,
        'admin_user_model' => null, // Specific admin user model if different
        'password_reset' => true,
        'authorize' => null, // Custom authorization callback: function($user, $request) { return $user->isAdmin(); }
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy Configuration
    |--------------------------------------------------------------------------
    |
    | Register policies for admin panel resources. These policies will be
    | automatically registered when the service provider boots.
    |
    */

    'policies' => [
        // \App\Models\User::class => \App\Policies\UserPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the admin panel theme and appearance. You can specify the
    | default theme, enable dark mode, and customize various UI elements.
    |
    */

    'theme' => [
        'default' => env('ADMIN_PANEL_THEME', 'default'),
        'dark_mode' => env('ADMIN_PANEL_DARK_MODE', false),
        'user_theme_preference' => true,
        'primary_color' => '#3b82f6', // Blue-500
        'sidebar_width' => '16rem',
        'compact_mode' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Library Integration
    |--------------------------------------------------------------------------
    |
    | Configure Spatie Media Library integration for file and image handling.
    | This includes default disk, conversions, and file validation rules.
    |
    */

    'media' => [
        'disk' => env('ADMIN_PANEL_MEDIA_DISK', 'public'),
        'path_generator' => null, // Custom path generator class
        'url_generator' => null, // Custom URL generator class

        'default_conversions' => [
            'thumb' => [
                'width' => 150,
                'height' => 150,
                'crop' => true,
            ],
            'medium' => [
                'width' => 400,
                'height' => 400,
                'crop' => false,
            ],
            'large' => [
                'width' => 800,
                'height' => 800,
                'crop' => false,
            ],
        ],

        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Media Library Field Configuration
    |--------------------------------------------------------------------------
    |
    | Configure specific settings for Media Library fields including
    | avatar-specific conversions, file size limits, and accepted types.
    |
    */

    'media_library' => [
        'default_disk' => env('ADMIN_PANEL_MEDIA_LIBRARY_DISK', 'public'),

        'accepted_mime_types' => [
            'avatar' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/webp',
            ],
            'image' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'image/svg+xml',
            ],
            'file' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
                'text/csv',
            ],
        ],

        'file_size_limits' => [
            'avatar' => 2048, // 2MB in KB
            'image' => 5120, // 5MB in KB
            'file' => 10240, // 10MB in KB
        ],

        'avatar_conversions' => [
            'thumb' => ['width' => 64, 'height' => 64, 'fit' => 'crop'],
            'medium' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'large' => ['width' => 400, 'height' => 400, 'fit' => 'crop'],
        ],

        'image_conversions' => [
            'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
            'medium' => ['width' => 400, 'height' => 400, 'fit' => 'contain'],
            'large' => ['width' => 800, 'height' => 800, 'fit' => 'contain'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the admin panel dashboard including metrics refresh interval,
    | default widgets, and layout preferences.
    |
    */

    'dashboard' => [
        'refresh_interval' => 30, // seconds
        'show_welcome_card' => true,

        /*
        |--------------------------------------------------------------------------
        | Default Dashboard
        |--------------------------------------------------------------------------
        |
        | The default dashboard class that will be used when no specific dashboard
        | is requested. This should extend JTD\AdminPanel\Dashboards\Dashboard.
        |
        */
        'default' => \JTD\AdminPanel\Dashboards\Main::class,

        /*
        |--------------------------------------------------------------------------
        | Registered Dashboards
        |--------------------------------------------------------------------------
        |
        | List of dashboard classes that should be available in the admin panel.
        | These will be automatically registered and available for navigation.
        |
        | You can register dashboards in two ways:
        | 1. Class names (legacy): \App\Dashboards\MyDashboard::class
        | 2. Dashboard instances (Nova v5): MyDashboard::make()->showRefreshButton()
        |
        | Note: For dashboard instances, you should register them in your
        | AppServiceProvider's dashboards() method instead of here.
        |
        */
        'dashboards' => [
            \JTD\AdminPanel\Dashboards\Main::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Dashboard Navigation
        |--------------------------------------------------------------------------
        |
        | Configure how dashboards appear in the navigation menu.
        |
        */
        'dashboard_navigation' => [
            // Whether to show dashboards in the main navigation
            'show_in_navigation' => true,

            // Whether to group multiple dashboards under a "Dashboards" section
            'group_multiple_dashboards' => true,

            // Icon for the dashboard navigation section
            'section_icon' => 'chart-bar',

            // Whether to show the main dashboard separately in navigation
            'show_main_dashboard_separately' => true,

            // Icon for the main dashboard menu item
            'main_dashboard_icon' => 'home',
        ],

        /*
        |--------------------------------------------------------------------------
        | Advanced Dashboard Navigation
        |--------------------------------------------------------------------------
        |
        | Configure advanced navigation features like breadcrumbs, quick switcher,
        | keyboard shortcuts, and navigation state persistence.
        |
        */
        'navigation' => [
            // Whether to show breadcrumb navigation
            'show_breadcrumbs' => true,

            // Whether to show the quick dashboard switcher
            'show_quick_switcher' => true,

            // Whether to enable keyboard shortcuts for navigation
            'enable_keyboard_shortcuts' => true,

            // Maximum number of items to keep in navigation history
            'max_history_items' => 10,

            // Maximum number of recent dashboards to show
            'max_recent_items' => 5,

            // Whether to persist navigation state in localStorage
            'persist_state' => true,

            // Whether to show navigation controls (back/forward buttons)
            'show_navigation_controls' => true,

            // Whether to show quick actions in breadcrumbs
            'show_quick_actions' => true,

            // Whether to show keyboard shortcut hints
            'show_keyboard_hints' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Dashboard Transitions
        |--------------------------------------------------------------------------
        |
        | Configure smooth transitions between dashboards including animations,
        | loading states, error handling, and gesture navigation.
        |
        */
        'transitions' => [
            // Default transition animation
            'default_animation' => 'fade',

            // Transition duration in milliseconds
            'transition_duration' => 300,

            // Whether to show loading overlay during transitions
            'show_transition_loading' => true,

            // Loading variant (spinner, skeleton, pulse, dots, fade)
            'loading_variant' => 'spinner',

            // Whether to show transition progress bar
            'show_transition_progress' => true,

            // Whether to allow canceling transitions
            'allow_cancel_transition' => true,

            // Whether to show transition error messages
            'show_transition_errors' => true,

            // Theme for transition overlays (light, dark)
            'theme' => 'light',

            // Whether to enable gesture navigation (swipe to go back/forward)
            'enable_gesture_navigation' => false,

            // Gesture threshold in pixels
            'gesture_threshold' => 50,

            // Whether to preserve scroll position during transitions
            'preserve_scroll' => true,

            // Whether to preserve form data during transitions
            'preserve_data' => true,

            // Transition timeout in milliseconds
            'timeout' => 10000,

            // Number of retry attempts for failed transitions
            'retry_attempts' => 3,

            // Whether to preload dashboard data for faster transitions
            'preload_data' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Dashboard Menu Integration
        |--------------------------------------------------------------------------
        |
        | Configure how dashboards are integrated into the admin panel menu
        | system including grouping, quick access, and favorites.
        |
        */
        'menu' => [
            // Whether to show quick access section in menu
            'show_quick_access' => true,

            // Number of recent dashboards to show in quick access
            'quick_access_limit' => 3,

            // Whether to show favorites section in menu
            'show_favorites' => true,

            // Whether to group dashboards by category
            'group_by_category' => true,

            // Whether to enable dashboard badges in menu
            'enable_badges' => true,

            // Whether to cache dashboard badges
            'cache_badges' => true,

            // Badge cache TTL in seconds
            'badge_cache_ttl' => 300,

            // Whether to show dashboard descriptions in menu tooltips
            'show_descriptions' => true,

            // Whether to enable dashboard menu search
            'enable_search' => true,

            // Default dashboard menu sections to show
            'default_sections' => [
                'quick_access' => true,
                'favorites' => true,
                'categories' => true,
                'all_dashboards' => false,
            ],

            // Menu section icons by category
            'category_icons' => [
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
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Dashboard Metadata & Configuration
        |--------------------------------------------------------------------------
        |
        | Configure dashboard metadata management, display preferences,
        | ordering, and visibility controls.
        |
        */
        'metadata' => [
            // Whether to enable metadata caching
            'enable_caching' => true,

            // Metadata cache TTL in seconds
            'cache_ttl' => 3600,

            // Valid dashboard categories
            'valid_categories' => [
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
            ],

            // Valid icon types
            'valid_icon_types' => [
                'heroicon',
                'fontawesome',
                'custom',
                'emoji',
                'svg',
                'image',
            ],

            // Default metadata values
            'defaults' => [
                'category' => 'General',
                'priority' => 100,
                'visible' => true,
                'enabled' => true,
                'tags' => [],
                'permissions' => [],
                'dependencies' => [],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Dashboard Configuration
        |--------------------------------------------------------------------------
        |
        | Default configuration options for dashboard display and behavior.
        | These can be overridden per dashboard or per user.
        |
        */
        'configuration' => [
            // Display configuration
            'display' => [
                'layout' => 'grid', // grid, list, cards
                'columns' => 3,
                'card_size' => 'medium', // small, medium, large
                'show_descriptions' => true,
                'show_icons' => true,
                'show_categories' => true,
                'compact_mode' => false,
            ],

            // Behavior configuration
            'behavior' => [
                'auto_refresh' => false,
                'refresh_interval' => 300, // seconds
                'lazy_loading' => true,
                'preload_data' => false,
                'cache_data' => true,
                'cache_ttl' => 600, // seconds
            ],

            // Navigation configuration
            'navigation' => [
                'show_breadcrumbs' => true,
                'show_back_button' => true,
                'enable_keyboard_shortcuts' => true,
                'enable_quick_switcher' => true,
                'remember_last_dashboard' => true,
            ],

            // Accessibility configuration
            'accessibility' => [
                'high_contrast' => false,
                'large_text' => false,
                'reduce_motion' => false,
                'screen_reader_optimized' => false,
            ],

            // Advanced configuration
            'advanced' => [
                'debug_mode' => false,
                'performance_monitoring' => false,
                'error_reporting' => true,
                'analytics_tracking' => true,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Dashboard Ordering & Visibility
        |--------------------------------------------------------------------------
        |
        | Configure dashboard ordering and visibility controls including
        | sorting options and user preferences.
        |
        */
        'ordering' => [
            // Default sort criteria
            'default_sort_by' => 'priority',
            'default_sort_direction' => 'asc',

            // Available sort options
            'sort_options' => [
                'priority' => 'Priority',
                'name' => 'Name',
                'category' => 'Category',
                'created_at' => 'Created Date',
                'updated_at' => 'Updated Date',
                'usage_count' => 'Usage Count',
                'last_accessed' => 'Last Accessed',
                'custom' => 'Custom Order',
            ],

            // Whether to enable user-specific ordering
            'enable_user_ordering' => true,

            // Whether to enable user-specific visibility
            'enable_user_visibility' => true,

            // Whether to track dashboard usage
            'track_usage' => true,

            // Ordering cache TTL in seconds
            'cache_ttl' => 1800,
        ],

        /*
        |--------------------------------------------------------------------------
        | Dashboard Authorization
        |--------------------------------------------------------------------------
        |
        | Configure dashboard authorization settings.
        |
        */
        'dashboard_authorization' => [
            // Enable authorization caching for better performance
            'enable_caching' => true,

            // Cache TTL in seconds (5 minutes default)
            'cache_ttl' => 300,

            // Cache key prefix
            'cache_key_prefix' => 'dashboard_auth',
        ],
        'default_metrics' => [
            \JTD\AdminPanel\Metrics\UserCountMetric::class,
            \JTD\AdminPanel\Metrics\ActiveUsersMetric::class,
            \JTD\AdminPanel\Metrics\ResourceCountMetric::class,
            \JTD\AdminPanel\Metrics\SystemHealthMetric::class,
        ],
        'metrics_cache_ttl' => 300, // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default behavior for resources including pagination,
    | search settings, and global resource options.
    |
    */

    'resources' => [
        'per_page' => 25,
        'max_per_page' => 100,
        'search_debounce' => 300, // milliseconds
        'auto_discovery' => true,
        'discovery_path' => 'app/Admin/Resources',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field Configuration
    |--------------------------------------------------------------------------
    |
    | Configure default behavior for fields including validation,
    | formatting, and display options.
    |
    */

    'fields' => [
        'date_format' => 'Y-m-d',
        'datetime_format' => 'Y-m-d H:i:s',
        'timezone' => null, // Uses app timezone if null
        'currency' => [
            'symbol' => '$',
            'position' => 'before', // 'before' or 'after'
            'decimals' => 2,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching and performance optimization settings for the
    | admin panel to ensure fast loading times and efficient queries.
    |
    */

    'performance' => [
        'cache_resources' => true,
        'cache_ttl' => 3600, // 1 hour
        'eager_load_relations' => true,
        'query_limit' => 1000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configure security settings including CSRF protection, XSS prevention,
    | and other security measures for the admin panel.
    |
    */

    'security' => [
        'csrf_protection' => true,
        'xss_protection' => true,
        'content_security_policy' => true,
        'rate_limiting' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization Configuration
    |--------------------------------------------------------------------------
    |
    | Configure localization settings for the admin panel including
    | supported locales and translation preferences.
    |
    */

    'localization' => [
        'default_locale' => 'en',
        'supported_locales' => ['en'],
        'fallback_locale' => 'en',
        'auto_detect_locale' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Configure logging for admin panel activities including user actions,
    | resource changes, and system events.
    |
    */

    'logging' => [
        'enabled' => env('ADMIN_PANEL_LOGGING', true),
        'channel' => env('ADMIN_PANEL_LOG_CHANNEL', 'daily'),
        'log_queries' => env('ADMIN_PANEL_LOG_QUERIES', false),
        'log_user_actions' => true,
    ],

];
