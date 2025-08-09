# Basic Input Fields

Essential form input fields for common data types in JTD Admin Panel.

## Text Field

The `Text` field provides a basic text input with support for suggestions, validation, and advanced behavior methods.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Text;

Text::make('Name')
```

### Configuration Options

#### Suggestions
Provide auto-complete suggestions for better user experience:

```php
Text::make('Category')
    ->suggestions(['Technology', 'Business', 'Health', 'Education'])
```

#### Maximum Length
Set character limits with optional client-side enforcement:

```php
Text::make('Title')
    ->maxlength(255)
    ->enforceMaxlength() // Enforces limit client-side
```

#### Password Mode
Display text input as password field:

```php
Text::make('Secret Key')
    ->asPassword()
```

### Advanced Examples

```php
// Complete text field with all options
Text::make('Article Title')
    ->required()
    ->maxlength(100)
    ->enforceMaxlength()
    ->suggestions(['How to', 'Guide to', 'Introduction to'])
    ->placeholder('Enter a compelling title')
    ->help('Keep it under 100 characters for SEO')
    ->sortable()
    ->searchable()
```

---

## Email Field

The `Email` field provides email input with built-in validation and formatting.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Email;

Email::make('Email Address')
```

### Features

- Automatic email format validation
- Searchable functionality for email-based filtering
- Proper email formatting and display optimization
- Integration with existing field validation system

### Examples

```php
// Basic email field
Email::make('Email')
    ->required()
    ->rules('email', 'unique:users,email')

// Email with custom validation
Email::make('Contact Email')
    ->nullable()
    ->rules('email')
    ->help('Optional contact email for notifications')
```

---

## Number Field

The `Number` field provides numeric input with precise controls and validation.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Number;

Number::make('Age')
```

### Configuration Options

#### Range Controls
Set minimum, maximum, and step values:

```php
Number::make('Price')
    ->min(0)
    ->max(9999.99)
    ->step(0.01)
```

#### Integer vs Decimal
```php
// Integer field
Number::make('Quantity')
    ->min(1)
    ->step(1)

// Decimal field
Number::make('Rating')
    ->min(0)
    ->max(5)
    ->step(0.1)
```

### Examples

```php
// Product price field
Number::make('Price')
    ->required()
    ->min(0)
    ->step(0.01)
    ->help('Enter price in USD')

// Age field with validation
Number::make('Age')
    ->min(18)
    ->max(120)
    ->rules('integer', 'between:18,120')
```

---

## Password Field

The `Password` field provides secure password input with automatic masking and hashing.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Password;

Password::make('Password')
```

### Features

- Automatic password masking for security
- Built-in password hashing integration
- Hidden from index and detail views by default
- Support for password confirmation workflows

### Configuration Options

```php
// Basic password field
Password::make('Password')
    ->required()
    ->rules('min:8', 'confirmed')

// Password with custom hashing
Password::make('Password')
    ->fillUsing(function ($request, $model, $attribute) {
        if ($request->filled($attribute)) {
            $model->{$attribute} = bcrypt($request->input($attribute));
        }
    })
```

### Security Best Practices

```php
Password::make('Password')
    ->required()
    ->rules([
        'min:8',
        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        'confirmed'
    ])
    ->help('Password must contain uppercase, lowercase, number, and special character')
    ->hideFromIndex()
    ->hideFromDetail()
```

---

## Password Confirmation Field

The `PasswordConfirmation` field provides password verification functionality.

### Basic Usage

```php
use JTD\AdminPanel\Fields\PasswordConfirmation;

PasswordConfirmation::make('Password Confirmation')
```

### Features

- Automatic password confirmation validation
- Seamless integration with Password field
- Enhanced security for password change workflows
- Built-in confirmation matching logic

### Complete Password Setup

```php
use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Fields\PasswordConfirmation;

// In your Resource's fields() method
public function fields(): array
{
    return [
        // Other fields...
        
        Password::make('Password')
            ->required()
            ->rules('min:8', 'confirmed')
            ->hideFromIndex()
            ->hideFromDetail(),
            
        PasswordConfirmation::make('Password Confirmation')
            ->required()
            ->hideFromIndex()
            ->hideFromDetail()
            ->onlyOnForms(),
    ];
}
```

---

## Hidden Field

The `Hidden` field provides hidden form inputs for IDs, tokens, and other data that shouldn't be visible to users.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Hidden;

Hidden::make('User ID', 'user_id')
```

### Common Use Cases

#### CSRF Protection
```php
Hidden::make('CSRF Token', '_token')
    ->default(csrf_token())
```

#### Foreign Keys
```php
Hidden::make('Category ID', 'category_id')
    ->default(function ($request) {
        return $request->get('category_id');
    })
```

#### Tracking Fields
```php
Hidden::make('Created By', 'created_by')
    ->default(function ($request) {
        return $request->user()->id;
    })
    ->fillUsing(function ($request, $model, $attribute) {
        if (!$model->exists) {
            $model->{$attribute} = $request->user()->id;
        }
    })
```

### Features

- Never displayed in any view (index, detail, forms)
- Automatically included in form submissions
- Support for dynamic default values
- Custom fill callbacks for complex logic

---

## Field Behavior Examples

All basic input fields support the full range of behavior methods:

### Visibility Control
```php
Text::make('Admin Notes')
    ->hideFromIndex()
    ->showOnDetail(function ($request, $resource) {
        return $request->user()->isAdmin();
    })
```

### Validation & Requirements
```php
Email::make('Email')
    ->required()
    ->rules('email', 'unique:users,email')
    ->nullable(false)
```

### Layout & Presentation
```php
Text::make('Description')
    ->stacked()
    ->fullWidth()
    ->textAlign('left')
    ->help('Provide a detailed description')
```

### Search & Filtering
```php
Text::make('Title')
    ->sortable()
    ->searchable()
    ->filterable()
```

---

## Next Steps

- Learn about [Selection Fields](./selection-fields.md) for dropdowns and options
- Explore [Text & Content Fields](./text-content-fields.md) for rich content
- Review [Field Validation](../validation.md) patterns
- Understand [Resource](../resources.md) integration
