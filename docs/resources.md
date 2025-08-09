# Getting Started with Resources

Resources are the primary building blocks of JTD Admin Panel. A resource corresponds to an Eloquent model and defines how that model should be displayed and managed in the admin interface.

## Overview

Resources provide a Nova-like API for defining:
- **Fields** - How model attributes are displayed and edited
- **Authorization** - Who can view, create, update, and delete records
- **Search** - Which fields are searchable
- **Navigation** - How the resource appears in menus
- **Actions** - Custom bulk operations
- **Filters** - Ways to filter the resource index
- **Metrics** - Dashboard statistics

## Creating Resources

### Using the Artisan Command

The easiest way to create a resource is using the `admin-panel:resource` command:

```bash
# Create a resource for the User model
php artisan admin-panel:resource UserResource --model=User

# Create a resource with automatic model detection
php artisan admin-panel:resource PostResource
```

This creates a new resource class in `app/Admin/Resources/` with a complete structure and example fields.

### Manual Creation

You can also create resources manually by extending the base `Resource` class:

```php
<?php

namespace App\Admin\Resources;

use Illuminate\Http\Request;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Fields\ID;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Email;
use App\Models\User;

class UserResource extends Resource
{
    /**
     * The model the resource corresponds to.
     */
    public static string $model = User::class;

    /**
     * The single value that should be used to represent the resource.
     */
    public static string $title = 'name';

    /**
     * The columns that should be searched.
     */
    public static array $search = [
        'id', 'name', 'email',
    ];

    /**
     * Get the fields displayed by the resource.
     */
    public function fields(Request $request): array
    {
        return [
            ID::make()->sortable(),
            
            Text::make('Name')
                ->sortable()
                ->rules('required', 'max:255'),
                
            Email::make('Email')
                ->sortable()
                ->rules('required', 'email', 'unique:users,email'),
        ];
    }
}
```

## Resource Registration

JTD Admin Panel supports two methods for registering resources:

### 1. Automatic Discovery (Recommended)

By default, resources are automatically discovered from the `app/Admin/Resources/` directory. Simply create your resource class in this directory and it will be automatically registered.

```php
// app/Admin/Resources/UserResource.php
class UserResource extends Resource
{
    // Resource definition...
}
```

The resource will be automatically available in the admin panel without any additional configuration.

### 2. Manual Registration

For more control, you can manually register resources in your `AdminServiceProvider`:

```bash
# Create an AdminServiceProvider if you don't have one
php artisan admin-panel:install --create-service-provider
```

```php
// app/Providers/AdminServiceProvider.php
use JTD\AdminPanel\Support\AdminPanel;

public function boot(): void
{
    AdminPanel::resources([
        \App\Admin\Resources\UserResource::class,
        \App\Admin\Resources\PostResource::class,
    ]);
}
```

## Resource Properties

### Model Association

Every resource must specify which Eloquent model it represents:

```php
public static string $model = User::class;
```

### Title Field

The title field determines how individual records are represented:

```php
// Use the 'name' field as the title
public static string $title = 'name';

// Or use a computed title
public function title(): string
{
    return $this->resource->first_name . ' ' . $this->resource->last_name;
}
```

### Search Configuration

Define which fields should be searchable:

```php
// Search by specific columns
public static array $search = [
    'id', 'name', 'email',
];

// Or use field-level searchable() method
Text::make('Name')->searchable(),
Email::make('Email')->searchable(),
```

### Resource Grouping

Organize resources in the navigation menu using groups:

```php
public static ?string $group = 'User Management';
```

Resources with the same group will be organized together in the navigation.

### Global Search

Control whether the resource appears in global search:

```php
public static bool $globallySearchable = true;
```

## Defining Fields

The `fields` method defines how your model's attributes are displayed and edited:

```php
public function fields(Request $request): array
{
    return [
        ID::make()->sortable(),
        
        Text::make('Name')
            ->sortable()
            ->searchable()
            ->rules('required', 'max:255'),
            
        Email::make('Email')
            ->sortable()
            ->rules('required', 'email', 'unique:users,email'),
            
        Select::make('Role')
            ->options([
                'admin' => 'Administrator',
                'editor' => 'Editor',
                'user' => 'User',
            ])
            ->displayUsingLabels()
            ->filterable(),
            
        Boolean::make('Active')
            ->sortable()
            ->filterable(),
            
        DateTime::make('Created At')
            ->onlyOnIndex()
            ->sortable(),
    ];
}
```

### Field Visibility

Control when fields are displayed:

```php
Text::make('Name')
    ->showOnIndex()      // Show on index page
    ->showOnDetail()     // Show on detail page
    ->showOnCreating()   // Show when creating
    ->showOnUpdating()   // Show when updating
    ->hideFromIndex()    // Hide from index page
    ->onlyOnForms()      // Only show on create/update forms
    ->exceptOnForms()    // Show everywhere except forms
```

### Field Validation

Add validation rules to fields:

```php
Text::make('Name')
    ->rules('required', 'max:255'),
    
Email::make('Email')
    ->rules('required', 'email', 'unique:users,email,' . $this->resource->id),
    
Number::make('Age')
    ->rules('required', 'integer', 'min:18', 'max:120'),
```

## Resource Authorization

Control who can access and modify resources:

```php
/**
 * Determine if the current user can view any resources.
 */
public function authorizedToViewAny(Request $request): bool
{
    return $request->user()->can('viewAny', User::class);
}

/**
 * Determine if the current user can view the resource.
 */
public function authorizedToView(Request $request): bool
{
    return $request->user()->can('view', $this->resource);
}

/**
 * Determine if the current user can create new resources.
 */
public function authorizedToCreate(Request $request): bool
{
    return $request->user()->can('create', User::class);
}

/**
 * Determine if the current user can update the resource.
 */
public function authorizedToUpdate(Request $request): bool
{
    return $request->user()->can('update', $this->resource);
}

/**
 * Determine if the current user can delete the resource.
 */
public function authorizedToDelete(Request $request): bool
{
    return $request->user()->can('delete', $this->resource);
}
```

## Navigation Customization

Customize how resources appear in the navigation menu:

```php
use JTD\AdminPanel\Menu\MenuItem;

public function menu(Request $request): MenuItem
{
    return parent::menu($request)
        ->withIcon('users')
        ->withBadge(fn() => User::where('active', true)->count())
        ->badgeType('success');
}
```

### Menu Options

```php
public function menu(Request $request): MenuItem
{
    return MenuItem::make('Users', route('admin-panel.resources.index', 'users'))
        ->withIcon('users')                    // Heroicon name
        ->withBadge(fn() => User::count())     // Dynamic badge
        ->badgeType('primary')                 // Badge color
        ->when($request->user()->isAdmin(), fn($menu) =>
            $menu->withBadge(fn() => User::where('pending', true)->count(), 'warning')
        );
}
```

## Query Customization

Customize the queries used to retrieve resources:

```php
/**
 * Build an "index" query for the given resource.
 */
public static function indexQuery(Request $request, $query)
{
    return $query->with(['roles', 'profile']);
}

/**
 * Build a "detail" query for the given resource.
 */
public static function detailQuery(Request $request, $query)
{
    return $query->with(['roles', 'profile', 'posts']);
}

/**
 * Build a "relatable" query for the given resource.
 */
public static function relatableQuery(Request $request, $query)
{
    return $query->where('active', true);
}
```

## Resource Labels

Customize how resources are labeled throughout the interface:

```php
/**
 * Get the displayable label of the resource.
 */
public static function label(): string
{
    return 'Team Members';
}

/**
 * Get the displayable singular label of the resource.
 */
public static function singularLabel(): string
{
    return 'Team Member';
}

/**
 * Get the URI key for the resource.
 */
public static function uriKey(): string
{
    return 'team-members';
}
```

## Available Commands

JTD Admin Panel provides several helpful commands for working with resources:

```bash
# Create a new resource
php artisan admin-panel:resource UserResource --model=User

# List all registered resources
php artisan admin-panel:resources

# List resources with validation
php artisan admin-panel:resources --validate

# List resources with statistics
php artisan admin-panel:resources --stats

# Filter resources by group
php artisan admin-panel:resources --group="User Management"

# Install the admin panel
php artisan admin-panel:install

# Create an AdminServiceProvider for manual registration
php artisan admin-panel:install --create-service-provider

# Run diagnostics on your installation
php artisan admin-panel:doctor

# Clear admin panel caches
php artisan admin-panel:clear-cache
```

## Next Steps

Now that you understand the basics of resources, explore these advanced topics:

- **[Field Types](./fields/README.md)** - Complete guide to all available field types
- **[Relationships](./fields/relationship-fields.md)** - Managing model relationships
- **[Actions](./actions.md)** - Creating custom bulk operations
- **[Filters](./filters.md)** - Adding filtering capabilities
- **[Metrics](./metrics.md)** - Building dashboard statistics
- **[Authorization](./authorization.md)** - Advanced permission handling
- **[Custom Pages](./custom-pages.md)** - Creating custom admin interfaces

## Advanced Features

### Filters

Add filtering capabilities to your resource index:

```php
use JTD\AdminPanel\Filters\SelectFilter;
use JTD\AdminPanel\Filters\DateFilter;

public function filters(Request $request): array
{
    return [
        new SelectFilter('Status', 'status', [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]),

        new DateFilter('Created After', 'created_at', '>='),
    ];
}
```

### Actions

Define custom bulk operations:

```php
use JTD\AdminPanel\Actions\Action;

public function actions(Request $request): array
{
    return [
        (new Action('Activate Users'))
            ->handle(function ($request, $models) {
                $models->each->update(['active' => true]);
                return 'Users activated successfully!';
            }),
    ];
}
```

### Metrics

Add dashboard metrics for your resource:

```php
use JTD\AdminPanel\Metrics\Value;
use JTD\AdminPanel\Metrics\Trend;

public function metrics(Request $request): array
{
    return [
        new Value('Total Users', User::count()),
        new Value('Active Users', User::where('active', true)->count()),
        new Trend('New Users', User::class, 'created_at'),
    ];
}
```

### Soft Deletes

For models using soft deletes, resources automatically support trash functionality:

```php
use Illuminate\Database\Eloquent\SoftDeletes;
use JTD\AdminPanel\Resources\Concerns\HasSoftDeletes;

class UserResource extends Resource
{
    use HasSoftDeletes;

    // Resource definition...
}
```

This adds restore and force delete capabilities to your resource.

### Resource Policies

Create Laravel policies for fine-grained authorization:

```bash
php artisan make:policy UserPolicy --model=User
```

```php
// app/Policies/UserPolicy.php
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-users');
    }

    public function view(User $user, User $model): bool
    {
        return $user->can('view-users') || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return $user->can('create-users');
    }

    public function update(User $user, User $model): bool
    {
        return $user->can('edit-users') || $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->can('delete-users') && $user->id !== $model->id;
    }
}
```

Then reference the policy in your resource:

```php
class UserResource extends Resource
{
    public static ?string $policy = UserPolicy::class;

    // Resource definition...
}
```

## Troubleshooting

### Resource Not Appearing

1. **Check auto-discovery is enabled**:
   ```php
   // config/admin-panel.php
   'resources' => [
       'auto_discovery' => true,
   ],
   ```

2. **Verify file location**: Resources must be in `app/Admin/Resources/`

3. **Check class structure**: Ensure your resource extends the base `Resource` class

4. **Clear cache**: Run `php artisan admin-panel:clear-cache`

### Permission Issues

1. **Check authorization methods**: Ensure authorization methods return `true` for testing

2. **Verify user authentication**: Make sure users are properly authenticated

3. **Review policies**: Check that policy methods exist and return expected values

### Performance Issues

1. **Enable caching**:
   ```php
   'performance' => [
       'cache_resources' => true,
       'cache_ttl' => 3600,
   ],
   ```

2. **Optimize queries**: Use `indexQuery()` to eager load relationships

3. **Limit search fields**: Only make necessary fields searchable

## Configuration

Resources can be configured in `config/admin-panel.php`:

```php
'resources' => [
    'per_page' => 25,                    // Default pagination
    'max_per_page' => 100,               // Maximum items per page
    'search_debounce' => 300,            // Search delay in milliseconds
    'auto_discovery' => true,            // Enable automatic resource discovery
    'discovery_path' => 'app/Admin/Resources', // Discovery directory
],

'performance' => [
    'cache_resources' => true,           // Cache discovered resources
    'cache_ttl' => 3600,                // Cache time-to-live in seconds
],
```

## Best Practices

### Resource Organization

1. **Use meaningful names**: `UserResource`, `BlogPostResource`, not `Resource1`
2. **Group related resources**: Use the `$group` property for organization
3. **Keep fields focused**: Only include fields that are actually needed
4. **Use appropriate field types**: Choose the most specific field type available

### Performance

1. **Eager load relationships**: Use `indexQuery()` to prevent N+1 queries
2. **Limit searchable fields**: Only make frequently searched fields searchable
3. **Use appropriate pagination**: Set reasonable `per_page` values
4. **Enable caching**: Use resource caching for better performance

### Security

1. **Implement proper authorization**: Don't rely on default `true` returns
2. **Use policies**: Create Laravel policies for complex authorization logic
3. **Validate input**: Always add appropriate validation rules to fields
4. **Sanitize output**: Be careful with `asHtml()` and user-generated content

### User Experience

1. **Provide helpful labels**: Use clear, descriptive field names
2. **Add help text**: Use `help()` method to guide users
3. **Group related fields**: Organize fields logically
4. **Use appropriate field visibility**: Hide fields that aren't relevant in certain contexts
