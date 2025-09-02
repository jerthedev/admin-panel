# Migration Guide

## Overview

This guide helps you migrate from previous versions of AdminPanel or from Laravel Nova to AdminPanel. It covers breaking changes, new features, and step-by-step migration instructions.

## Migration Paths

### From Laravel Nova

AdminPanel is designed to be 100% compatible with Laravel Nova, making migration straightforward.

### From AdminPanel v1.x to v2.x

Major version upgrades may include breaking changes that require code updates.

### From AdminPanel v2.x to v3.x

Phase 3 introduces enhanced frontend features and may require configuration updates.

## Pre-Migration Checklist

### Backup Your Application

```bash
# Backup database
mysqldump -u username -p database_name > backup.sql

# Backup application files
tar -czf app_backup.tar.gz /path/to/your/app

# Backup configuration
cp -r config config_backup
```

### Review Current Implementation

1. **List all dashboards** and their configurations
2. **Document custom cards** and their dependencies
3. **Note any custom middleware** or authentication
4. **Review resource configurations**
5. **Check custom field implementations**

### Test Environment Setup

```bash
# Create a test branch
git checkout -b migration-test

# Set up testing environment
cp .env .env.testing
```

## Migration from Laravel Nova

### Step 1: Install AdminPanel

```bash
# Remove Nova (optional)
composer remove laravel/nova

# Install AdminPanel
composer require jerthedev/admin-panel

# Publish configuration
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider"
```

### Step 2: Update Namespaces

Replace Nova namespaces with AdminPanel equivalents:

```php
// Before (Nova)
use Laravel\Nova\Card;
use Laravel\Nova\Dashboard;
use Laravel\Nova\Resource;
use Laravel\Nova\Fields\Text;

// After (AdminPanel)
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Fields\Text;
```

### Step 3: Update Service Provider

```php
// Before (Nova)
class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public function boot()
    {
        parent::boot();
        
        Nova::serving(function (ServingNova $event) {
            // Nova-specific code
        });
    }
}

// After (AdminPanel)
class AdminPanelServiceProvider extends ServiceProvider
{
    public function boot()
    {
        AdminPanel::serving(function (ServingAdminPanel $event) {
            // AdminPanel-specific code
        });
    }
}
```

### Step 4: Update Routes

```php
// Before (Nova)
Route::middleware(['nova'])
    ->prefix('nova-api')
    ->group(function () {
        // Nova routes
    });

// After (AdminPanel)
Route::middleware(['admin-panel'])
    ->prefix('admin-api')
    ->group(function () {
        // AdminPanel routes
    });
```

### Step 5: Update Configuration

```php
// config/admin-panel.php (new)
return [
    'name' => env('ADMIN_PANEL_NAME', 'Admin Panel'),
    'path' => env('ADMIN_PANEL_PATH', '/admin'),
    
    // Migration from Nova config
    'brand' => [
        'name' => config('nova.name', 'Admin Panel'),
        'logo' => config('nova.logo'),
    ],
    
    'auth' => [
        'guard' => config('nova.guard', 'web'),
    ],
];
```

### Step 6: Migrate Resources

Nova resources work with minimal changes:

```php
// Before (Nova)
class User extends Resource
{
    public static $model = \App\Models\User::class;
    
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable(),
            Text::make('Email')->sortable(),
        ];
    }
}

// After (AdminPanel) - mostly unchanged
class User extends Resource
{
    public static $model = \App\Models\User::class;
    
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),
            Text::make('Name')->sortable(),
            Text::make('Email')->sortable(),
        ];
    }
}
```

### Step 7: Migrate Cards

Cards require minimal changes:

```php
// Before (Nova)
class UserMetrics extends Card
{
    public function component()
    {
        return 'user-metrics';
    }
    
    public function uriKey()
    {
        return 'user-metrics';
    }
}

// After (AdminPanel) - enhanced features available
class UserMetrics extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'User Metrics',
            'data' => $this->getData(),
            'refreshInterval' => 30, // New feature
        ]);
    }
}
```

### Step 8: Migrate Dashboards

```php
// Before (Nova)
class Main extends Dashboard
{
    public function cards()
    {
        return [
            new UserMetrics,
            new OrderMetrics,
        ];
    }
}

// After (AdminPanel) - enhanced with metadata
class Main extends Dashboard
{
    public function name(): string
    {
        return 'Main Dashboard';
    }
    
    public function description(): string
    {
        return 'Main application dashboard with overview metrics';
    }
    
    public function icon(): string
    {
        return 'HomeIcon';
    }
    
    public function cards(): array
    {
        return [
            UserMetrics::make(),
            OrderMetrics::make(),
        ];
    }
}
```

## Version-Specific Migrations

### AdminPanel v1.x to v2.x

#### Breaking Changes

1. **Configuration structure changed**
2. **Card API updated**
3. **Dashboard registration method changed**

#### Migration Steps

```bash
# Update composer
composer update jerthedev/admin-panel

# Republish configuration
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider" --tag="config" --force

# Update card implementations
php artisan admin-panel:migrate-cards
```

#### Configuration Updates

```php
// v1.x configuration
'dashboards' => [
    'main' => MainDashboard::class,
],

// v2.x configuration
'dashboards' => [
    'available' => [
        'main' => [
            'class' => MainDashboard::class,
            'name' => 'Main Dashboard',
            'description' => 'Main application dashboard',
        ],
    ],
],
```

### AdminPanel v2.x to v3.x (Phase 3)

#### New Features

1. **Enhanced dashboard selection**
2. **Advanced navigation system**
3. **Responsive design improvements**
4. **Performance optimizations**

#### Migration Steps

```bash
# Update to v3.x
composer update jerthedev/admin-panel

# Install new frontend dependencies
npm install

# Rebuild assets
npm run build

# Update configuration
php artisan admin-panel:upgrade-config
```

#### Dashboard Enhancements

```php
// v2.x dashboard
class AnalyticsDashboard extends Dashboard
{
    public function name(): string
    {
        return 'Analytics';
    }
}

// v3.x dashboard with enhanced features
class AnalyticsDashboard extends Dashboard
{
    public function name(): string
    {
        return 'Analytics';
    }
    
    public function description(): string
    {
        return 'Comprehensive analytics and reporting dashboard';
    }
    
    public function icon(): string
    {
        return 'ChartBarIcon';
    }
    
    public function category(): string
    {
        return 'Reports';
    }
    
    public function meta(): array
    {
        return [
            'refreshInterval' => 300,
            'autoRefresh' => true,
            'exportable' => true,
        ];
    }
}
```

## Database Migrations

### Schema Updates

Some versions may require database schema updates:

```bash
# Run new migrations
php artisan migrate

# If custom tables exist, update them
php artisan admin-panel:migrate-schema
```

### Data Migration

```php
// database/migrations/xxxx_migrate_admin_panel_data.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MigrateAdminPanelData extends Migration
{
    public function up()
    {
        // Migrate dashboard preferences
        DB::table('user_preferences')
            ->where('key', 'nova_dashboard')
            ->update(['key' => 'admin_panel_dashboard']);
            
        // Migrate card settings
        DB::table('card_settings')
            ->update(['provider' => 'admin-panel']);
    }
    
    public function down()
    {
        // Reverse migration
    }
}
```

## Asset Migration

### Frontend Assets

```bash
# Remove old assets
rm -rf public/vendor/nova
rm -rf public/vendor/admin-panel-old

# Publish new assets
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider" --tag="assets"

# Update build configuration
npm run build
```

### Custom Vue Components

```javascript
// Before (Nova)
import { Card } from 'laravel-nova'

export default {
  extends: Card,
  // component logic
}

// After (AdminPanel)
import { Card } from '@jtd/admin-panel'

export default {
  extends: Card,
  // component logic - mostly unchanged
}
```

## Testing Migration

### Update Test Suite

```php
// Before (Nova tests)
use Laravel\Nova\Testing\Browser\Components\IndexComponent;

class NovaTest extends DuskTestCase
{
    public function test_nova_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/nova')
                ->assertSee('Dashboard');
        });
    }
}

// After (AdminPanel tests)
use JTD\AdminPanel\Testing\Browser\Components\IndexComponent;

class AdminPanelTest extends DuskTestCase
{
    public function test_admin_panel_dashboard()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->user)
                ->visit('/admin')
                ->assertSee('Dashboard');
        });
    }
}
```

### Update API Tests

```php
// Update API endpoints in tests
// Before: /nova-api/
// After: /admin-api/

$response = $this->getJson('/admin-api/dashboards/main');
```

## Post-Migration Tasks

### Verification Checklist

- [ ] All dashboards load correctly
- [ ] Cards display proper data
- [ ] User authentication works
- [ ] API endpoints respond correctly
- [ ] Frontend assets load properly
- [ ] Tests pass
- [ ] Performance is acceptable

### Performance Optimization

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize assets
npm run production
```

### Monitoring

Set up monitoring to track migration success:

```php
// Monitor dashboard load times
AdminPanel::serving(function ($event) {
    $start = microtime(true);
    
    $event->listen('dashboard.loaded', function () use ($start) {
        $loadTime = microtime(true) - $start;
        Log::info('Dashboard load time', ['time' => $loadTime]);
    });
});
```

## Rollback Plan

### Preparation

```bash
# Create rollback script
cat > rollback.sh << 'EOF'
#!/bin/bash
# Restore database backup
mysql -u username -p database_name < backup.sql

# Restore application files
tar -xzf app_backup.tar.gz

# Restore configuration
cp -r config_backup/* config/

# Clear caches
php artisan cache:clear
php artisan config:clear
EOF

chmod +x rollback.sh
```

### Emergency Rollback

```bash
# If migration fails, execute rollback
./rollback.sh

# Restore previous version
composer install --no-dev
npm run production
```

## Common Issues

### Namespace Conflicts

```php
// Issue: Class not found
Class 'Laravel\Nova\Card' not found

// Solution: Update imports
use JTD\AdminPanel\Cards\Card;
```

### Configuration Errors

```php
// Issue: Configuration key not found
config('nova.name') returns null

// Solution: Update configuration references
config('admin-panel.app.name')
```

### Asset Loading Issues

```bash
# Issue: Assets not loading
# Solution: Republish and rebuild
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider" --tag="assets" --force
npm run build
```

### Database Connection Issues

```php
// Issue: Wrong database connection
// Solution: Update configuration
'database' => [
    'connection' => env('ADMIN_PANEL_DB_CONNECTION', 'mysql'),
],
```

## Support and Resources

### Documentation

- **[Installation Guide](installation.md)** - Fresh installation
- **[Configuration Guide](configuration-guide.md)** - Configuration options
- **[Dashboard Guide](dashboard-phase3-guide.md)** - Dashboard development
- **[Cards Guide](cards-guide.md)** - Card development

### Community

- **GitHub Issues** - Report migration problems
- **Discord Community** - Get help from other users
- **Stack Overflow** - Search for solutions

### Professional Support

For complex migrations or enterprise support:
- **Migration Services** - Professional migration assistance
- **Training** - Team training on AdminPanel
- **Custom Development** - Custom feature development

## Migration Timeline

### Small Applications (< 10 dashboards)

- **Planning**: 1-2 days
- **Migration**: 2-3 days
- **Testing**: 1-2 days
- **Total**: 4-7 days

### Medium Applications (10-50 dashboards)

- **Planning**: 3-5 days
- **Migration**: 1-2 weeks
- **Testing**: 3-5 days
- **Total**: 2-3 weeks

### Large Applications (50+ dashboards)

- **Planning**: 1-2 weeks
- **Migration**: 3-4 weeks
- **Testing**: 1-2 weeks
- **Total**: 5-8 weeks

## Next Steps

After successful migration:

1. **Explore new features** introduced in your target version
2. **Optimize performance** using new capabilities
3. **Update documentation** for your team
4. **Plan future upgrades** to stay current
5. **Contribute back** to the community with feedback and improvements
