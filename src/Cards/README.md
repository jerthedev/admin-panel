# Admin Panel Cards

This directory contains the Card base class and related implementations for the JTD Admin Panel package.

## Overview

The Card base class provides Nova-compatible functionality for creating custom dashboard cards with support for:

- **Meta Data**: Runtime configuration via `withMeta()`
- **Authorization**: Conditional display via `canSee()`
- **Naming**: Custom card names via `name()` and `withName()`
- **Components**: Vue component mapping via `component()`
- **Fluent Interface**: Method chaining for easy configuration

## Base Card Class

The `Card` abstract base class (`src/Cards/Card.php`) provides the foundation for all admin panel cards.

### Key Features

- **100% Nova API Compatibility**: Implements the same methods and patterns as Laravel Nova cards
- **Automatic Name Generation**: Converts class names to human-readable titles
- **URI Key Generation**: Creates kebab-case URI keys from class names
- **Component Name Generation**: Automatically generates Vue component names
- **JSON Serialization**: Ready for API responses and frontend consumption

### Core Methods

```php
// Factory method
Card::make()

// Meta data management
$card->withMeta(['key' => 'value'])
$card->meta()

// Authorization
$card->canSee(function (Request $request) {
    return $request->user()->isAdmin();
})
$card->authorize($request)

// Naming
$card->name()
$card->withName('Custom Name')

// Component mapping
$card->component()
$card->withComponent('CustomComponent')

// Serialization
$card->jsonSerialize()
```

## Usage Examples

### Basic Card

```php
use JTD\AdminPanel\Cards\Card;

class StatsCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Statistics Overview',
            'refreshInterval' => 30,
        ]);
    }
}

// Usage
$card = StatsCard::make();
```

### Card with Authorization

```php
class AdminCard extends Card
{
    public static function make(): static
    {
        return parent::make()->canSee(function (Request $request) {
            return $request->user()?->is_admin ?? false;
        });
    }
}
```

### Enhanced Card with Styling Options

```php
class EnhancedCard extends Card
{
    public function __construct()
    {
        parent::__construct();

        $this
            ->withTitle('Enhanced Card')
            ->withSubtitle('With advanced styling')
            ->withIcon('ChartBarIcon')
            ->withColor('primary')
            ->withVariant('elevated')
            ->refreshable()
            ->refreshEvery(30)
            ->withLabels(['status' => 'Active'])
            ->withStyles([
                'borderRadius' => '12px',
                'boxShadow' => '0 8px 25px rgba(0, 0, 0, 0.1)'
            ]);
    }
}
```

## Enhanced withMeta() System

The enhanced `withMeta()` system provides fluent methods for common card configurations:

### Color and Theming Methods

```php
// Theme colors
$card->withColor('primary')      // Blue theme
$card->withColor('success')      // Green theme
$card->withColor('danger')       // Red theme
$card->withColor('warning')      // Yellow theme

// Custom colors
$card->withBackgroundColor('#f0f0f0')
$card->withTextColor('#333333')
$card->withBorderColor('#e5e5e5')

// Hex colors and Tailwind classes supported
$card->withColor('#3B82F6')      // Hex color
$card->withColor('blue-500')     // Tailwind class
```

### Content Methods

```php
$card->withTitle('Card Title')
$card->withSubtitle('Card subtitle')
$card->withDescription('Detailed description')
$card->withIcon('ChartBarIcon')
```

### Styling Methods

```php
// Variants
$card->withVariant('default')    // Default styling
$card->withVariant('bordered')   // Bordered variant
$card->withVariant('elevated')   // Elevated with shadow
$card->withVariant('flat')       // Flat styling
$card->withVariant('gradient')   // Gradient background

// Sizes
$card->withSize('sm')           // Small
$card->withSize('md')           // Medium (default)
$card->withSize('lg')           // Large
$card->withSize('xl')           // Extra large
$card->withSize('full')         // Full width

// Custom styling
$card->withClasses(['custom-class', 'highlighted'])
$card->withStyles([
    'backgroundColor' => '#f8fafc',
    'borderRadius' => '8px',
    'padding' => '20px'
])
```

### Interactive Methods

```php
$card->refreshable()            // Enable refresh
$card->refreshEvery(30)         // Refresh every 30 seconds
$card->withLabels([
    'status' => 'Active',
    'priority' => 'High'
])
```

### Validation

All meta data is automatically validated:

- **Colors**: Hex colors, Tailwind classes, and theme colors
- **Refresh intervals**: 1-3600 seconds
- **Variants**: Valid variant names only
- **Sizes**: Valid size names only
- **Labels**: String key-value pairs only
- **Styles**: Valid CSS properties only
```

### Card with Complex Meta Data

```php
class ChartCard extends Card
{
    public function withChartData(array $data): static
    {
        return $this->withMeta([
            'chart' => [
                'type' => 'line',
                'data' => $data,
                'options' => [
                    'responsive' => true,
                    'maintainAspectRatio' => false,
                ],
            ],
        ]);
    }
}
```

## Testing

The Card implementation includes comprehensive test coverage:

### Unit Tests (`tests/Unit/Cards/CardTest.php`)
- Tests all public methods and their behavior
- Validates fluent interface chaining
- Ensures proper name/URI key generation
- Verifies meta data handling
- Tests authorization logic
- **100% code coverage**

### Integration Tests (`tests/Integration/Cards/CardIntegrationTest.php`)
- Tests Laravel Request integration
- Validates serialization with complex data
- Tests collection operations
- Verifies middleware-like authorization patterns

### E2E Tests (`tests/e2e/Cards/CardE2ETest.php`)
- Simulates real-world usage scenarios
- Tests complete card lifecycle
- Validates performance with large datasets
- Tests error handling and edge cases

## Running Tests

```bash
# Run all Card tests
vendor/bin/phpunit tests/Unit/Cards/ tests/Integration/Cards/ tests/e2e/Cards/

# Run with coverage
vendor/bin/phpunit tests/Unit/Cards/CardTest.php --coverage-text

# Generate HTML coverage report
vendor/bin/phpunit tests/Unit/Cards/CardTest.php --coverage-html coverage/cards
```

## Nova Compatibility

This implementation is designed to be 100% compatible with Laravel Nova's Card API:

- Same method signatures and return types
- Identical fluent interface patterns
- Compatible meta data handling
- Same authorization callback patterns
- JSON serialization matches Nova's format

## Architecture

```
src/Cards/
├── Card.php              # Abstract base class
├── Examples/             # Example implementations
│   └── WelcomeCard.php   # Welcome card example
└── README.md             # This documentation

tests/
├── Unit/Cards/           # Unit tests
├── Integration/Cards/    # Integration tests
└── e2e/Cards/           # End-to-end tests
```

## Best Practices

1. **Extend the Base Class**: Always extend `Card` for custom implementations
2. **Use Factory Methods**: Leverage `make()` for consistent instantiation
3. **Chain Methods**: Use fluent interface for readable configuration
4. **Test Thoroughly**: Follow the testing patterns for comprehensive coverage
5. **Document Meta Data**: Clearly document expected meta data structure
6. **Handle Authorization**: Always consider authorization requirements
7. **Follow Naming Conventions**: Use descriptive class names for auto-generation

## Contributing

When adding new card functionality:

1. Extend the `Card` base class
2. Add comprehensive tests (Unit, Integration, E2E)
3. Document the new functionality
4. Ensure 100% code coverage
5. Follow Laravel Pint formatting standards
6. Update this README if needed
