# AdminPanel Examples

This directory contains comprehensive examples and reference implementations for the AdminPanel package. These examples demonstrate best practices, advanced features, and real-world usage patterns.

## 📋 Available Examples

### 🔢 AnalyticsCard
**Location:** [AnalyticsCard.md](AnalyticsCard.md) | [Usage Guide](AnalyticsCard-Usage-Guide.md)

A comprehensive analytics dashboard card that demonstrates:
- **Nova-compatible API** - 100% compatibility with Laravel Nova
- **Authorization patterns** - Role-based and custom authorization
- **Data visualization** - Charts, metrics, and interactive components
- **Real-time features** - Refresh, configure, and export capabilities
- **Responsive design** - Mobile-friendly layouts
- **Testing coverage** - Unit, integration, and E2E tests

**Perfect for:** Analytics dashboards, KPI monitoring, business intelligence

## 🚀 Quick Start

### Publishing Examples

Publish all examples to your application:

```bash
php artisan vendor:publish --tag=admin-panel-examples
```

This creates:
- `app/Admin/Cards/AnalyticsCard.php` - PHP card implementation
- `resources/js/admin-cards/AnalyticsCard.vue` - Vue component

### Using Package Examples

Use examples directly from the package:

```php
use JTD\AdminPanel\Cards\Examples\AnalyticsCard;
use JTD\AdminPanel\Support\AdminPanel;

AdminPanel::cards([
    AnalyticsCard::class,
]);
```

### Creating Custom Implementations

Generate new cards based on examples:

```bash
php artisan admin-panel:make-card MyCard --template=analytics
```

## 📚 Example Categories

### 🎯 Cards
- **AnalyticsCard** - Comprehensive analytics dashboard
- **WelcomeCard** - Simple welcome message card
- **EnhancedStatsCard** - Advanced statistics display

### 📊 Dashboards
- **Default Dashboard** - Basic dashboard layout
- **Executive Dashboard** - High-level metrics
- **Operations Dashboard** - Operational metrics

### 📄 Resources
- **User Resource** - User management example
- **Product Resource** - E-commerce product management
- **Order Resource** - Order processing workflow

### 🔧 Fields
- **Custom Field Examples** - Advanced field implementations
- **Validation Examples** - Complex validation patterns
- **Relationship Examples** - Model relationship handling

## 🎨 Customization Patterns

### Authorization
```php
// Role-based authorization
$card = AnalyticsCard::forRole('manager');

// Permission-based authorization
$card = AnalyticsCard::make()->canSee(function ($request) {
    return $request->user()->can('view-analytics');
});

// Custom logic authorization
$card = AnalyticsCard::make()->canSee(function ($request) {
    return $request->user()->department === 'marketing';
});
```

### Configuration
```php
// Date range filtering
$card = AnalyticsCard::withDateRange('2024-01-01', '2024-01-31');

// Metric selection
$card = AnalyticsCard::withMetrics(['users', 'revenue', 'conversion']);

// Custom meta data
$card = AnalyticsCard::make()->withMeta([
    'title' => 'Custom Analytics',
    'refreshInterval' => 60,
    'size' => 'xl',
]);
```

### Data Sources
```php
// Database integration
protected function getTotalUsers(): int
{
    return User::count();
}

// API integration
protected function getAnalyticsData(): array
{
    return Http::get('https://api.analytics.com/data')->json();
}

// Cache integration
protected function getCachedData(): array
{
    return Cache::remember('analytics-data', 900, function () {
        return $this->fetchExpensiveData();
    });
}
```

## 🧪 Testing Examples

### Unit Testing
```php
public function test_analytics_card_returns_correct_data()
{
    $card = new AnalyticsCard;
    $data = $card->data(request());
    
    $this->assertArrayHasKey('totalUsers', $data);
    $this->assertIsInt($data['totalUsers']);
}
```

### Feature Testing
```php
public function test_analytics_card_displays_on_dashboard()
{
    $this->actingAs($this->createAdminUser())
        ->get('/admin/dashboard')
        ->assertSee('Analytics Overview');
}
```

### E2E Testing
```javascript
test('analytics card displays metrics correctly', async ({ page }) => {
    await page.goto('/admin/dashboard');
    await expect(page.locator('[data-testid="analytics-card"]')).toBeVisible();
    await expect(page.locator('[data-testid="total-users-metric"]')).toContainText('Total Users');
});
```

## 🔧 Development Workflow

### 1. Study the Examples
- Read the documentation thoroughly
- Examine the source code
- Run the tests to understand behavior

### 2. Customize for Your Needs
- Modify data sources
- Adjust authorization logic
- Customize the UI components

### 3. Test Your Implementation
- Write unit tests for your logic
- Add feature tests for integration
- Include E2E tests for user workflows

### 4. Deploy and Monitor
- Use caching for performance
- Monitor for errors and performance issues
- Gather user feedback for improvements

## 📖 Documentation Structure

Each example includes:

### 📋 Overview Documentation
- **Purpose and use cases**
- **Key features demonstrated**
- **Integration patterns**
- **Customization options**

### 🚀 Usage Guide
- **Step-by-step setup**
- **Configuration options**
- **Real-world examples**
- **Troubleshooting tips**

### 🧪 Test Coverage
- **Unit tests** - Individual component testing
- **Integration tests** - System integration testing
- **E2E tests** - Full user workflow testing
- **Performance tests** - Load and stress testing

### 🎨 Customization Examples
- **Data source integration**
- **UI customization**
- **Authorization patterns**
- **Performance optimization**

## 🤝 Contributing Examples

### Adding New Examples

1. **Create the implementation** in the appropriate directory
2. **Write comprehensive tests** with 100% coverage
3. **Document thoroughly** with usage guides
4. **Add to the publishing system** in the service provider
5. **Update this README** with the new example

### Example Standards

- **100% Nova compatibility** for cards and resources
- **Comprehensive test coverage** (unit, integration, E2E)
- **Clear documentation** with usage examples
- **Real-world applicability** - solve actual problems
- **Performance considerations** - caching, optimization
- **Security best practices** - authorization, validation

### Code Quality

- **Follow PSR-12** coding standards
- **Use strict types** and proper type hints
- **Include PHPDoc** comments for all methods
- **Handle errors gracefully** with proper exceptions
- **Optimize for performance** with caching where appropriate

## 🔍 Finding Examples

### By Feature
- **Authorization** - AnalyticsCard (role-based, custom logic)
- **Data Visualization** - AnalyticsCard (charts, metrics)
- **Real-time Updates** - AnalyticsCard (refresh, WebSocket ready)
- **Export Functionality** - AnalyticsCard (data export)
- **Responsive Design** - AnalyticsCard (mobile-friendly)

### By Use Case
- **Business Intelligence** - AnalyticsCard
- **User Management** - UserResource
- **E-commerce** - ProductResource, OrderResource
- **Content Management** - PageResource, PostResource
- **System Administration** - SettingsPage, LogsPage

### By Complexity
- **Beginner** - WelcomeCard, basic resources
- **Intermediate** - AnalyticsCard, custom fields
- **Advanced** - Complex dashboards, custom pages

## 📞 Support

### Getting Help
- **Documentation** - Read the comprehensive guides
- **Examples** - Study the reference implementations
- **Tests** - Run and examine the test suites
- **Community** - Join the discussion forums

### Reporting Issues
- **Bug Reports** - Use the GitHub issue tracker
- **Feature Requests** - Propose new examples or improvements
- **Documentation** - Suggest improvements to guides

### Contributing
- **Pull Requests** - Submit improvements and new examples
- **Code Review** - Help review community contributions
- **Testing** - Help test new features and examples

## 📄 License

All examples are provided under the same license as the AdminPanel package. See the main package documentation for license details.

---

**Next Steps:**
1. Choose an example that matches your needs
2. Follow the usage guide for implementation
3. Customize for your specific requirements
4. Test thoroughly before deployment
5. Share your improvements with the community
