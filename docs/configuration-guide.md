# Configuration Guide

## Overview

AdminPanel provides extensive configuration options to customize behavior, appearance, and functionality. This guide covers all configuration options and best practices.

## Configuration File

The main configuration file is located at `config/admin-panel.php`. Publish it using:

```bash
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider" --tag="config"
```

## Core Configuration

### Application Settings

```php
'app' => [
    'name' => env('ADMIN_PANEL_NAME', 'Admin Panel'),
    'url' => env('ADMIN_PANEL_URL', '/admin'),
    'timezone' => env('ADMIN_PANEL_TIMEZONE', 'UTC'),
    'locale' => env('ADMIN_PANEL_LOCALE', 'en'),
],
```

### Authentication

```php
'auth' => [
    'guard' => env('ADMIN_PANEL_GUARD', 'web'),
    'middleware' => ['web', 'auth'],
    'login_url' => '/login',
    'logout_url' => '/logout',
    'redirect_after_login' => '/admin',
    'redirect_after_logout' => '/',
],
```

### Database

```php
'database' => [
    'connection' => env('ADMIN_PANEL_DB_CONNECTION', null),
    'prefix' => env('ADMIN_PANEL_DB_PREFIX', ''),
],
```

## Dashboard Configuration

### Default Dashboard

```php
'dashboards' => [
    'default' => 'main',
    'available' => [
        'main' => [
            'name' => 'Main Dashboard',
            'description' => 'Main application dashboard',
            'icon' => 'HomeIcon',
            'cards' => [
                // Card classes
            ],
        ],
    ],
],
```

### Dashboard Behavior

```php
'behavior' => [
    'auto_refresh' => env('ADMIN_PANEL_AUTO_REFRESH', false),
    'refresh_interval' => env('ADMIN_PANEL_REFRESH_INTERVAL', 300), // seconds
    'lazy_loading' => env('ADMIN_PANEL_LAZY_LOADING', true),
    'preload_data' => env('ADMIN_PANEL_PRELOAD_DATA', false),
    'cache_data' => env('ADMIN_PANEL_CACHE_DATA', true),
    'cache_ttl' => env('ADMIN_PANEL_CACHE_TTL', 600), // seconds
],
```

## UI Configuration

### Theme Settings

```php
'theme' => [
    'default' => 'light',
    'available' => ['light', 'dark', 'auto'],
    'colors' => [
        'primary' => '#3b82f6',
        'secondary' => '#6b7280',
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'error' => '#ef4444',
    ],
],
```

### Layout Options

```php
'layout' => [
    'sidebar' => [
        'collapsible' => true,
        'default_collapsed' => false,
        'width' => '256px',
        'collapsed_width' => '64px',
    ],
    'header' => [
        'show_logo' => true,
        'show_user_menu' => true,
        'show_notifications' => true,
        'show_search' => true,
    ],
    'footer' => [
        'show' => true,
        'text' => 'Powered by AdminPanel',
        'links' => [
            'Documentation' => 'https://docs.example.com',
            'Support' => 'https://support.example.com',
        ],
    ],
],
```

### Responsive Design

```php
'responsive' => [
    'enabled' => true,
    'breakpoints' => [
        'sm' => '640px',
        'md' => '768px',
        'lg' => '1024px',
        'xl' => '1280px',
        '2xl' => '1536px',
    ],
    'mobile' => [
        'sidebar_overlay' => true,
        'compact_header' => true,
        'touch_gestures' => true,
    ],
],
```

## Performance Configuration

### Caching

```php
'cache' => [
    'enabled' => env('ADMIN_PANEL_CACHE_ENABLED', true),
    'store' => env('ADMIN_PANEL_CACHE_STORE', 'default'),
    'prefix' => env('ADMIN_PANEL_CACHE_PREFIX', 'admin_panel'),
    'ttl' => [
        'dashboards' => 3600, // 1 hour
        'cards' => 600,       // 10 minutes
        'metrics' => 300,     // 5 minutes
        'resources' => 1800,  // 30 minutes
    ],
],
```

### Asset Optimization

```php
'assets' => [
    'minify' => env('ADMIN_PANEL_MINIFY_ASSETS', true),
    'combine' => env('ADMIN_PANEL_COMBINE_ASSETS', true),
    'version' => env('ADMIN_PANEL_ASSET_VERSION', '1.0.0'),
    'cdn' => [
        'enabled' => env('ADMIN_PANEL_CDN_ENABLED', false),
        'url' => env('ADMIN_PANEL_CDN_URL', ''),
    ],
],
```

### Database Optimization

```php
'database' => [
    'query_cache' => env('ADMIN_PANEL_QUERY_CACHE', true),
    'eager_loading' => env('ADMIN_PANEL_EAGER_LOADING', true),
    'chunk_size' => env('ADMIN_PANEL_CHUNK_SIZE', 1000),
    'timeout' => env('ADMIN_PANEL_DB_TIMEOUT', 30),
],
```

## Security Configuration

### Authentication & Authorization

```php
'security' => [
    'csrf_protection' => true,
    'rate_limiting' => [
        'enabled' => true,
        'max_attempts' => 60,
        'decay_minutes' => 1,
    ],
    'session' => [
        'timeout' => 120, // minutes
        'secure' => env('ADMIN_PANEL_SECURE_SESSIONS', false),
        'same_site' => 'lax',
    ],
],
```

### Content Security Policy

```php
'csp' => [
    'enabled' => env('ADMIN_PANEL_CSP_ENABLED', false),
    'directives' => [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline'",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: https:",
    ],
],
```

## Feature Configuration

### Cards

```php
'cards' => [
    'auto_discovery' => true,
    'cache_enabled' => true,
    'cache_ttl' => 600,
    'refresh_interval' => 30,
    'max_per_dashboard' => 20,
    'allowed_components' => [
        // Whitelist of allowed Vue components
    ],
],
```

### Metrics

```php
'metrics' => [
    'cache_enabled' => true,
    'cache_ttl' => 300,
    'default_range' => 30, // days
    'available_ranges' => [30, 60, 90, 365],
    'timezone' => 'UTC',
    'date_format' => 'Y-m-d',
],
```

### Resources

```php
'resources' => [
    'auto_discovery' => true,
    'per_page' => 25,
    'max_per_page' => 100,
    'search_debounce' => 300, // milliseconds
    'export_enabled' => true,
    'import_enabled' => false,
],
```

## Environment Variables

### Core Settings

```env
# Application
ADMIN_PANEL_NAME="My Admin Panel"
ADMIN_PANEL_URL="/admin"
ADMIN_PANEL_TIMEZONE="America/New_York"

# Authentication
ADMIN_PANEL_GUARD="web"

# Performance
ADMIN_PANEL_CACHE_ENABLED=true
ADMIN_PANEL_AUTO_REFRESH=false
ADMIN_PANEL_REFRESH_INTERVAL=300

# Security
ADMIN_PANEL_SECURE_SESSIONS=true
ADMIN_PANEL_CSP_ENABLED=true
```

### Development Settings

```env
# Debug
ADMIN_PANEL_DEBUG=true
ADMIN_PANEL_LOG_LEVEL="debug"

# Assets
ADMIN_PANEL_MINIFY_ASSETS=false
ADMIN_PANEL_COMBINE_ASSETS=false

# Cache
ADMIN_PANEL_CACHE_ENABLED=false
```

### Production Settings

```env
# Performance
ADMIN_PANEL_CACHE_ENABLED=true
ADMIN_PANEL_MINIFY_ASSETS=true
ADMIN_PANEL_COMBINE_ASSETS=true

# Security
ADMIN_PANEL_SECURE_SESSIONS=true
ADMIN_PANEL_CSP_ENABLED=true

# CDN
ADMIN_PANEL_CDN_ENABLED=true
ADMIN_PANEL_CDN_URL="https://cdn.example.com"
```

## Advanced Configuration

### Custom Service Providers

```php
// config/admin-panel.php
'providers' => [
    App\Providers\AdminPanelServiceProvider::class,
    App\Providers\CustomDashboardProvider::class,
],
```

### Middleware Configuration

```php
'middleware' => [
    'web' => [
        'web',
        'auth',
        'verified',
    ],
    'api' => [
        'api',
        'auth:sanctum',
        'throttle:api',
    ],
    'custom' => [
        App\Http\Middleware\AdminPanelMiddleware::class,
    ],
],
```

### Event Listeners

```php
'events' => [
    'dashboard.viewed' => [
        App\Listeners\LogDashboardView::class,
    ],
    'card.refreshed' => [
        App\Listeners\UpdateCardCache::class,
    ],
],
```

## Configuration Validation

### Validation Rules

AdminPanel validates configuration on boot:

```php
'validation' => [
    'enabled' => env('ADMIN_PANEL_VALIDATE_CONFIG', true),
    'strict' => env('ADMIN_PANEL_STRICT_VALIDATION', false),
    'rules' => [
        'app.name' => 'required|string|max:255',
        'app.url' => 'required|string',
        'auth.guard' => 'required|string',
        'cache.ttl.*' => 'integer|min:0',
    ],
],
```

### Custom Validation

```php
// In a service provider
AdminPanel::configValidator(function ($config) {
    if ($config['cache']['enabled'] && !$config['cache']['store']) {
        throw new InvalidConfigurationException('Cache store must be specified when caching is enabled');
    }
});
```

## Configuration Helpers

### Runtime Configuration

```php
use JTD\AdminPanel\Support\AdminPanel;

// Get configuration value
$value = AdminPanel::config('cache.enabled');

// Set configuration value
AdminPanel::setConfig('cache.enabled', true);

// Check if feature is enabled
if (AdminPanel::isEnabled('cards.auto_discovery')) {
    // Feature is enabled
}
```

### Environment Detection

```php
// Check environment
if (AdminPanel::isProduction()) {
    // Production-specific logic
}

if (AdminPanel::isDevelopment()) {
    // Development-specific logic
}

if (AdminPanel::isTesting()) {
    // Testing-specific logic
}
```

## Best Practices

### Development

1. **Use environment variables** for environment-specific settings
2. **Keep sensitive data** in `.env` files
3. **Use caching** for expensive operations
4. **Enable debug mode** during development

### Production

1. **Disable debug mode** in production
2. **Enable asset optimization** (minify, combine)
3. **Use CDN** for static assets
4. **Enable security features** (CSP, secure sessions)
5. **Configure proper caching** strategies

### Security

1. **Use HTTPS** in production
2. **Enable CSRF protection**
3. **Configure rate limiting**
4. **Implement proper authentication**
5. **Use secure session settings**

## Troubleshooting

### Common Issues

#### Configuration Not Loading

```bash
# Clear configuration cache
php artisan config:clear

# Republish configuration
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider" --tag="config" --force
```

#### Cache Issues

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
```

#### Asset Issues

```bash
# Rebuild assets
npm run build

# Clear asset cache
php artisan admin-panel:clear-assets
```

### Debug Configuration

Enable debug mode to see detailed configuration information:

```php
'debug' => [
    'enabled' => env('APP_DEBUG', false),
    'config' => env('ADMIN_PANEL_DEBUG_CONFIG', false),
    'queries' => env('ADMIN_PANEL_DEBUG_QUERIES', false),
    'cache' => env('ADMIN_PANEL_DEBUG_CACHE', false),
],
```

## Migration Guide

### From Previous Versions

When upgrading AdminPanel, configuration may need updates:

```bash
# Backup current configuration
cp config/admin-panel.php config/admin-panel.php.backup

# Publish new configuration
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider" --tag="config" --force

# Compare and merge changes
diff config/admin-panel.php.backup config/admin-panel.php
```

### Breaking Changes

Check the changelog for breaking configuration changes and update accordingly.

## Next Steps

- **[Dashboard Guide](dashboard-phase3-guide.md)** - Dashboard implementation
- **[Cards Guide](cards-guide.md)** - Custom card development
- **[Performance Guide](performance/optimization.md)** - Performance optimization
- **[Security Guide](security/best-practices.md)** - Security best practices
