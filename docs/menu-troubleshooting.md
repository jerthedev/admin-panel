# Menu Troubleshooting Guide

Common issues and solutions for JTD Admin Panel menu system.

## Table of Contents

- [Menu Items Not Appearing](#menu-items-not-appearing)
- [Authorization Issues](#authorization-issues)
- [Performance Problems](#performance-problems)
- [Collapsible Sections](#collapsible-sections)
- [Filtered Resources](#filtered-resources)
- [Badge Issues](#badge-issues)
- [Cache Problems](#cache-problems)
- [Debug Tools](#debug-tools)

## Menu Items Not Appearing

### Issue: Menu items don't show up

**Symptoms:**
- Menu appears empty or missing items
- Some items visible, others not
- Menu structure incomplete

**Common Causes & Solutions:**

#### 1. Authorization Blocking Items

```php
// Problem: Authorization callback returns false
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        return $request->user()?->can('view-users'); // Returns false
    });

// Solution: Check user permissions
// Debug with explicit logging
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        $canSee = $request->user()?->can('view-users');
        \Log::info('UserResource canSee: ' . ($canSee ? 'true' : 'false'));
        return $canSee;
    });
```

#### 2. Resource Name Typos

```php
// Problem: Incorrect resource name
MenuItem::resource('UserResources'); // Extra 's'

// Solution: Use exact resource class name
MenuItem::resource('UserResource');
```

#### 3. Missing Resource Registration

```php
// Problem: Resource not registered
MenuItem::resource('PostResource'); // PostResource doesn't exist

// Solution: Ensure resource is registered
// In AdminPanelServiceProvider
AdminPanel::resources([
    \App\AdminPanel\PostResource::class,
]);
```

#### 4. Empty Sections Being Hidden

```php
// Problem: All items in section are unauthorized
MenuSection::make('Admin Tools', [
    MenuItem::resource('UserResource')->canSee(fn() => false),
    MenuItem::resource('RoleResource')->canSee(fn() => false),
])->collapsible(); // Empty collapsible sections are hidden

// Solution: Make section non-collapsible or add visible items
MenuSection::make('Admin Tools', [
    MenuItem::resource('UserResource')->canSee(fn() => false),
    MenuItem::resource('RoleResource')->canSee(fn() => false),
]); // Non-collapsible sections are kept even when empty
```

## Authorization Issues

### Issue: Authorization not working as expected

#### 1. Authorization Callback Errors

```php
// Problem: Exception in authorization callback
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        return $request->user()->role->name === 'admin'; // Null pointer if no role
    });

// Solution: Add null checks
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        return $request->user()?->role?->name === 'admin';
    });
```

#### 2. Cached Authorization Issues

```php
// Problem: Authorization cached with wrong result
MenuItem::resource('UserResource')
    ->canSee($callback)
    ->cacheAuth(3600); // Cached for 1 hour

// Solution: Clear cache or reduce TTL
MenuItem::resource('UserResource')
    ->canSee($callback)
    ->cacheAuth(300) // 5 minutes
    ->clearAuthCache(); // Clear existing cache
```

#### 3. Request Context Missing

```php
// Problem: Authorization depends on request data not available
MenuItem::resource('TeamResource')
    ->canSee(function ($request) {
        return $request->user()->team_id === $request->route('team'); // Route param not available
    });

// Solution: Use available request data only
MenuItem::resource('TeamResource')
    ->canSee(function ($request) {
        return $request->user()?->team_id !== null;
    });
```

## Performance Problems

### Issue: Menu loading slowly

#### 1. Expensive Authorization Checks

```php
// Problem: Complex database queries in authorization
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        return $request->user()->permissions()->where('name', 'view-users')->exists();
    });

// Solution: Cache authorization results
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        return $request->user()->permissions()->where('name', 'view-users')->exists();
    })
    ->cacheAuth(600); // Cache for 10 minutes
```

#### 2. Expensive Badge Calculations

```php
// Problem: Complex badge calculations
MenuItem::resource('OrderResource')
    ->withBadge(function () {
        return Order::with('items', 'customer', 'payments')->where('status', 'pending')->count();
    });

// Solution: Cache badge results and optimize query
MenuItem::resource('OrderResource')
    ->withBadge(function () {
        return Order::where('status', 'pending')->count(); // Simplified query
    })
    ->cacheBadge(300); // Cache for 5 minutes
```

#### 3. Too Many Menu Items

```php
// Problem: Large menu structure
AdminPanel::mainMenu(function (Request $request) {
    $sections = [];
    for ($i = 0; $i < 100; $i++) { // Too many sections
        $sections[] = MenuSection::make("Section $i", [/* items */]);
    }
    return $sections;
});

// Solution: Reduce menu items or use pagination/grouping
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Main', [
            MenuItem::link('View All Sections', '/admin/sections'),
        ]),
        // Only show most important sections
    ];
});
```

## Collapsible Sections

### Issue: Collapsible sections not working

#### 1. Section Has Path

```php
// Problem: Collapsible section with path
MenuSection::make('Dashboard')
    ->path('/dashboard')
    ->collapsible(); // This throws InvalidArgumentException

// Solution: Choose either path OR collapsible
// Option 1: Direct link
MenuSection::make('Dashboard')
    ->path('/dashboard');

// Option 2: Collapsible container
MenuSection::make('Dashboard', [
    MenuItem::link('Overview', '/dashboard'),
    MenuItem::link('Analytics', '/dashboard/analytics'),
])
->collapsible();
```

#### 2. State Not Persisting

```php
// Problem: Collapsed state not remembered
MenuSection::make('User Management', [...])
    ->collapsible()
    ->collapsed();

// Solution: Add unique state ID
MenuSection::make('User Management', [...])
    ->collapsible()
    ->collapsed()
    ->stateId('user_management_section');
```

#### 3. Frontend JavaScript Issues

**Problem:** Collapsible functionality not working in browser

**Solutions:**
1. Check browser console for JavaScript errors
2. Ensure admin panel assets are compiled and loaded
3. Verify Vue.js components are working

```bash
# Recompile assets
npm run build

# Check for JavaScript errors in browser console
# Verify admin panel routes are loaded
```

## Filtered Resources

### Issue: Filtered resources showing wrong results

#### 1. Incorrect Filter Class Names

```php
// Problem: Wrong filter class name
MenuItem::filter('Active Users', 'UserResource')
    ->applies('StatusFilters', 'active'); // Extra 's'

// Solution: Use exact filter class name
MenuItem::filter('Active Users', 'UserResource')
    ->applies('StatusFilter', 'active');
```

#### 2. Filter Parameters Not Working

```php
// Problem: Filter parameters not applied
MenuItem::filter('High Value Orders', 'OrderResource')
    ->applies('AmountFilter', '1000', ['operator' => '>']);

// Solution: Check filter implementation supports parameters
// Verify filter URL generation
$item = MenuItem::filter('High Value Orders', 'OrderResource')
    ->applies('AmountFilter', '1000', ['operator' => '>']);
\Log::info('Filter URL: ' . $item->url);
```

#### 3. Multiple Filters Conflicting

```php
// Problem: Conflicting filter values
MenuItem::filter('Conflicted Users', 'UserResource')
    ->applies('StatusFilter', 'active')
    ->applies('StatusFilter', 'inactive'); // Conflicts with above

// Solution: Use different filters or single filter with multiple values
MenuItem::filter('Active or Inactive Users', 'UserResource')
    ->applies('StatusFilter', ['active', 'inactive']);
```

## Badge Issues

### Issue: Badges not displaying correctly

#### 1. Badge Callback Errors

```php
// Problem: Exception in badge callback
MenuItem::resource('UserResource')
    ->withBadge(function () {
        return User::where('status', 'active')->count()->format(); // count() doesn't have format()
    });

// Solution: Fix callback logic
MenuItem::resource('UserResource')
    ->withBadge(function () {
        return User::where('status', 'active')->count();
    });
```

#### 2. Badge Caching Issues

```php
// Problem: Badge shows stale data
MenuItem::resource('OrderResource')
    ->withBadge(fn() => Order::where('status', 'pending')->count())
    ->cacheBadge(3600); // Cached for 1 hour

// Solution: Reduce cache TTL or clear cache when data changes
MenuItem::resource('OrderResource')
    ->withBadge(fn() => Order::where('status', 'pending')->count())
    ->cacheBadge(300); // 5 minutes

// Or clear cache when orders change
// In OrderObserver or similar
MenuItem::make('Orders', '/orders')->clearBadgeCache();
```

## Cache Problems

### Issue: Menu caches causing issues

#### 1. Stale Authorization Cache

```php
// Problem: User permissions changed but menu still shows old state
// Solution: Clear authorization cache
MenuItem::resource('UserResource')->clearAuthCache();

// Or clear all caches
php artisan cache:clear
```

#### 2. Badge Cache Not Updating

```php
// Problem: Badge shows old count after data changes
// Solution: Clear badge cache when data changes
MenuItem::resource('OrderResource')->clearBadgeCache();
```

#### 3. Cache Keys Conflicting

```php
// Problem: Different menu items sharing cache keys
// Solution: Use unique state IDs
MenuSection::make('Section 1', [...])
    ->stateId('unique_section_1')
    ->cacheAuth(300);

MenuSection::make('Section 2', [...])
    ->stateId('unique_section_2')
    ->cacheAuth(300);
```

## Debug Tools

### Enable Debug Mode

```php
// In config/admin-panel.php
'debug' => true,

// Or via environment
ADMIN_PANEL_DEBUG=true
```

### Debug Menu Structure

```php
// Log menu structure
AdminPanel::mainMenu(function (Request $request) {
    $menu = [
        // Your menu structure
    ];
    
    \Log::info('Menu structure:', $menu);
    return $menu;
});
```

### Debug Authorization

```php
// Add logging to authorization callbacks
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        $user = $request->user();
        $canSee = $user?->can('view-users');
        
        \Log::info('UserResource authorization', [
            'user_id' => $user?->id,
            'can_see' => $canSee,
            'permissions' => $user?->permissions->pluck('name'),
        ]);
        
        return $canSee;
    });
```

### Debug Badge Calculations

```php
// Log badge calculations
MenuItem::resource('OrderResource')
    ->withBadge(function () {
        $count = Order::where('status', 'pending')->count();
        \Log::info('Order badge count: ' . $count);
        return $count;
    });
```

### Check Cache Status

```php
// Check if item is using cache
$item = MenuItem::resource('UserResource')->cacheAuth(300);
\Log::info('Cache key: ' . $item->getAuthCacheKey());
\Log::info('Cache value: ' . \Cache::get($item->getAuthCacheKey()));
```

### Browser Debug

1. **Open browser developer tools**
2. **Check Console tab** for JavaScript errors
3. **Check Network tab** for failed requests
4. **Check Application/Storage tab** for localStorage state persistence

### Common Debug Commands

```bash
# Clear all caches
php artisan cache:clear

# Clear config cache
php artisan config:clear

# Clear route cache
php artisan route:clear

# Recompile assets
npm run build

# Check logs
tail -f storage/logs/laravel.log
```

---

For more help, see [Menu Documentation](menus.md) or [Nova Migration Guide](nova-migration-guide.md).
