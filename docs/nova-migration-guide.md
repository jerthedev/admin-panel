# Nova Menu Migration Guide

This guide helps you migrate from Laravel Nova's menu system to JTD Admin Panel with minimal code changes and enhanced features.

## Table of Contents

- [Quick Migration](#quick-migration)
- [API Compatibility](#api-compatibility)
- [Enhanced Features](#enhanced-features)
- [Step-by-Step Migration](#step-by-step-migration)
- [Common Patterns](#common-patterns)
- [Troubleshooting](#troubleshooting)

## Quick Migration

### 1. Replace Nova with AdminPanel

**Before (Nova):**
```php
use Laravel\Nova\Nova;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Menu\MenuItem;

Nova::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Content', [
            MenuItem::resource(Post::class),
            MenuItem::resource(Category::class),
        ]),
    ];
});
```

**After (JTD Admin Panel):**
```php
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Menu\MenuItem;

AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Content', [
            MenuItem::resource('PostResource'),
            MenuItem::resource('CategoryResource'),
        ]),
    ];
});
```

### 2. Update Resource References

**Nova uses model classes:**
```php
MenuItem::resource(User::class)
MenuItem::resource(App\Models\Post::class)
```

**JTD Admin Panel uses resource class names:**
```php
MenuItem::resource('UserResource')
MenuItem::resource('PostResource')
```

## API Compatibility

### 100% Compatible Methods

These methods work exactly the same:

```php
// Menu sections
MenuSection::make('Section Name', $items)
MenuSection::make('Dashboard')->path('/dashboard')

// Menu items
MenuItem::make('Label', '/url')
MenuItem::link('Label', '/url')
MenuItem::externalLink('Label', 'https://external.com')

// Customization
->withIcon('icon-name')
->withBadge('Badge Text', 'badge-type')
->canSee(function ($request) { return true; })

// User menu
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $menu->append(MenuItem::make('Profile', '/profile'));
    return $menu;
});
```

### Enhanced Methods

These methods have additional features:

```php
// Collapsible sections (NEW)
MenuSection::make('Collapsible', $items)
    ->collapsible()
    ->collapsed()
    ->stateId('custom_id');

// Authorization caching (NEW)
MenuItem::resource('UserResource')
    ->canSee($expensiveCallback)
    ->cacheAuth(300); // Cache for 5 minutes

// Filtered resources (NEW)
MenuItem::filter('Active Users', 'UserResource')
    ->applies('StatusFilter', 'active');

// Badge caching (NEW)
MenuItem::resource('UserResource')
    ->withBadge($expensiveCallback, 'info')
    ->cacheBadge(300);
```

## Enhanced Features

### 1. Collapsible Sections

**Nova:** Sections are always expanded
**JTD Admin Panel:** Sections can be collapsible with state persistence

```php
// Collapsible section
MenuSection::make('User Management', [
    MenuItem::resource('UserResource'),
    MenuItem::resource('RoleResource'),
])
->collapsible()
->collapsed() // Start collapsed
->stateId('user_mgmt'); // Persistent state
```

### 2. Filtered Resources

**Nova:** No built-in filtered resource links
**JTD Admin Panel:** Direct filtered resource links

```php
// Show only active users
MenuItem::filter('Active Users', 'UserResource')
    ->applies('StatusFilter', 'active');

// Multiple filters
MenuItem::filter('Premium Active Users', 'UserResource')
    ->applies('StatusFilter', 'active')
    ->applies('SubscriptionFilter', 'premium');

// Filter with parameters
MenuItem::filter('High Value Orders', 'OrderResource')
    ->applies('AmountFilter', '1000', ['operator' => '>=']);
```

### 3. Performance Optimization

**Nova:** No built-in caching
**JTD Admin Panel:** Authorization and badge caching

```php
// Cache expensive authorization
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        // Expensive database query or API call
        return $request->user()->hasComplexPermission();
    })
    ->cacheAuth(600); // Cache for 10 minutes

// Cache dynamic badges
MenuItem::resource('OrderResource')
    ->withBadge(function () {
        return Order::where('status', 'pending')->count();
    }, 'warning')
    ->cacheBadge(300); // Cache for 5 minutes
```

### 4. Menu Filtering

**Nova:** Shows all menu items regardless of authorization
**JTD Admin Panel:** Automatically filters unauthorized items

```php
// Unauthorized items are automatically hidden
// Empty sections/groups are removed if collapsible
// No additional code required
```

## Step-by-Step Migration

### Step 1: Install JTD Admin Panel

```bash
composer require jerthedev/admin-panel
php artisan admin-panel:install
```

### Step 2: Update Menu Registration

**Find your Nova menu registration:**
```php
// In NovaServiceProvider or similar
Nova::mainMenu(function (Request $request) {
    // Your menu structure
});
```

**Replace with AdminPanel:**
```php
// In AdminPanelServiceProvider or AppServiceProvider
AdminPanel::mainMenu(function (Request $request) {
    // Same menu structure with minor updates
});
```

### Step 3: Update Resource References

**Replace model classes with resource names:**
```php
// Before
MenuItem::resource(User::class)
MenuItem::resource(App\Models\Post::class)

// After
MenuItem::resource('UserResource')
MenuItem::resource('PostResource')
```

### Step 4: Update Imports

**Replace Nova imports:**
```php
// Remove these
use Laravel\Nova\Nova;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Menu\MenuItem;

// Add these
use JTD\AdminPanel\Support\AdminPanel;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Menu\MenuItem;
```

### Step 5: Test and Enhance

1. **Test basic functionality** - Ensure all menu items appear
2. **Add enhanced features** - Implement collapsible sections, filtering, caching
3. **Optimize performance** - Add caching to expensive operations

## Common Patterns

### Role-Based Menus

**Nova pattern:**
```php
Nova::mainMenu(function (Request $request) {
    $items = [
        MenuSection::make('Content', [
            MenuItem::resource(Post::class),
        ]),
    ];

    if ($request->user()->isAdmin()) {
        $items[] = MenuSection::make('Admin', [
            MenuItem::resource(User::class),
        ]);
    }

    return $items;
});
```

**JTD Admin Panel pattern (same + enhanced):**
```php
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Content', [
            MenuItem::resource('PostResource'),
        ]),

        MenuSection::make('Admin', [
            MenuItem::resource('UserResource'),
        ])->canSee(fn($req) => $req->user()->isAdmin())
          ->cacheAuth(300), // Cache admin check
    ];
});
```

### Dynamic Badges

**Nova pattern:**
```php
MenuItem::resource(Order::class)
    ->withBadge(function () {
        return Order::where('status', 'pending')->count();
    });
```

**JTD Admin Panel pattern (same + caching):**
```php
MenuItem::resource('OrderResource')
    ->withBadge(function () {
        return Order::where('status', 'pending')->count();
    }, 'warning')
    ->cacheBadge(300); // Cache badge calculation
```

### User Menu Customization

**Nova pattern:**
```php
Nova::userMenu(function (Request $request, Menu $menu) {
    $menu->append(
        MenuItem::make('Profile', '/profile')
    );

    return $menu;
});
```

**JTD Admin Panel pattern (identical):**
```php
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $menu->append(
        MenuItem::make('Profile', '/profile')
    );

    return $menu;
});
```

## Troubleshooting

### Menu Items Not Appearing

**Issue:** Menu items from Nova don't show up

**Solution:**
1. Check resource name format: `'UserResource'` not `User::class`
2. Verify imports are updated
3. Check authorization callbacks

### Performance Issues

**Issue:** Menu loading slowly

**Solution:**
1. Add `cacheAuth()` to expensive authorization checks
2. Use `cacheBadge()` for dynamic badge calculations
3. Avoid complex database queries in menu callbacks

### Authorization Not Working

**Issue:** Authorization behaves differently than Nova

**Solution:**
1. JTD Admin Panel automatically filters unauthorized items
2. Use `canSee()` callbacks for authorization
3. Check user permissions and roles

### Resource Links Broken

**Issue:** Resource links don't work

**Solution:**
1. Ensure resources are registered with AdminPanel
2. Check resource class names match exactly
3. Verify routes are published

## Migration Checklist

- [ ] Install JTD Admin Panel package
- [ ] Update menu registration from `Nova::mainMenu` to `AdminPanel::mainMenu`
- [ ] Replace model classes with resource names in `MenuItem::resource()`
- [ ] Update import statements
- [ ] Test all menu items appear and work
- [ ] Add enhanced features (collapsible, filtering, caching)
- [ ] Optimize performance with caching
- [ ] Update user menu if customized
- [ ] Test authorization and permissions
- [ ] Update documentation and team knowledge

## Next Steps

After migration, explore enhanced features:

1. **[Collapsible Sections](menus.md#collapsible-sections)** - Improve menu organization
2. **[Filtered Resources](menus.md#filtered-resources)** - Quick access to filtered data
3. **[Performance Optimization](menus.md#performance)** - Cache expensive operations
4. **[Advanced Authorization](menus.md#authorization)** - Role-based and feature-flag menus

---

For detailed API documentation, see [Menu Documentation](menus.md).
