# Nova Compatibility Matrix

Detailed comparison between Laravel Nova and JTD Admin Panel menu systems.

## Overview

| Aspect | Nova v5 | JTD Admin Panel | Migration Effort |
|--------|---------|-----------------|------------------|
| **API Compatibility** | ✅ | ✅ 100% | None |
| **Enhanced Features** | ❌ | ✅ | Optional |
| **Performance** | ⚠️ | ✅ Enhanced | Optional |
| **Migration Path** | N/A | ✅ Seamless | Minimal |

## Core Menu Components

### MenuSection

| Feature | Nova v5 | JTD Admin Panel | Notes |
|---------|---------|-----------------|-------|
| `make(name, items)` | ✅ | ✅ | Identical API |
| `path(url)` | ✅ | ✅ | Identical API |
| `icon(icon)` | ✅ | ✅ | Identical API |
| `withBadge(badge, type)` | ✅ | ✅ | Identical API |
| `canSee(callback)` | ✅ | ✅ | Identical API |
| **Collapsible sections** | ❌ | ✅ | JTD enhancement |
| **State persistence** | ❌ | ✅ | JTD enhancement |
| **Authorization caching** | ❌ | ✅ | JTD enhancement |
| **Badge caching** | ❌ | ✅ | JTD enhancement |

### MenuGroup

| Feature | Nova v5 | JTD Admin Panel | Notes |
|---------|---------|-----------------|-------|
| `make(name, items)` | ✅ | ✅ | Identical API |
| `canSee(callback)` | ✅ | ✅ | Identical API |
| **Collapsible groups** | ❌ | ✅ | JTD enhancement |
| **Authorization caching** | ❌ | ✅ | JTD enhancement |

### MenuItem

| Feature | Nova v5 | JTD Admin Panel | Notes |
|---------|---------|-----------------|-------|
| `make(label, url)` | ✅ | ✅ | Identical API |
| `resource(class)` | ✅ | ✅ | Resource name format differs |
| `link(label, url)` | ✅ | ✅ | Identical API |
| `externalLink(label, url)` | ✅ | ✅ | Identical API |
| `withIcon(icon)` | ✅ | ✅ | Identical API |
| `withBadge(badge, type)` | ✅ | ✅ | Identical API |
| `canSee(callback)` | ✅ | ✅ | Identical API |
| `openInNewTab()` | ✅ | ✅ | Identical API |
| **Filtered resources** | ❌ | ✅ | JTD enhancement |
| **Authorization caching** | ❌ | ✅ | JTD enhancement |
| **Badge caching** | ❌ | ✅ | JTD enhancement |

## Menu Registration

### Main Menu

| Feature | Nova v5 | JTD Admin Panel | Migration |
|---------|---------|-----------------|-----------|
| Registration method | `Nova::mainMenu()` | `AdminPanel::mainMenu()` | Change class name |
| Callback signature | `function(Request $request)` | `function(Request $request)` | Identical |
| Return type | `array` | `array` | Identical |
| **Automatic filtering** | ❌ | ✅ | No code changes needed |

**Nova:**
```php
Nova::mainMenu(function (Request $request) {
    return [/* menu items */];
});
```

**JTD Admin Panel:**
```php
AdminPanel::mainMenu(function (Request $request) {
    return [/* menu items */];
});
```

### User Menu

| Feature | Nova v5 | JTD Admin Panel | Migration |
|---------|---------|-----------------|-----------|
| Registration method | `Nova::userMenu()` | `AdminPanel::userMenu()` | Change class name |
| Callback signature | `function(Request $request, Menu $menu)` | `function(Request $request, Menu $menu)` | Identical |
| Menu methods | `append()`, `prepend()` | `append()`, `prepend()` | Identical |
| **Default logout preservation** | ❌ | ✅ | Automatic enhancement |
| **Validation** | ❌ | ✅ | Prevents invalid items |

## Resource References

| Nova v5 | JTD Admin Panel | Migration Required |
|---------|-----------------|-------------------|
| `MenuItem::resource(User::class)` | `MenuItem::resource('UserResource')` | ✅ Change format |
| `MenuItem::resource(App\Models\Post::class)` | `MenuItem::resource('PostResource')` | ✅ Change format |

## Enhanced Features

### Collapsible Sections

| Feature | Nova v5 | JTD Admin Panel |
|---------|---------|-----------------|
| Collapsible sections | ❌ | ✅ |
| State persistence | ❌ | ✅ |
| Custom state IDs | ❌ | ✅ |
| Nested collapsible groups | ❌ | ✅ |

**JTD Admin Panel Only:**
```php
MenuSection::make('User Management', [
    MenuItem::resource('UserResource'),
])
->collapsible()
->collapsed()
->stateId('user_mgmt');
```

### Filtered Resources

| Feature | Nova v5 | JTD Admin Panel |
|---------|---------|-----------------|
| Filtered resource links | ❌ | ✅ |
| Multiple filters | ❌ | ✅ |
| Filter parameters | ❌ | ✅ |
| URL generation | ❌ | ✅ |

**JTD Admin Panel Only:**
```php
MenuItem::filter('Active Users', 'UserResource')
    ->applies('StatusFilter', 'active')
    ->applies('EmailFilter', '@company.com');
```

### Performance Optimization

| Feature | Nova v5 | JTD Admin Panel |
|---------|---------|-----------------|
| Authorization caching | ❌ | ✅ |
| Badge caching | ❌ | ✅ |
| Menu filtering | ❌ | ✅ |
| Cache management | ❌ | ✅ |

**JTD Admin Panel Only:**
```php
MenuItem::resource('UserResource')
    ->canSee($expensiveCallback)
    ->cacheAuth(300)
    ->withBadge($expensiveBadge)
    ->cacheBadge(300);
```

## Migration Complexity

### Zero-Change Migration

These work without any code changes:

```php
// ✅ Works identically in both systems
MenuSection::make('Content', [
    MenuItem::make('Posts', '/posts'),
    MenuItem::link('Categories', '/categories'),
    MenuItem::externalLink('Docs', 'https://docs.com'),
])
->icon('document-text')
->withBadge('New', 'info')
->canSee(fn($req) => $req->user()?->can('manage-content'));
```

### Minimal-Change Migration

Only class names need updating:

```php
// Nova
use Laravel\Nova\Nova;
Nova::mainMenu(function (Request $request) {
    return [MenuItem::resource(User::class)];
});

// JTD Admin Panel
use JTD\AdminPanel\Support\AdminPanel;
AdminPanel::mainMenu(function (Request $request) {
    return [MenuItem::resource('UserResource')];
});
```

### Enhancement Opportunities

Optional improvements available:

```php
// Basic Nova-compatible version
MenuSection::make('Users', [
    MenuItem::resource('UserResource'),
]);

// Enhanced JTD Admin Panel version
MenuSection::make('Users', [
    MenuItem::resource('UserResource')
        ->cacheAuth(300),
    MenuItem::filter('Active Users', 'UserResource')
        ->applies('StatusFilter', 'active'),
])
->collapsible()
->stateId('users_section');
```

## Feature Comparison Summary

### ✅ 100% Compatible Features
- Menu registration API
- Menu component creation
- Icon and badge system
- Authorization callbacks
- User menu customization
- External links
- Method chaining

### ✅ Enhanced Features (JTD Admin Panel Only)
- Collapsible sections and groups
- State persistence
- Filtered resource links
- Authorization caching
- Badge caching
- Automatic menu filtering
- Performance optimization
- Validation and error handling

### ⚠️ Minor Differences
- Resource references: `User::class` → `'UserResource'`
- Class names: `Nova::` → `AdminPanel::`
- Import statements

### ❌ No Breaking Changes
- All Nova menu code works with minimal updates
- No functionality is removed or changed
- Migration is additive, not destructive

## Migration Effort Estimate

| Project Size | Estimated Time | Main Tasks |
|--------------|----------------|------------|
| **Small** (1-5 menu sections) | 30 minutes | Update imports, resource names |
| **Medium** (5-15 menu sections) | 1-2 hours | Update imports, resource names, test |
| **Large** (15+ menu sections) | 2-4 hours | Update imports, resource names, test, optimize |
| **Complex** (Custom authorization) | 4-8 hours | Above + review auth logic, add caching |

## Recommended Migration Strategy

### Phase 1: Basic Migration (Required)
1. Update import statements
2. Change `Nova::` to `AdminPanel::`
3. Update resource references
4. Test functionality

### Phase 2: Enhancement (Optional)
1. Add collapsible sections where beneficial
2. Implement filtered resource links
3. Add authorization caching for expensive checks
4. Add badge caching for dynamic badges

### Phase 3: Optimization (Optional)
1. Review and optimize authorization logic
2. Add state persistence for user experience
3. Implement advanced filtering
4. Performance testing and tuning

---

For detailed migration instructions, see [Nova Migration Guide](nova-migration-guide.md).
