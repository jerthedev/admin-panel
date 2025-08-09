# Relationship Fields

Fields for managing model relationships in JTD Admin Panel.

## BelongsTo Field

The `BelongsTo` field manages foreign key relationships with enhanced display options, searchable functionality, and validation.

### Basic Usage

```php
use JTD\AdminPanel\Fields\BelongsTo;

BelongsTo::make('User')
```

### Features

- Foreign key relationship management
- Searchable dropdown for large datasets
- Custom display formatting
- Validation and authorization support
- Integration with Eloquent relationships

### Configuration Options

#### Resource Specification
Specify the related resource:

```php
BelongsTo::make('Category', 'category', CategoryResource::class)
```

#### Searchable Relationships
Enable search functionality for large datasets:

```php
BelongsTo::make('User')
    ->searchable()
```

#### Display Formatting
Customize how related models are displayed:

```php
BelongsTo::make('Author', 'user')
    ->displayUsing(function ($user) {
        return $user->name . ' (' . $user->email . ')';
    })
```

### Advanced Examples

```php
// Complete BelongsTo field
BelongsTo::make('Category')
    ->searchable()
    ->required()
    ->displayUsing(function ($category) {
        return $category->name . ' - ' . $category->description;
    })
    ->rules('required', 'exists:categories,id')
    ->help('Select the category for this item')

// User assignment
BelongsTo::make('Assigned To', 'assignee', UserResource::class)
    ->searchable()
    ->nullable()
    ->displayUsing(function ($user) {
        return $user ? $user->name . ' (' . $user->department . ')' : null;
    })
    ->help('Assign this task to a team member')

// Hierarchical relationships
BelongsTo::make('Parent Category', 'parent', CategoryResource::class)
    ->nullable()
    ->searchable()
    ->displayUsing(function ($category) {
        return $category ? $category->full_path : 'No Parent';
    })
```

### Model Setup

Ensure your Eloquent model has the proper relationship:

```php
// In your model
public function category()
{
    return $this->belongsTo(Category::class);
}

public function user()
{
    return $this->belongsTo(User::class);
}
```

---

## HasMany Field

The `HasMany` field manages one-to-many relationships with inline editing, creation capabilities, and advanced management features.

### Basic Usage

```php
use JTD\AdminPanel\Fields\HasMany;

HasMany::make('Comments')
```

### Features

- One-to-many relationship display and management
- Inline creation and editing capabilities
- Bulk operations support
- Pagination for large datasets
- Custom field configuration for related models

### Configuration Options

#### Related Resource
Specify the related resource class:

```php
HasMany::make('Posts', 'posts', PostResource::class)
```

#### Inline Creation
Enable inline creation of related models:

```php
HasMany::make('Comments')
    ->allowInlineCreation()
```

#### Field Customization
Customize fields shown for related models:

```php
HasMany::make('Order Items')
    ->fields([
        Text::make('Product Name'),
        Number::make('Quantity'),
        Currency::make('Price'),
    ])
```

### Advanced Examples

```php
// Complete HasMany field
HasMany::make('Order Items', 'items', OrderItemResource::class)
    ->allowInlineCreation()
    ->allowInlineEditing()
    ->fields([
        Select::make('Product')
            ->options(Product::pluck('name', 'id'))
            ->required(),
        Number::make('Quantity')
            ->min(1)
            ->required(),
        Currency::make('Unit Price')
            ->min(0)
            ->step(0.01),
    ])
    ->rules('required', 'array', 'min:1')
    ->help('Add items to this order')

// Blog post comments
HasMany::make('Comments')
    ->allowInlineCreation()
    ->fields([
        Text::make('Author Name')
            ->required(),
        Email::make('Author Email')
            ->required(),
        Textarea::make('Comment')
            ->rows(3)
            ->required(),
        Boolean::make('Approved')
            ->default(false),
    ])
    ->sortBy('created_at', 'desc')

// Project tasks
HasMany::make('Tasks', 'tasks', TaskResource::class)
    ->allowInlineCreation()
    ->fields([
        Text::make('Title')
            ->required(),
        Textarea::make('Description')
            ->rows(2),
        Select::make('Priority')
            ->options([
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
            ])
            ->default('medium'),
        Date::make('Due Date'),
    ])
```

### Model Setup

```php
// In your model
public function comments()
{
    return $this->hasMany(Comment::class);
}

public function items()
{
    return $this->hasMany(OrderItem::class, 'order_id');
}
```

---

## ManyToMany Field

The `ManyToMany` field manages many-to-many relationships with pivot table support, advanced selection interfaces, and bulk operations.

### Basic Usage

```php
use JTD\AdminPanel\Fields\ManyToMany;

ManyToMany::make('Tags')
```

### Features

- Many-to-many relationship management
- Pivot table data support
- Advanced selection interfaces
- Bulk attach/detach operations
- Custom pivot field configuration

### Configuration Options

#### Related Resource
Specify the related resource:

```php
ManyToMany::make('Roles', 'roles', RoleResource::class)
```

#### Pivot Fields
Include pivot table fields:

```php
ManyToMany::make('Projects')
    ->withPivot([
        Date::make('Started At'),
        Date::make('Completed At'),
        Text::make('Role'),
    ])
```

#### Selection Interface
Customize the selection interface:

```php
ManyToMany::make('Categories')
    ->searchable()
    ->displayUsing(function ($category) {
        return $category->name . ' (' . $category->posts_count . ' posts)';
    })
```

### Advanced Examples

```php
// Complete ManyToMany field with pivot data
ManyToMany::make('Team Members', 'users', UserResource::class)
    ->withPivot([
        Select::make('Role')
            ->options([
                'member' => 'Team Member',
                'lead' => 'Team Lead',
                'manager' => 'Manager',
            ])
            ->required(),
        Date::make('Joined At')
            ->default(now()),
        Boolean::make('Active')
            ->default(true),
    ])
    ->searchable()
    ->displayUsing(function ($user) {
        return $user->name . ' - ' . $user->email;
    })
    ->help('Assign team members with their roles')

// Product categories with metadata
ManyToMany::make('Categories')
    ->withPivot([
        Boolean::make('Featured')
            ->default(false),
        Number::make('Sort Order')
            ->default(0),
        Text::make('Custom Label')
            ->nullable(),
    ])
    ->searchable()
    ->rules('required', 'array', 'min:1')

// Course enrollments
ManyToMany::make('Students', 'enrolledStudents', UserResource::class)
    ->withPivot([
        Date::make('Enrolled At')
            ->default(now()),
        Select::make('Status')
            ->options([
                'active' => 'Active',
                'completed' => 'Completed',
                'dropped' => 'Dropped',
            ])
            ->default('active'),
        Number::make('Grade')
            ->min(0)
            ->max(100)
            ->nullable(),
    ])
    ->displayUsing(function ($student) {
        return $student->name . ' (' . $student->student_id . ')';
    })
```

### Model Setup

```php
// In your model
public function tags()
{
    return $this->belongsToMany(Tag::class);
}

public function users()
{
    return $this->belongsToMany(User::class, 'project_user')
        ->withPivot(['role', 'joined_at', 'active'])
        ->withTimestamps();
}

// Migration for pivot table
Schema::create('project_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('project_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('role')->default('member');
    $table->timestamp('joined_at')->nullable();
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

---

## Relationship Validation

### BelongsTo Validation
```php
BelongsTo::make('Category')
    ->rules([
        'required',
        'exists:categories,id',
        function ($attribute, $value, $fail) {
            if (!Category::find($value)->is_active) {
                $fail('Selected category is not active.');
            }
        }
    ])
```

### HasMany Validation
```php
HasMany::make('Items')
    ->rules([
        'required',
        'array',
        'min:1',
        'max:10'
    ])
    ->fields([
        Text::make('Name')
            ->rules('required', 'max:255'),
        Number::make('Quantity')
            ->rules('required', 'integer', 'min:1'),
    ])
```

### ManyToMany Validation
```php
ManyToMany::make('Tags')
    ->rules([
        'array',
        'max:5' // Maximum 5 tags
    ])
    ->withPivot([
        Number::make('Weight')
            ->rules('integer', 'between:1,10'),
    ])
```

---

## Performance Optimization

### Eager Loading
```php
// In your Resource
public static function indexQuery(NovaRequest $request, $query)
{
    return $query->with(['category', 'tags', 'comments']);
}
```

### Relationship Caching
```php
BelongsTo::make('Category')
    ->searchable()
    ->withCache(3600) // Cache for 1 hour
```

### Pagination
```php
HasMany::make('Comments')
    ->perPage(10) // Paginate large relationships
```

---

## Authorization

### Relationship Authorization
```php
BelongsTo::make('User')
    ->canSee(function ($request) {
        return $request->user()->can('view-users');
    })
    ->canAttach(function ($request, $model) {
        return $request->user()->can('assign-users');
    })
```

### Conditional Relationships
```php
HasMany::make('Private Notes')
    ->canSee(function ($request) {
        return $request->user()->isAdmin();
    })
```

---

## Field Combinations

Relationship fields work together for complex data models:

```php
public function fields(): array
{
    return [
        BelongsTo::make('Category')
            ->searchable()
            ->required(),
            
        BelongsTo::make('Author', 'user', UserResource::class)
            ->searchable()
            ->displayUsing(fn($user) => $user->name),
            
        ManyToMany::make('Tags')
            ->searchable(),
            
        HasMany::make('Comments')
            ->allowInlineCreation()
            ->fields([
                Text::make('Author'),
                Textarea::make('Content'),
            ]),
    ];
}
```

---

## Next Steps

- Review [Field Validation](../validation.md) for relationship validation
- Learn about [Resource](../resources.md) creation and management
- Explore [Custom Pages](../custom-pages.md) for advanced interfaces
- Understand [Authorization](../authorization.md) for relationship security
