# Display & Formatting Fields

Fields for enhanced display and formatting in JTD Admin Panel.

## ID Field

The `ID` field provides primary key display with specialized functionality including sortable support and copyable features.

### Basic Usage

```php
use JTD\AdminPanel\Fields\ID;

ID::make()
```

### Features

- Automatic primary key detection with fallback to 'id' attribute
- Built-in sortable() support for primary key sorting
- copyable() method for copying ID values to clipboard
- Hidden from creation forms by default (readonly on create)
- Optimized display with smaller, muted text styling
- Nova-compatible API with enhanced functionality

### Configuration Options

#### Custom Attribute
Specify a different attribute for the ID field:

```php
ID::make('User ID', 'user_id')
    ->sortable()
```

#### Copyable Functionality
Enable copy-to-clipboard for easy ID sharing:

```php
ID::make()
    ->sortable()
    ->copyable()
```

### Advanced Examples

```php
// Standard ID field
ID::make()
    ->sortable()
    ->copyable()

// Custom ID field
ID::make('Reference Number', 'ref_number')
    ->sortable()
    ->copyable()
    ->help('Unique reference number for this record')

// UUID field
ID::make('UUID', 'uuid')
    ->copyable()
    ->help('Universally unique identifier')
```

---

## Badge Field

The `Badge` field displays status information with color mapping, icons, and customizable styles.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Badge;

Badge::make('Status')
```

### Configuration Options

#### Color Mapping
Map field values to badge colors:

```php
Badge::make('Status')
    ->map([
        'active' => 'success',
        'inactive' => 'danger',
        'pending' => 'warning',
        'draft' => 'info',
    ])
```

#### Custom Styles
Define custom CSS classes for badge types:

```php
Badge::make('Priority')
    ->types([
        'high' => 'bg-red-100 text-red-800',
        'medium' => 'bg-yellow-100 text-yellow-800',
        'low' => 'bg-green-100 text-green-800',
    ])
```

#### Icons
Add icons to badges for better visual communication:

```php
Badge::make('Status')
    ->map([
        'published' => 'success',
        'draft' => 'warning',
    ])
    ->withIcons()
    ->icons([
        'success' => 'check-circle',
        'warning' => 'clock',
    ])
```

### Advanced Examples

```php
// Complete badge field with all features
Badge::make('Order Status')
    ->map([
        'pending' => 'warning',
        'processing' => 'info',
        'shipped' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger',
    ])
    ->withIcons()
    ->icons([
        'warning' => 'clock',
        'info' => 'cog',
        'primary' => 'truck',
        'success' => 'check-circle',
        'danger' => 'x-circle',
    ])
    ->labels([
        'pending' => 'Pending Payment',
        'processing' => 'Processing Order',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
    ])

// User role badge
Badge::make('Role')
    ->map([
        'admin' => 'danger',
        'editor' => 'warning',
        'author' => 'info',
        'subscriber' => 'success',
    ])
    ->withIcons()
```

---

## Color Field

The `Color` field provides an HTML5 color picker with support for multiple color formats and alpha channels.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Color;

Color::make('Brand Color')
```

### Features

- HTML5 color picker interface
- Support for hex, RGB, HSL formats
- Alpha channel support for transparency
- Color palette integration
- Real-time color preview

### Configuration Options

#### Default Color
Set a default color value:

```php
Color::make('Theme Color')
    ->default('#3B82F6') // Blue default
```

#### Color Format
Specify the preferred color format:

```php
Color::make('Background Color')
    ->format('hex') // hex, rgb, hsl
```

### Advanced Examples

```php
// Brand color picker
Color::make('Primary Color')
    ->default('#1F2937')
    ->rules('required', 'regex:/^#[0-9A-Fa-f]{6}$/')
    ->help('Choose your brand primary color')

// Theme customization
Color::make('Accent Color')
    ->default('#10B981')
    ->nullable()
    ->help('Optional accent color for highlights')

// UI color scheme
Color::make('Button Color')
    ->default('#3B82F6')
    ->rules('required')
    ->help('Color for primary buttons and links')
```

---

## Currency Field

The `Currency` field provides multi-locale currency formatting with symbol positioning, precision control, and validation.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Currency;

Currency::make('Price')
```

### Configuration Options

#### Currency Type
Specify the currency:

```php
Currency::make('Price')
    ->currency('USD') // Default from config

Currency::make('Euro Price')
    ->currency('EUR')
```

#### Locale Formatting
Control locale-specific formatting:

```php
Currency::make('Price')
    ->locale('en_US') // Default from app.locale
```

#### Precision and Range
Set decimal precision and value ranges:

```php
Currency::make('Price')
    ->min(0)
    ->max(99999.99)
    ->step(0.01)
```

### Advanced Examples

```php
// Product price field
Currency::make('Price')
    ->currency('USD')
    ->min(0)
    ->step(0.01)
    ->required()
    ->rules('numeric', 'min:0', 'max:99999.99')
    ->help('Enter price in US dollars')

// Multi-currency support
Currency::make('Base Price')
    ->currency('USD')
    ->min(0)
    ->step(0.01),

Currency::make('Euro Price')
    ->currency('EUR')
    ->min(0)
    ->step(0.01),

// Subscription pricing
Currency::make('Monthly Fee')
    ->currency('USD')
    ->min(0.99)
    ->step(0.01)
    ->rules('required', 'numeric', 'min:0.99')
    ->help('Minimum $0.99 monthly subscription')
```

### Currency Configuration

Configure supported currencies in your application:

```php
// config/admin-panel.php
'currency' => 'USD',
'supported_currencies' => [
    'USD' => 'US Dollar',
    'EUR' => 'Euro',
    'GBP' => 'British Pound',
    'CAD' => 'Canadian Dollar',
],
```

---

## URL Field

The `URL` field provides URL input with validation, clickable display, favicon support, and protocol handling.

### Basic Usage

```php
use JTD\AdminPanel\Fields\URL;

URL::make('Website')
```

### Features

- URL validation and formatting
- Clickable display links
- Favicon integration for visual enhancement
- Protocol handling (http/https)
- Link preview functionality

### Configuration Options

#### Display Text
Customize the link display text:

```php
URL::make('Website')
    ->displayUsing(function ($value) {
        return parse_url($value, PHP_URL_HOST);
    })
```

#### Link Behavior
Control how links open:

```php
URL::make('External Link')
    ->openInNewTab() // Opens in new tab/window
```

#### Favicon Display
Show favicons next to URLs:

```php
URL::make('Website')
    ->withFavicon() // Display site favicon
```

### Advanced Examples

```php
// Complete URL field
URL::make('Company Website')
    ->rules('required', 'url', 'active_url')
    ->withFavicon()
    ->openInNewTab()
    ->displayUsing(function ($value) {
        return parse_url($value, PHP_URL_HOST);
    })
    ->help('Enter the full website URL including http:// or https://')

// Social media links
URL::make('Twitter Profile')
    ->rules('url', 'regex:/^https:\/\/(www\.)?twitter\.com\//')
    ->placeholder('https://twitter.com/username')
    ->help('Your Twitter profile URL'),

URL::make('LinkedIn Profile')
    ->rules('url', 'regex:/^https:\/\/(www\.)?linkedin\.com\//')
    ->placeholder('https://linkedin.com/in/username')
    ->help('Your LinkedIn profile URL')

// API endpoint
URL::make('API Endpoint')
    ->rules('required', 'url')
    ->placeholder('https://api.example.com/v1')
    ->help('Base URL for API integration')
```

### URL Validation

```php
URL::make('Website')
    ->rules([
        'required',
        'url',
        'active_url', // Validates that URL is reachable
        'regex:/^https?:\/\//', // Ensure http or https protocol
    ])
```

---

## Field Combinations

Display and formatting fields work well together for comprehensive data presentation:

```php
public function fields(): array
{
    return [
        ID::make()
            ->sortable()
            ->copyable(),
            
        Badge::make('Status')
            ->map([
                'active' => 'success',
                'inactive' => 'danger',
            ])
            ->withIcons(),
            
        Currency::make('Price')
            ->currency('USD')
            ->min(0)
            ->step(0.01),
            
        Color::make('Brand Color')
            ->default('#3B82F6'),
            
        URL::make('Website')
            ->withFavicon()
            ->openInNewTab(),
    ];
}
```

---

## Styling and Theming

### Custom Badge Styles
```php
Badge::make('Priority')
    ->addTypes([
        'critical' => 'bg-red-600 text-white font-bold',
        'urgent' => 'bg-orange-500 text-white',
        'normal' => 'bg-blue-100 text-blue-800',
    ])
```

### Color Scheme Integration
```php
Color::make('Theme Color')
    ->default(function () {
        return auth()->user()->preferences['theme_color'] ?? '#3B82F6';
    })
```

### Currency Localization
```php
Currency::make('Price')
    ->locale(function () {
        return auth()->user()->locale ?? config('app.locale');
    })
```

---

## Next Steps

- Explore [Relationship Fields](./relationship-fields.md) for model relationships
- Learn about [Field Validation](../validation.md) patterns
- Review [Resource](../resources.md) integration
- Understand [Custom Pages](../custom-pages.md) for advanced interfaces
