# AnalyticsCard Example

The `AnalyticsCard` is a comprehensive example implementation that demonstrates all the features and capabilities of the AdminPanel card system. It serves as both a working example and a reference implementation for developers creating custom cards.

## Overview

The AnalyticsCard displays key performance metrics and analytics data in a visually appealing, interactive format. It showcases:

- **Nova-compatible API** - 100% compatibility with Laravel Nova's card system
- **Authorization** - Role-based and custom authorization logic
- **Meta data configuration** - Custom titles, descriptions, icons, and grouping
- **Real-time data** - Mock analytics data with proper structure
- **Interactive features** - Refresh, configure, and export capabilities
- **Responsive design** - Works on desktop, tablet, and mobile devices
- **Accessibility** - Screen reader friendly with proper ARIA labels

## Features Demonstrated

### 1. Basic Card Structure
```php
class AnalyticsCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        
        $this->withMeta([
            'title' => 'Analytics Overview',
            'description' => 'Key performance metrics and analytics data',
            'icon' => 'chart-bar',
            'color' => 'blue',
            'group' => 'Analytics',
            'refreshable' => true,
            'refreshInterval' => 30,
            'size' => 'lg',
        ]);
    }
}
```

### 2. Authorization Methods
```php
// Admin-only access
$card = AnalyticsCard::adminOnly();

// Role-based access
$card = AnalyticsCard::forRole('manager');

// Custom authorization
$card = AnalyticsCard::make()->canSee(function (Request $request) {
    return $request->user()->hasPermission('view-analytics');
});
```

### 3. Configuration Methods
```php
// Date range filtering
$card = AnalyticsCard::withDateRange('2024-01-01', '2024-01-31');

// Specific metrics
$card = AnalyticsCard::withMetrics(['users', 'pageviews', 'revenue']);
```

### 4. Data Structure
The card returns comprehensive analytics data:

```php
public function data(Request $request): array
{
    return [
        'totalUsers' => 15420,
        'activeUsers' => 12350,
        'pageViews' => 89750,
        'conversionRate' => 3.2,
        'revenue' => 45230.50,
        'topPages' => [
            ['path' => '/dashboard', 'views' => 12500, 'percentage' => 35.2],
            // ...
        ],
        'userGrowth' => [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'New Users',
                    'data' => [1200, 1900, 3000, 5000, 2000, 3000],
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                ],
                // ...
            ],
        ],
        'deviceBreakdown' => [
            ['device' => 'Desktop', 'users' => 8500, 'percentage' => 55.1],
            // ...
        ],
        'lastUpdated' => now()->toISOString(),
    ];
}
```

## Installation and Usage

### 1. Using the Package Example

The AnalyticsCard is included in the package examples:

```php
use JTD\AdminPanel\Cards\Examples\AnalyticsCard;

// In your AdminServiceProvider or dashboard
AdminPanel::cards([
    AnalyticsCard::class,
]);
```

### 2. Publishing the Example

Publish the example to your application:

```bash
php artisan vendor:publish --tag=admin-panel-examples
```

This will create:
- `app/Admin/Cards/AnalyticsCard.php` - The PHP card class
- `resources/js/admin-cards/AnalyticsCard.vue` - The Vue component

### 3. Creating Your Own

Use the make command to create a new card based on the AnalyticsCard:

```bash
php artisan admin-panel:make-card MyAnalyticsCard --template=analytics
```

## Customization

### Modifying Data Sources

Replace the mock data methods with real data sources:

```php
protected function getTotalUsers(): int
{
    return User::count();
}

protected function getActiveUsers(): int
{
    return User::where('last_login_at', '>=', now()->subDays(30))->count();
}

protected function getPageViews(): int
{
    return PageView::where('created_at', '>=', now()->subDays(30))->count();
}
```

### Adding New Metrics

Extend the data method to include additional metrics:

```php
public function data(Request $request): array
{
    return array_merge(parent::data($request), [
        'bounceRate' => $this->getBounceRate(),
        'sessionDuration' => $this->getAverageSessionDuration(),
        'topReferrers' => $this->getTopReferrers(),
    ]);
}
```

### Customizing the Vue Component

Modify the Vue component to display your custom data:

```vue
<template>
  <div class="analytics-card">
    <!-- Add your custom metrics -->
    <div class="custom-metric">
      <div class="text-2xl font-bold">{{ data.bounceRate }}%</div>
      <div class="text-sm text-gray-500">Bounce Rate</div>
    </div>
  </div>
</template>
```

## Testing

The AnalyticsCard includes comprehensive tests:

### Unit Tests
```bash
vendor/bin/phpunit tests/Unit/Cards/AnalyticsCardTest.php
```

### Integration Tests
```bash
vendor/bin/phpunit tests/Feature/AnalyticsCardIntegrationTest.php
```

### E2E Tests
```bash
vendor/bin/phpunit tests/Feature/AnalyticsCardE2ETest.php
npm run test:e2e tests/e2e/Cards/AnalyticsCard.spec.js
```

## API Reference

### Static Methods

| Method | Description | Example |
|--------|-------------|---------|
| `make()` | Create a new instance | `AnalyticsCard::make()` |
| `adminOnly()` | Admin-only access | `AnalyticsCard::adminOnly()` |
| `forRole($role)` | Role-based access | `AnalyticsCard::forRole('manager')` |
| `withDateRange($start, $end)` | Set date range | `AnalyticsCard::withDateRange('2024-01-01', '2024-01-31')` |
| `withMetrics($metrics)` | Set specific metrics | `AnalyticsCard::withMetrics(['users', 'revenue'])` |

### Instance Methods

| Method | Description | Return Type |
|--------|-------------|-------------|
| `data(Request $request)` | Get analytics data | `array` |
| `meta()` | Get card metadata | `array` |
| `authorize(Request $request)` | Check authorization | `bool` |
| `jsonSerialize()` | Serialize for API | `array` |

### Meta Data Properties

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| `title` | `string` | Card title | `'Analytics Overview'` |
| `description` | `string` | Card description | `'Key performance metrics...'` |
| `icon` | `string` | Icon name | `'chart-bar'` |
| `color` | `string` | Theme color | `'blue'` |
| `group` | `string` | Card group | `'Analytics'` |
| `refreshable` | `bool` | Can be refreshed | `true` |
| `refreshInterval` | `int` | Refresh interval (seconds) | `30` |
| `size` | `string` | Card size | `'lg'` |

## Best Practices

1. **Data Caching** - Cache expensive analytics queries
2. **Authorization** - Always implement proper authorization
3. **Error Handling** - Handle API failures gracefully
4. **Performance** - Optimize database queries
5. **Accessibility** - Include proper ARIA labels
6. **Testing** - Write comprehensive tests

## Troubleshooting

### Common Issues

**Card not appearing**
- Check authorization logic
- Verify card is registered in AdminServiceProvider
- Ensure auto-discovery is enabled

**Data not loading**
- Check data method implementation
- Verify database connections
- Review error logs

**Vue component not rendering**
- Ensure Vue component is compiled
- Check for JavaScript errors
- Verify component registration

### Debug Mode

Enable debug mode to see detailed information:

```php
$card = new AnalyticsCard;
$card->withMeta(['debug' => true]);
```

## Contributing

When contributing to the AnalyticsCard example:

1. Maintain 100% Nova compatibility
2. Include comprehensive tests
3. Update documentation
4. Follow coding standards
5. Add proper type hints

## License

This example is part of the AdminPanel package and follows the same license terms.
