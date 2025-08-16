# Field Documentation

JTD Admin Panel provides a comprehensive field system for building powerful admin interfaces. This documentation covers all available field types, their configuration options, and usage examples.

## Field Categories

### [Basic Input Fields](./basic-input-fields.md)
Essential form input fields for common data types:
- [Text Field](./basic-input-fields.md#text-field) - Basic text input with suggestions and validation
- [Email Field](./basic-input-fields.md#email-field) - Email input with built-in validation
- [Number Field](./basic-input-fields.md#number-field) - Numeric input with min/max/step controls
- [Password Field](./basic-input-fields.md#password-field) - Secure password input with masking
- [PasswordConfirmation Field](./basic-input-fields.md#password-confirmation-field) - Password confirmation field
- [Hidden Field](./basic-input-fields.md#hidden-field) - Hidden form inputs for IDs and tokens

### [Selection Fields](./selection-fields.md)
Fields for selecting from predefined options:
- [Select Field](./selection-fields.md#select-field) - Single selection dropdown with options
- [MultiSelect Field](./selection-fields.md#multiselect-field) - Multiple selection dropdown
- [Boolean Field](./selection-fields.md#boolean-field) - Checkbox/toggle with customizable labels

### [Text & Content Fields](./text-content-fields.md)
Fields for rich text and content management:
- [Textarea Field](./text-content-fields.md#textarea-field) - Multi-line text input
- [Code Field](./text-content-fields.md#code-field) - Syntax highlighting editor
- [Slug Field](./text-content-fields.md#slug-field) - URL-friendly slug generation

### [Date & Time Fields](./date-time-fields.md)
Fields for handling dates, times, and timezones:
- [Date Field](./date-time-fields.md#date-field) - Date picker with localization
- [DateTime Field](./date-time-fields.md#datetime-field) - Combined date and time picker
- [Timezone Field](./date-time-fields.md#timezone-field) - Timezone selection dropdown

### [File & Media Fields](./file-media-fields.md)
Fields for file uploads and media management:
- [File Field](./file-media-fields.md#file-field) - File upload with validation
- [Image Field](./file-media-fields.md#image-field) - Image upload with preview
- [Avatar Field](./file-media-fields.md#avatar-field) - User avatar extending Image field
- [Gravatar Field](./file-media-fields.md#gravatar-field) - Email-based avatar generation

### [Media Library Fields](./media-library-fields.md) ✨ NEW
Professional file and image management with Spatie Media Library integration:
- [MediaLibraryFile Field](./media-library-fields.md#medialibraryfile-field) - Advanced file management with collections and conversions
- [MediaLibraryImage Field](./media-library-fields.md#medialibraryimage-field) - Professional image management with responsive images and gallery
- [MediaLibraryAvatar Field](./media-library-fields.md#medialibraryavatar-field) - Specialized avatar management with cropping and fallbacks

### [Display & Formatting Fields](./display-formatting-fields.md)
Fields for enhanced display and formatting:
- [ID Field](./display-formatting-fields.md#id-field) - Primary key display with special features
- [Badge Field](./display-formatting-fields.md#badge-field) - Status badges with color mapping
- [Color Field](./display-formatting-fields.md#color-field) - HTML5 color picker
- [Currency Field](./display-formatting-fields.md#currency-field) - Multi-locale currency formatting
- [URL Field](./display-formatting-fields.md#url-field) - URL input with validation and display

### [Relationship Fields](./relationship-fields.md)
Fields for managing model relationships:
- [BelongsTo Field](./relationship-fields.md#belongsto-field) - Foreign key relationships
- [HasMany Field](./relationship-fields.md#hasmany-field) - One-to-many relationships

## Field Behavior Methods

All fields inherit powerful behavior methods from the base `Field` class:

### Display Control
- `showOnIndex()` / `hideFromIndex()` - Control index page visibility
- `showOnDetail()` / `hideFromDetail()` - Control detail page visibility
- `showOnCreating()` / `hideWhenCreating()` - Control creation form visibility
- `showOnUpdating()` / `hideWhenUpdating()` - Control update form visibility
- `onlyOnIndex()` / `onlyOnDetail()` / `onlyOnForms()` / `exceptOnForms()` - Exclusive visibility

### Field Behavior
- `required()` - Mark field as required with validation
- `readonly()` - Make field read-only (disables input)
- `immutable()` - Allow value submission but disable input editing
- `nullable()` - Allow null values in database
- `sortable()` - Enable sorting on index pages
- `searchable()` - Enable search functionality
- `filterable()` - Auto-generate filters for the field

### Layout & Presentation
- `textAlign()` - Control text alignment (left, center, right)
- `stacked()` - Stack field under label instead of beside
- `fullWidth()` - Make field take full container width
- `copyable()` - Add copy-to-clipboard functionality
- `asHtml()` - Render field content as HTML

### Text Field Enhancements
- `maxlength()` - Set maximum character length
- `enforceMaxlength()` - Client-side length limit enforcement
- `suggestions()` - Auto-complete suggestions array
- `placeholder()` - Set placeholder text
- `help()` - Add help text below field

## Quick Start

### Basic Field Definition

```php
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\Select;

// In your Resource's fields() method
public function fields(): array
{
    return [
        Text::make('Name')
            ->required()
            ->sortable()
            ->searchable(),
            
        Email::make('Email')
            ->required()
            ->rules('email', 'unique:users,email'),
            
        Select::make('Status')
            ->options([
                'active' => 'Active',
                'inactive' => 'Inactive',
                'pending' => 'Pending',
            ])
            ->displayUsingLabels()
            ->filterable(),
    ];
}
```

### Field with Custom Behavior

```php
use JTD\AdminPanel\Fields\Text;

Text::make('Title')
    ->required()
    ->maxlength(255)
    ->enforceMaxlength()
    ->suggestions(['Article', 'Tutorial', 'Guide'])
    ->help('Enter a descriptive title for your content')
    ->showOnIndex()
    ->showOnDetail()
    ->hideWhenCreating()
```

## Advanced Usage

### Custom Field Resolution

```php
Text::make('Full Name')
    ->resolveUsing(function ($value, $resource) {
        return $resource->first_name . ' ' . $resource->last_name;
    })
    ->displayUsing(function ($value) {
        return strtoupper($value);
    })
```

### Conditional Field Display

```php
Text::make('Admin Notes')
    ->showOnDetail(function ($request, $resource) {
        return $request->user()->isAdmin();
    })
    ->hideFromIndex()
```

### Field Validation

```php
Text::make('Username')
    ->required()
    ->rules('min:3', 'max:20', 'alpha_dash', 'unique:users,username')
    ->fillUsing(function ($request, $model, $attribute) {
        $model->{$attribute} = strtolower($request->input($attribute));
    })
```

## Nova Compatibility

JTD Admin Panel maintains 95%+ API compatibility with Laravel Nova fields, making migration straightforward. Most Nova field definitions will work without modification.

## Next Steps

- Explore specific field types in the categorized documentation
- Learn about [Resource](../resources.md) creation and management
- Understand [Field Validation](../validation.md) patterns
- Review [Custom Pages](../custom-pages.md) for advanced interfaces

---

For questions or contributions, please refer to our [Contributing Guide](../../CONTRIBUTING.md).
