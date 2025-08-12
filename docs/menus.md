# Menu Customization

JTD Admin Panel provides a comprehensive menu customization system with full Laravel Nova compatibility. Create dynamic, role-based menus with authorization, filtering, and performance optimization.

## Table of Contents

- [Quick Start](#quick-start)
- [Menu Components](#menu-components)
- [Factory Methods](#factory-methods)
- [Authorization](#authorization)
- [Performance](#performance)
- [Nova Compatibility](#nova-compatibility)
- [Examples](#examples)
- [Troubleshooting](#troubleshooting)

## Quick Start

### Basic Menu Setup

```php
<?php

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;

// Register your custom menu
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Dashboard link
        MenuSection::make('Dashboard')
            ->path('/admin/dashboard')
            ->icon('home'),

        // Resource section
        MenuSection::make('Content Management', [
            MenuItem::resource('PostResource'),
            MenuItem::resource('CategoryResource'),
            MenuItem::link('Media Library', '/admin/media'),
        ])->icon('document-text'),

        // User menu
        MenuSection::make('Users', [
            MenuItem::resource('UserResource'),
            MenuItem::filter('Active Users', 'UserResource')
                ->applies('StatusFilter', 'active'),
        ])->icon('users'),
    ];
});
```

### User Menu Customization

```php
// Customize the user dropdown menu
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();
    
    $menu->prepend(
        MenuItem::make("Profile ({$user->name})", "/profile/{$user->id}")
            ->withIcon('user')
    );
    
    $menu->append(
        MenuItem::make('Account Settings', '/settings')
            ->withIcon('cog')
    );
    
    // Default logout link is automatically preserved
    return $menu;
});
```

## Menu Components

### MenuSection

Sections organize menu items into logical groups and can be collapsible or have direct paths.

```php
// Section with items (collapsible container)
MenuSection::make('User Management', [
    MenuItem::resource('UserResource'),
    MenuItem::link('User Reports', '/reports/users'),
])
->icon('users')
->collapsible()
->collapsed() // Start collapsed
->canSee(fn($req) => $req->user()?->can('manage-users'));

// Section with direct path (navigation link)
MenuSection::make('Dashboard')
    ->path('/admin/dashboard')
    ->icon('chart-bar')
    ->withBadge('New', 'info');
```

**Key Methods:**
- `make(string $name, array $items = [])` - Create section
- `path(string $path)` - Set direct navigation path
- `icon(string $icon)` - Set icon name
- `collapsible(bool $collapsible = true)` - Make collapsible
- `collapsed(bool $collapsed = true)` - Set initial collapsed state
- `stateId(string $id)` - Custom state ID for persistence
- `withBadge($badge, string $type = 'primary')` - Add badge
- `canSee(callable $callback)` - Authorization callback
- `cacheAuth(int $ttl)` - Cache authorization for performance

### MenuGroup

Groups organize items within sections without adding visual separation.

```php
MenuGroup::make('User Actions', [
    MenuItem::make('Create User', '/users/create'),
    MenuItem::make('Import Users', '/users/import'),
])
->collapsible()
->canSee(fn($req) => $req->user()?->can('manage-users'));
```

### MenuItem

Individual menu items with various types and behaviors.

```php
// Resource link
MenuItem::resource('UserResource')
    ->withBadge(fn() => User::count(), 'info');

// Custom link
MenuItem::make('Custom Page', '/admin/custom')
    ->withIcon('puzzle-piece')
    ->withBadge('Beta', 'warning');

// External link
MenuItem::externalLink('Documentation', 'https://docs.example.com')
    ->openInNewTab()
    ->withIcon('book-open');

// Filtered resource
MenuItem::filter('Active Users', 'UserResource')
    ->applies('StatusFilter', 'active')
    ->applies('EmailFilter', '@company.com');
```

## Factory Methods

### MenuItem Factory Methods

| Method | Description | Example |
|--------|-------------|---------|
| `make(label, url)` | Basic menu item | `MenuItem::make('Settings', '/settings')` |
| `resource(resource)` | Resource link | `MenuItem::resource('UserResource')` |
| `link(label, url)` | Alias for make() | `MenuItem::link('Reports', '/reports')` |
| `externalLink(label, url)` | External link | `MenuItem::externalLink('Docs', 'https://docs.com')` |
| `filter(label, resource)` | Filtered resource | `MenuItem::filter('Active', 'UserResource')` |

### Customization Methods

| Method | Description | Parameters |
|--------|-------------|------------|
| `withIcon(icon)` | Set icon | `string $icon` |
| `withBadge(badge, type)` | Add badge | `mixed $badge, string $type = 'primary'` |
| `withMeta(key, value)` | Add metadata | `string $key, mixed $value` |
| `canSee(callback)` | Authorization | `callable $callback` |
| `cacheAuth(ttl)` | Cache auth | `int $ttl` (seconds) |
| `openInNewTab()` | Open externally | None |

### Filter Methods

| Method | Description | Parameters |
|--------|-------------|------------|
| `applies(filter, value, params)` | Apply filter | `string $filter, mixed $value, array $params = []` |

**Filter Examples:**
```php
// Single filter
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

## Authorization

### Basic Authorization

```php
// Simple authorization
MenuItem::resource('UserResource')
    ->canSee(function ($request) {
        return $request->user()?->can('view-users');
    });

// Section authorization
MenuSection::make('Admin Tools', [...])
    ->canSee(fn($req) => $req->user()?->is_admin);
```

### Performance Optimization

```php
// Cache expensive authorization checks
MenuItem::make('Expensive Check', '/expensive')
    ->canSee(function ($request) {
        // Expensive operation (database query, API call)
        return $request->user()?->hasComplexPermission();
    })
    ->cacheAuth(300); // Cache for 5 minutes
```

### Authorization Patterns

```php
// Role-based
->canSee(fn($req) => $req->user()?->hasRole('admin'))

// Permission-based
->canSee(fn($req) => $req->user()?->can('manage-users'))

// Team-based
->canSee(fn($req) => $req->user()?->team_id === 1)

// Feature flag
->canSee(fn($req) => $req->user()?->hasFeatureFlag('beta_features'))

// Time-based
->canSee(function ($req) {
    return $req->user()?->is_admin && now()->hour >= 9 && now()->hour < 17;
})
```

## Performance

### Authorization Caching

Cache expensive authorization checks to improve performance:

```php
MenuItem::make('Complex Auth', '/complex')
    ->canSee(function ($request) {
        // Expensive authorization logic
        return $this->performExpensiveCheck($request->user());
    })
    ->cacheAuth(600); // Cache for 10 minutes
```

### Badge Caching

Cache dynamic badge values:

```php
MenuItem::resource('UserResource')
    ->withBadge(function () {
        return User::where('created_at', '>=', now()->subDay())->count();
    }, 'info')
    ->cacheBadge(300); // Cache badge for 5 minutes
```

### Menu Filtering

The system automatically filters unauthorized items during rendering:

```php
// Unauthorized items are automatically hidden
// Empty sections/groups are removed if collapsible
// Performance is optimized with caching
```

## Nova Compatibility

JTD Admin Panel provides 100% API compatibility with Laravel Nova's menu system.

### Direct Migration

Most Nova menu code works without modification:

```php
// Nova code (works as-is)
Nova::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Content', [
            MenuItem::resource(Post::class),
            MenuItem::resource(Category::class),
        ]),
    ];
});

// JTD Admin Panel (same API)
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Content', [
            MenuItem::resource('PostResource'),
            MenuItem::resource('CategoryResource'),
        ]),
    ];
});
```

### Enhanced Features

JTD Admin Panel extends Nova with additional features:

| Feature | Nova | JTD Admin Panel |
|---------|------|-----------------|
| Basic Menu Items | ✅ | ✅ |
| Menu Sections | ✅ | ✅ |
| Menu Groups | ✅ | ✅ |
| Authorization | ✅ | ✅ |
| User Menu | ✅ | ✅ |
| Filtered Resources | ❌ | ✅ |
| Collapsible Sections | ❌ | ✅ |
| Authorization Caching | ❌ | ✅ |
| Badge Caching | ❌ | ✅ |
| State Persistence | ❌ | ✅ |
| Menu Filtering | ❌ | ✅ |

## Examples

See comprehensive examples in:
- [examples/MenuItemFactoryExample.php](../examples/MenuItemFactoryExample.php)
- [examples/MenuAuthorizationExample.php](../examples/MenuAuthorizationExample.php)
- [examples/CollapsibleMenuExample.php](../examples/CollapsibleMenuExample.php)
- [examples/UserMenuExample.php](../examples/UserMenuExample.php)

## Troubleshooting

### Common Issues

**Menu items not appearing:**
- Check authorization with `canSee()` callbacks
- Verify user permissions and roles
- Check for typos in resource names

**Performance issues:**
- Add `cacheAuth()` to expensive authorization checks
- Use `cacheBadge()` for dynamic badge calculations
- Avoid complex logic in menu callbacks

**Collapsible sections not working:**
- Ensure sections don't have both `collapsible()` and `path()`
- Check that `stateId()` is unique across menu items
- Verify frontend JavaScript is loaded

**Filtered resources showing wrong results:**
- Check filter class names and parameters
- Verify filter applies to correct resource
- Test filter URLs manually

### Debug Mode

Enable debug mode to see menu structure:

```php
// In config/admin-panel.php
'debug' => true,

// Or via environment
ADMIN_PANEL_DEBUG=true
```

### Cache Issues

Clear menu caches:

```php
// Clear all menu caches
php artisan cache:clear

// Clear specific menu auth cache
MenuItem::make('Test', '/test')->clearAuthCache();
```

---

For more examples and advanced usage, see the [Nova Migration Guide](nova-migration-guide.md) and [API Reference](api-reference.md).
