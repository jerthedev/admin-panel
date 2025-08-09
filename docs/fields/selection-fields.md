# Selection Fields

Fields for selecting from predefined options in JTD Admin Panel.

## Select Field

The `Select` field provides a single selection dropdown with support for options, Enum integration, and searchable functionality.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Select;

Select::make('Status')
    ->options([
        'active' => 'Active',
        'inactive' => 'Inactive',
        'pending' => 'Pending',
    ])
```

### Configuration Options

#### Options Array
Define available options with key-value pairs:

```php
Select::make('Priority')
    ->options([
        'low' => 'Low Priority',
        'medium' => 'Medium Priority',
        'high' => 'High Priority',
        'urgent' => 'Urgent',
    ])
```

#### Enum Integration
Use PHP Enums for type-safe selections:

```php
// Assuming you have a Status enum
enum Status: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

Select::make('Status')
    ->enum(Status::class)
```

#### Searchable Dropdown
Enable search functionality for large option sets:

```php
Select::make('Country')
    ->options([
        'us' => 'United States',
        'ca' => 'Canada',
        'uk' => 'United Kingdom',
        // ... many more countries
    ])
    ->searchable()
```

#### Display Options
Control how options are displayed:

```php
Select::make('Category')
    ->options([
        'tech' => 'Technology',
        'business' => 'Business',
        'health' => 'Health',
    ])
    ->displayUsingLabels() // Shows labels instead of keys
```

### Advanced Examples

```php
// Complete select field with all features
Select::make('User Role')
    ->options([
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'author' => 'Author',
        'subscriber' => 'Subscriber',
    ])
    ->searchable()
    ->displayUsingLabels()
    ->required()
    ->filterable()
    ->help('Select the user\'s role in the system')

// Dynamic options based on context
Select::make('Department')
    ->options(function () {
        return Department::pluck('name', 'id')->toArray();
    })
    ->searchable()
```

---

## MultiSelect Field

The `MultiSelect` field provides multiple selection functionality with an intuitive tagging interface.

### Basic Usage

```php
use JTD\AdminPanel\Fields\MultiSelect;

MultiSelect::make('Tags')
    ->options([
        'php' => 'PHP',
        'laravel' => 'Laravel',
        'vue' => 'Vue.js',
        'javascript' => 'JavaScript',
    ])
```

### Features

- Advanced tagging interface for multiple selections
- Searchable dropdown with real-time filtering
- Intuitive tag-based selection management
- Support for large datasets with efficient rendering

### Configuration Options

#### Basic Multi-Selection
```php
MultiSelect::make('Skills')
    ->options([
        'php' => 'PHP Development',
        'js' => 'JavaScript',
        'python' => 'Python',
        'design' => 'UI/UX Design',
        'marketing' => 'Digital Marketing',
    ])
```

#### Searchable Multi-Select
```php
MultiSelect::make('Categories')
    ->options(Category::pluck('name', 'id')->toArray())
    ->searchable()
    ->help('Select one or more categories')
```

### Database Storage

MultiSelect fields typically store data as JSON arrays. Ensure your model casts the attribute appropriately:

```php
// In your Eloquent model
protected $casts = [
    'tags' => 'array',
    'skills' => 'array',
    'categories' => 'array',
];
```

### Advanced Examples

```php
// Multi-select with dynamic options
MultiSelect::make('Permissions')
    ->options(function () {
        return Permission::where('active', true)
            ->pluck('display_name', 'name')
            ->toArray();
    })
    ->searchable()
    ->help('Select user permissions')

// Multi-select with validation
MultiSelect::make('Required Skills')
    ->options([
        'communication' => 'Communication',
        'teamwork' => 'Teamwork',
        'leadership' => 'Leadership',
        'technical' => 'Technical Skills',
    ])
    ->rules('required', 'array', 'min:2')
    ->help('Select at least 2 required skills')
```

---

## Boolean Field

The `Boolean` field provides checkbox/toggle functionality with customizable labels and display modes.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Boolean;

Boolean::make('Active')
```

### Configuration Options

#### Custom True/False Values
Handle non-standard boolean representations:

```php
Boolean::make('Published')
    ->trueValue('yes')
    ->falseValue('no')
```

#### Custom Labels
Provide descriptive labels for better UX:

```php
Boolean::make('Email Notifications')
    ->help('Receive email notifications for important updates')
```

### Display Modes

The Boolean field supports different visual representations:

```php
// Standard checkbox (default)
Boolean::make('Active')

// With custom styling context
Boolean::make('Featured')
    ->help('Mark this item as featured')
```

### Advanced Examples

```php
// Boolean with conditional logic
Boolean::make('Send Welcome Email')
    ->default(true)
    ->help('Automatically send welcome email to new users')
    ->showOnCreating()
    ->hideFromIndex()

// Boolean with custom values
Boolean::make('Status')
    ->trueValue('enabled')
    ->falseValue('disabled')
    ->help('Enable or disable this feature')

// Boolean with validation
Boolean::make('Terms Accepted')
    ->required()
    ->rules('accepted')
    ->help('You must accept the terms and conditions')
    ->onlyOnForms()
```

### Database Considerations

Boolean fields work with various database column types:

```php
// Migration examples
Schema::table('users', function (Blueprint $table) {
    $table->boolean('active')->default(true);
    $table->tinyInteger('email_notifications')->default(1);
    $table->string('status')->default('enabled'); // For custom true/false values
});
```

---

## Field Combinations

Selection fields work well together for complex forms:

```php
public function fields(): array
{
    return [
        Select::make('Category')
            ->options(Category::pluck('name', 'id'))
            ->required()
            ->searchable(),
            
        MultiSelect::make('Tags')
            ->options(Tag::pluck('name', 'id'))
            ->searchable()
            ->help('Select relevant tags'),
            
        Boolean::make('Published')
            ->default(false)
            ->help('Make this content publicly visible'),
            
        Boolean::make('Featured')
            ->default(false)
            ->help('Feature this content on the homepage')
            ->showOnUpdating(), // Only show when editing
    ];
}
```

---

## Validation Examples

### Select Field Validation
```php
Select::make('Status')
    ->options(['draft', 'published', 'archived'])
    ->required()
    ->rules('in:draft,published,archived')
```

### MultiSelect Validation
```php
MultiSelect::make('Categories')
    ->options(Category::pluck('name', 'id'))
    ->rules('required', 'array', 'min:1', 'max:5')
    ->help('Select 1-5 categories')
```

### Boolean Validation
```php
Boolean::make('Terms Accepted')
    ->rules('accepted')
    ->required()
    ->help('Required to proceed')
```

---

## Filtering & Search

All selection fields support automatic filtering:

```php
Select::make('Status')
    ->options(['active', 'inactive', 'pending'])
    ->filterable() // Adds automatic filter

MultiSelect::make('Tags')
    ->options(Tag::pluck('name', 'id'))
    ->filterable() // Supports multi-value filtering

Boolean::make('Published')
    ->filterable() // True/False filter
```

---

## Next Steps

- Explore [Text & Content Fields](./text-content-fields.md) for rich content
- Learn about [Date & Time Fields](./date-time-fields.md) for temporal data
- Review [Field Validation](../validation.md) patterns
- Understand [Resource](../resources.md) integration
