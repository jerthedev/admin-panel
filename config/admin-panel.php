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
        'default_widgets' => [
            // Will be populated with default widget classes
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
