# Menu API Reference

Complete API reference for JTD Admin Panel's menu system.

## Table of Contents

- [AdminPanel Class](#adminpanel-class)
- [MenuSection Class](#menusection-class)
- [MenuGroup Class](#menugroup-class)
- [MenuItem Class](#menuitem-class)
- [Menu Class](#menu-class)

## AdminPanel Class

### Static Methods

#### `mainMenu(callable $callback): void`

Register the main menu structure.

**Parameters:**
- `$callback` - Function that receives `Request $request` and returns array of menu items

**Example:**
```php
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Dashboard')->path('/dashboard'),
        MenuSection::make('Users', [
            MenuItem::resource('UserResource'),
        ]),
    ];
});
```

#### `userMenu(callable $callback): void`

Register the user dropdown menu.

**Parameters:**
- `$callback` - Function that receives `Request $request` and `Menu $menu`

**Example:**
```php
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $menu->append(MenuItem::make('Profile', '/profile'));
    return $menu;
});
```

#### `resolveMainMenu(Request $request): array`

Resolve the registered main menu.

**Returns:** Array of menu items

#### `serializeMainMenu(array $menu, Request $request): array`

Serialize menu for frontend consumption with authorization filtering.

**Returns:** Array of serialized menu data

## MenuSection Class

### Constructor

#### `make(string $name, array $items = []): static`

Create a new menu section.

**Parameters:**
- `$name` - Section display name
- `$items` - Array of MenuGroup or MenuItem objects

### Configuration Methods

#### `path(string $path): static`

Set direct navigation path. Cannot be used with `collapsible()`.

**Parameters:**
- `$path` - URL path for direct navigation

#### `icon(string $icon): static`

Set section icon.

**Parameters:**
- `$icon` - Icon name (Heroicons or custom)

#### `collapsible(bool $collapsible = true): static`

Make section collapsible. Cannot be used with `path()`.

**Parameters:**
- `$collapsible` - Whether section can be collapsed

#### `collapsed(bool $collapsed = true): static`

Set initial collapsed state.

**Parameters:**
- `$collapsed` - Whether section starts collapsed

#### `stateId(string $id): static`

Set custom state ID for persistence.

**Parameters:**
- `$id` - Unique identifier for state persistence

### Badge Methods

#### `withBadge(mixed $badge, string $type = 'primary'): static`

Add badge to section.

**Parameters:**
- `$badge` - Badge text/number or callable
- `$type` - Badge type: `primary`, `secondary`, `success`, `warning`, `danger`, `info`

#### `cacheBadge(int $ttl): static`

Cache badge calculation.

**Parameters:**
- `$ttl` - Cache TTL in seconds

### Authorization Methods

#### `canSee(callable $callback): static`

Set authorization callback.

**Parameters:**
- `$callback` - Function that receives `Request $request` and returns boolean

#### `cacheAuth(int $ttl): static`

Cache authorization result.

**Parameters:**
- `$ttl` - Cache TTL in seconds

### Utility Methods

#### `isVisible(Request $request = null): bool`

Check if section is visible to user.

#### `toArray(Request $request = null): array`

Serialize section to array.

## MenuGroup Class

### Constructor

#### `make(string $name, array $items = []): static`

Create a new menu group.

**Parameters:**
- `$name` - Group display name
- `$items` - Array of MenuItem objects

### Configuration Methods

#### `collapsible(bool $collapsible = true): static`

Make group collapsible.

#### `collapsed(bool $collapsed = true): static`

Set initial collapsed state.

#### `stateId(string $id): static`

Set custom state ID for persistence.

### Authorization Methods

#### `canSee(callable $callback): static`

Set authorization callback.

#### `cacheAuth(int $ttl): static`

Cache authorization result.

### Utility Methods

#### `isVisible(Request $request = null): bool`

Check if group is visible to user.

#### `toArray(Request $request = null): array`

Serialize group to array.

## MenuItem Class

### Factory Methods

#### `make(string $label, string $url): static`

Create basic menu item.

**Parameters:**
- `$label` - Display text
- `$url` - Navigation URL

#### `resource(string $resource): static`

Create resource menu item.

**Parameters:**
- `$resource` - Resource class name (e.g., 'UserResource')

#### `link(string $label, string $url): static`

Alias for `make()`.

#### `externalLink(string $label, string $url): static`

Create external link menu item.

**Parameters:**
- `$label` - Display text
- `$url` - External URL

#### `filter(string $label, string $resource): static`

Create filtered resource menu item.

**Parameters:**
- `$label` - Display text
- `$resource` - Resource class name

### Configuration Methods

#### `withIcon(string $icon): static`

Set menu item icon.

**Parameters:**
- `$icon` - Icon name

#### `withBadge(mixed $badge, string $type = 'primary'): static`

Add badge to menu item.

**Parameters:**
- `$badge` - Badge text/number or callable
- `$type` - Badge type

#### `withMeta(string $key, mixed $value): static`

Add metadata.

**Parameters:**
- `$key` - Metadata key
- `$value` - Metadata value

#### `openInNewTab(bool $newTab = true): static`

Open link in new tab.

#### `method(string $method, array $data = []): static`

Set HTTP method for link.

**Parameters:**
- `$method` - HTTP method (GET, POST, etc.)
- `$data` - Additional form data

### Filter Methods

#### `applies(string $filter, mixed $value, array $parameters = []): static`

Apply filter to filtered resource item.

**Parameters:**
- `$filter` - Filter class name
- `$value` - Filter value
- `$parameters` - Filter constructor parameters

### Badge Methods

#### `cacheBadge(int $ttl): static`

Cache badge calculation.

**Parameters:**
- `$ttl` - Cache TTL in seconds

#### `clearBadgeCache(): static`

Clear badge cache.

### Authorization Methods

#### `canSee(callable $callback): static`

Set authorization callback.

#### `cacheAuth(int $ttl): static`

Cache authorization result.

#### `clearAuthCache(): static`

Clear authorization cache.

### Utility Methods

#### `isVisible(Request $request = null): bool`

Check if item is visible to user.

#### `resolveBadge(Request $request = null): mixed`

Resolve badge value.

#### `toArray(Request $request = null): array`

Serialize item to array.

## Menu Class

Used for user menu management.

### Methods

#### `append(MenuItem $item): static`

Add item to end of menu.

#### `prepend(MenuItem $item): static`

Add item to beginning of menu.

#### `getItems(): array`

Get all menu items.

## Method Chaining Examples

### Complex Menu Section

```php
MenuSection::make('User Management', [
    MenuItem::resource('UserResource')
        ->withIcon('users')
        ->withBadge(fn() => User::count(), 'info')
        ->cacheBadge(300),
    
    MenuItem::filter('Active Users', 'UserResource')
        ->applies('StatusFilter', 'active')
        ->withIcon('check-circle')
        ->withBadge('Active', 'success'),
])
->icon('users')
->collapsible()
->stateId('user_management')
->canSee(fn($req) => $req->user()?->can('manage-users'))
->cacheAuth(600);
```

### Performance Optimized Item

```php
MenuItem::resource('OrderResource')
    ->withIcon('shopping-cart')
    ->withBadge(function () {
        return Order::where('status', 'pending')->count();
    }, 'warning')
    ->cacheBadge(300)
    ->canSee(function ($request) {
        return $request->user()?->hasComplexPermission();
    })
    ->cacheAuth(600);
```

### Filtered Resource with Multiple Filters

```php
MenuItem::filter('Premium Active Users', 'UserResource')
    ->applies('StatusFilter', 'active')
    ->applies('SubscriptionFilter', 'premium')
    ->applies('EmailFilter', '@company.com')
    ->withIcon('star')
    ->withBadge('VIP', 'warning');
```

## Return Types

### Serialized Menu Structure

```php
[
    [
        'name' => 'Section Name',
        'icon' => 'icon-name',
        'badge' => 'Badge Text',
        'badgeType' => 'primary',
        'collapsible' => true,
        'collapsed' => false,
        'stateId' => 'section_state_id',
        'path' => null,
        'visible' => true,
        'items' => [
            [
                'label' => 'Item Label',
                'url' => '/item/url',
                'icon' => 'item-icon',
                'badge' => 5,
                'badgeType' => 'info',
                'visible' => true,
                'meta' => [
                    'type' => 'resource',
                    'resource' => 'UserResource',
                    'method' => 'GET',
                    'target' => '_self',
                ],
            ],
        ],
    ],
]
```

## Error Handling

### Common Exceptions

#### `InvalidArgumentException`

Thrown when:
- Collapsible section has a path
- Section with path is made collapsible
- Invalid menu item type in user menu

#### `BadMethodCallException`

Thrown when:
- Method called on wrong menu component type
- Invalid filter application

### Exception Examples

```php
// This throws InvalidArgumentException
MenuSection::make('Invalid')
    ->collapsible()
    ->path('/invalid');

// This throws InvalidArgumentException
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $menu->append(MenuSection::make('Invalid')); // Only MenuItem allowed
});
```

---

For usage examples, see [Menu Documentation](menus.md) and [Nova Migration Guide](nova-migration-guide.md).
