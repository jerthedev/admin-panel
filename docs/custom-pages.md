# Custom Pages

**Target Audience:** Laravel developers who install `jerthedev/admin-panel` via Composer
**For Package Developers:** See [Custom Pages for Package Developers](custom-pages-for-package-developers.md)

The JTD Admin Panel package provides a powerful Custom Pages system that allows Laravel developers to create custom administrative interfaces beyond the standard CRUD operations provided by Resources. Custom Pages offer the flexibility to build dashboards, reports, forms, wizards, and other specialized interfaces while maintaining consistency with the admin panel's design and navigation.

## What Custom Pages Enable

Custom Pages are perfect for creating:

- **System Dashboards** - Overview pages with metrics, charts, and system status
- **Settings Pages** - Application configuration interfaces with complex forms
- **Report Pages** - Data visualization and export interfaces
- **Wizard Pages** - Multi-step guided processes and onboarding flows
- **Moderation Pages** - Content review and approval workflows
- **Analytics Pages** - Custom analytics and business intelligence interfaces

## Overview

Custom Pages extend the admin panel's capabilities by providing:

- **Field Integration**: Use the same field system as Resources for consistent UI
- **Menu Integration**: Automatic integration with the admin panel navigation
- **Authorization**: Built-in permission checking and access control
- **Vue Component System**: Flexible frontend component resolution with multi-component support
- **Route Generation**: Automatic route registration with consistent naming
- **Data Binding**: Custom data methods for complex page requirements
- **Multi-Component Architecture**: Support for complex pages with multiple Vue components

## Prerequisites

Before creating Custom Pages, ensure you have:

- **JTD Admin Panel installed** - See [Installation Guide](installation.md)
- **Admin authentication configured** - Users must be able to access the admin panel
- **Node.js and npm/yarn** - For compiling Vue components (if using custom components)

## Quick Start Guide

### Step 1: Set Up Custom Pages Environment

Run the setup command to prepare your application for Custom Pages:

```bash
php artisan admin-panel:setup-custom-pages
```

This command:
- Creates the `app/Admin/Pages/` directory
- Sets up Vite configuration for Custom Page components
- Creates the `resources/js/admin-pages/` directory for Vue components
- Provides example files to get you started

### Step 2: Create Your First Custom Page

Use the artisan command to generate a new Custom Page:

```bash
php artisan admin-panel:make-page SystemDashboard --group="System" --icon="server"
```

This creates:
- `app/Admin/Pages/SystemDashboardPage.php` - The page class
- `resources/js/admin-pages/SystemDashboard.vue` - The Vue component

### Step 3: Customize Your Page

Edit the generated page class to add your custom functionality:

## Basic Usage

### Creating a Custom Page Manually

You can also create a Custom Page manually by extending the base `Page` class:

```php
<?php

namespace App\Admin\Pages;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Pages\Page;

class SystemStatusPage extends Page
{
    /**
     * The Vue components for this page (multi-component support).
     * For single component pages, you can use just one component.
     */
    public static array $components = ['SystemStatus'];

    /**
     * The menu group this page belongs to.
     */
    public static ?string $group = 'System';

    /**
     * The display title for this page.
     */
    public static ?string $title = 'System Status';

    /**
     * The icon for this page (Heroicon name).
     */
    public static ?string $icon = 'server';

    /**
     * Get the fields for this page.
     */
    public function fields(Request $request): array
    {
        return [
            Text::make('Server Name')->readonly(),
            Number::make('CPU Usage')->suffix('%'),
            Number::make('Memory Usage')->suffix('%'),
        ];
    }

    /**
     * Get custom data for this page.
     */
    public function data(Request $request): array
    {
        return [
            'server_info' => [
                'name' => gethostname(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'system_metrics' => [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_usage' => $this->getDiskUsage(),
            ],
        ];
    }

    /**
     * Determine if the user can view this page.
     */
    public static function authorizedToViewAny(Request $request): bool
    {
        return $request->user()?->is_admin ?? false;
    }

    // Helper methods...
    private function getCpuUsage(): float { /* implementation */ }
    private function getMemoryUsage(): float { /* implementation */ }
    private function getDiskUsage(): float { /* implementation */ }
}
```

### Automatic Registration

Pages are automatically discovered and registered when placed in the `app/Admin/Pages` directory. No additional registration is required.

### Manual Registration

You can also manually register pages in your `AdminServiceProvider`:

```php
use JTD\AdminPanel\Support\AdminPanel;
use App\Admin\Pages\SystemStatusPage;

public function boot()
{
    AdminPanel::pages([
        SystemStatusPage::class,
    ]);
}
```

### Directory-based Registration

Register all pages from a specific directory:

```php
AdminPanel::pagesIn(app_path('Admin/CustomPages'));
```

## Page Class Architecture

### Required Properties

#### `$components` Array
The Vue components for this page. Supports both single and multi-component pages:

```php
// Single component page
public static array $components = ['SystemDashboard'];

// Multi-component page (wizard, tabbed interface, etc.)
public static array $components = [
    'OnboardingWizard',    // Primary component
    'WizardStep1',         // Additional routable component
    'WizardStep2',         // Additional routable component
];
```

**Component Resolution:**
- Components are resolved from `resources/js/admin-pages/` directory
- First component in array is the primary component
- Additional components are available via routing: `/admin/pages/pagename/component-name`

### Optional Properties

#### `$group` - Menu Organization
Groups related pages together in the navigation menu:

```php
public static ?string $group = 'Content Management';
public static ?string $group = 'System';
public static ?string $group = 'Reports';
```

#### `$title` - Display Name
Custom display title for the page:

```php
public static ?string $title = 'User Analytics Dashboard';
public static ?string $title = 'Content Moderation Queue';
```

**Default Behavior:** If not specified, title is generated from class name (e.g., `SystemDashboardPage` becomes "System Dashboard")

#### `$icon` - Menu Icon
Heroicon name for the menu item:

```php
public static ?string $icon = 'chart-bar';        // Analytics
public static ?string $icon = 'shield-check';     // Security/Moderation
public static ?string $icon = 'cog';              // Settings
public static ?string $icon = 'document-report';  // Reports
```

**Icon Reference:** Uses [Heroicons](https://heroicons.com/) outline icons

## Page Methods

### Required Methods

#### `fields(Request $request): array`

Define the fields that should be displayed on the page. Uses the same field system as Resources for consistency:

```php
public function fields(Request $request): array
{
    return [
        // Basic fields
        Text::make('Server Name')
            ->readonly()
            ->help('The hostname of the current server'),

        Number::make('CPU Usage')
            ->suffix('%')
            ->help('Current CPU utilization percentage'),

        // Interactive fields for settings pages
        Select::make('Log Level')->options([
            'debug' => 'Debug',
            'info' => 'Info',
            'warning' => 'Warning',
            'error' => 'Error',
        ])->help('Set the application log level'),

        Boolean::make('Maintenance Mode')
            ->help('Enable maintenance mode for the application'),

        // Complex fields
        Textarea::make('System Message')
            ->rows(4)
            ->placeholder('Enter a system-wide message for users'),

        // Conditional fields based on request
        ...$this->getConditionalFields($request),
    ];
}

private function getConditionalFields(Request $request): array
{
    $fields = [];

    if ($request->user()->hasRole('super-admin')) {
        $fields[] = Text::make('Debug Token')
            ->readonly()
            ->help('Debug token for advanced troubleshooting');
    }

    return $fields;
}
```

**Field Integration Benefits:**
- **Consistent UI**: Fields render with the same styling as Resources
- **Validation**: Built-in validation rules and error handling
- **Accessibility**: Automatic ARIA labels and keyboard navigation
- **Theming**: Automatic dark/light theme support

### Optional Methods

#### `data(Request $request): array`

Provide custom data to the Vue component:

```php
public function data(Request $request): array
{
    return [
        'statistics' => $this->getStatistics(),
        'recent_activity' => $this->getRecentActivity(),
    ];
}
```

#### `actions(Request $request): array`

Define actions available on the page:

```php
public function actions(Request $request): array
{
    return [
        new RefreshDataAction(),
        new ExportReportAction(),
    ];
}
```

#### `metrics(Request $request): array`

Define metrics to display on the page:

```php
public function metrics(Request $request): array
{
    return [
        new TotalUsersMetric(),
        new ActiveSessionsMetric(),
    ];
}
```

### Authorization Methods

#### `authorizedToViewAny(Request $request): bool`

Control who can access the page:

```php
public static function authorizedToViewAny(Request $request): bool
{
    return $request->user()?->hasRole('admin');
}
```

## Vue Component Development

### Component Creation and Structure

Custom Page Vue components are created in the `resources/js/admin-pages/` directory and automatically integrated with the admin panel's build system.

#### Basic Component Structure

Create a Vue component that matches your page's component name:

```vue
<!-- resources/js/admin-pages/SystemDashboard.vue -->
<template>
    <div class="custom-page">
        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">
                {{ page.title }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Monitor your system's performance and health
            </p>
        </div>

        <!-- Fields Section -->
        <div v-if="fields.length > 0" class="mb-8">
            <h2 class="text-lg font-medium mb-4">System Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="field in fields"
                    :key="field.attribute"
                    class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow"
                >
                    <component
                        :is="field.component"
                        :field="field"
                        :value="field.value"
                        :readonly="true"
                    />
                </div>
            </div>
        </div>

        <!-- Custom Data Section -->
        <div v-if="data.metrics" class="mb-8">
            <h2 class="text-lg font-medium mb-4">Performance Metrics</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div
                    v-for="metric in data.metrics"
                    :key="metric.name"
                    class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center"
                >
                    <div class="text-2xl font-bold text-blue-600">{{ metric.value }}</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">{{ metric.label }}</div>
                </div>
            </div>
        </div>

        <!-- Actions Section -->
        <div v-if="actions.length > 0" class="flex space-x-4">
            <button
                v-for="action in actions"
                :key="action.name"
                @click="executeAction(action)"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
                {{ action.name }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    page: {
        type: Object,
        required: true
    },
    fields: {
        type: Array,
        default: () => []
    },
    actions: {
        type: Array,
        default: () => []
    },
    metrics: {
        type: Array,
        default: () => []
    },
    data: {
        type: Object,
        default: () => ({})
    }
})

const executeAction = (action) => {
    // Handle action execution
    console.log('Executing action:', action.name)
}
</script>
```

#### Component Props

All Custom Page components receive these props automatically:

| Prop | Type | Description |
|------|------|-------------|
| `page` | Object | Page metadata (title, group, icon, etc.) |
| `fields` | Array | Field definitions from `fields()` method |
| `actions` | Array | Action definitions from `actions()` method |
| `metrics` | Array | Metric definitions from `metrics()` method |
| `data` | Object | Custom data from `data()` method |

### Component Location and Naming

#### File Location
Place Custom Page components in:
```
resources/js/admin-pages/YourComponent.vue
```

#### Naming Conventions
- **Component files**: Use PascalCase (e.g., `SystemDashboard.vue`)
- **Component references**: Match the file name exactly in `$components` array
- **Subdirectories**: Supported for organization (e.g., `Reports/SalesReport.vue`)

#### Component Resolution Examples

```php
// Single component
public static array $components = ['SystemDashboard'];
// Resolves to: resources/js/admin-pages/SystemDashboard.vue

// Component in subdirectory
public static array $components = ['Reports/SalesReport'];
// Resolves to: resources/js/admin-pages/Reports/SalesReport.vue

// Multi-component page
public static array $components = [
    'OnboardingWizard',     // Primary: resources/js/admin-pages/OnboardingWizard.vue
    'WizardStep1',          // Additional: resources/js/admin-pages/WizardStep1.vue
    'WizardStep2',          // Additional: resources/js/admin-pages/WizardStep2.vue
];
```

```vue
<template>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">{{ page.title }}</h1>
        </div>

        <!-- Render fields -->
        <div v-if="fields.length > 0" class="space-y-4">
            <component
                v-for="field in fields"
                :key="field.attribute"
                :is="field.component"
                :field="field"
                :value="field.value"
            />
        </div>

        <!-- Custom content using data -->
        <div v-if="data.statistics" class="mt-8">
            <h2 class="text-lg font-medium mb-4">Statistics</h2>
            <!-- Your custom content here -->
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    page: Object,
    fields: Array,
    actions: Array,
    metrics: Array,
    data: Object,
})
</script>
```

### Multi-Component Architecture

Custom Pages support multiple Vue components for complex interfaces like wizards, tabbed pages, or multi-step workflows.

#### Basic Multi-Component Setup

```php
class OnboardingWizardPage extends Page
{
    public static array $components = [
        'OnboardingWizard',    // Primary component (loads by default)
        'WizardStep1',         // Accessible via routing
        'WizardStep2',         // Accessible via routing
        'WizardStep3',         // Accessible via routing
    ];

    public static string $group = 'Setup';
    public static string $title = 'Onboarding Wizard';

    public function fields(Request $request): array
    {
        return [
            Text::make('Company Name')->rules('required'),
            Select::make('Industry')->options($this->getIndustryOptions()),
            Boolean::make('Enable Notifications'),
        ];
    }

    public function data(Request $request): array
    {
        return [
            'currentStep' => $request->get('step', 1),
            'totalSteps' => 3,
            'wizardData' => session('wizard_data', []),
        ];
    }
}
```

#### Multi-Component Routing

When a page has multiple components, additional routes are automatically generated:

| Route | Component Loaded | URL |
|-------|------------------|-----|
| Primary | `OnboardingWizard` | `/admin/pages/onboardingwizard` |
| Step 1 | `WizardStep1` | `/admin/pages/onboardingwizard/WizardStep1` |
| Step 2 | `WizardStep2` | `/admin/pages/onboardingwizard/WizardStep2` |
| Step 3 | `WizardStep3` | `/admin/pages/onboardingwizard/WizardStep3` |

#### Component Communication

All components in a multi-component page share the same:
- **Fields**: Access to `fields()` method output
- **Data**: Access to `data()` method output
- **Actions**: Access to `actions()` method output
- **Page Context**: Same page metadata and permissions

```vue
<!-- OnboardingWizard.vue - Primary Component -->
<template>
    <div class="wizard-container">
        <div class="wizard-progress">
            <div class="step" :class="{ active: data.currentStep === 1 }">
                <router-link :to="{ name: 'admin-panel.pages.onboardingwizard.component', params: { component: 'WizardStep1' } }">
                    Step 1: Company Info
                </router-link>
            </div>
            <div class="step" :class="{ active: data.currentStep === 2 }">
                <router-link :to="{ name: 'admin-panel.pages.onboardingwizard.component', params: { component: 'WizardStep2' } }">
                    Step 2: Configuration
                </router-link>
            </div>
        </div>

        <!-- Current step content -->
        <router-view :fields="fields" :data="data" :actions="actions" />
    </div>
</template>
```

## Routing

### Automatic Route Generation

Routes are automatically generated for all registered pages:

- **Route Name**: `admin-panel.pages.{pagename}`
- **URI Path**: `admin/pages/{pagename}`
- **Controller**: `PageController@show`

### Route Examples

| Page Class | Route Name | URI Path |
|------------|------------|----------|
| `DashboardPage` | `admin-panel.pages.dashboard` | `admin/pages/dashboard` |
| `SystemStatusPage` | `admin-panel.pages.systemstatus` | `admin/pages/systemstatus` |
| `UserReportPage` | `admin-panel.pages.userreport` | `admin/pages/userreport` |

## Menu Integration

### Automatic Menu Items

Pages automatically appear in the admin panel navigation based on:

1. **Authorization**: Only if `authorizedToViewAny()` returns `true`
2. **Grouping**: Organized by the `$group` property
3. **Ordering**: Alphabetical within each group

### Menu Customization

```php
public function menu(Request $request): MenuItem
{
    return MenuItem::make($this->label(), route($this->routeName()))
        ->withIcon($this->icon())
        ->withBadge($this->getBadgeCount($request));
}
```

## Configuration

### Auto-Discovery

Enable or disable automatic page discovery:

```php
// config/admin-panel.php
'pages' => [
    'auto_discovery' => true,
    'discovery_path' => 'app/Admin/Pages',
],
```

### Performance

Configure caching for page discovery:

```php
'performance' => [
    'cache_pages' => true,
    'cache_ttl' => 3600, // 1 hour
],
```

## Artisan Commands for Custom Pages

### Creating Custom Pages

#### Basic Page Creation
```bash
php artisan admin-panel:make-page SystemDashboard --group="System" --icon="server"
```

#### Multi-Component Page Creation
```bash
php artisan admin-panel:make-page OnboardingWizard \
    --components="OnboardingWizard,Step1,Step2,Step3" \
    --group="Setup" \
    --icon="academic-cap"
```

#### Setup Development Environment
```bash
php artisan admin-panel:setup-custom-pages
```

For complete command reference, see [Custom Pages Artisan Commands](artisan-commands/custom-pages.md).

## Advanced Features

### Custom Authorization

Implement complex authorization logic:

```php
public static function authorizedToViewAny(Request $request): bool
{
    $user = $request->user();
    
    return $user && (
        $user->hasRole('admin') || 
        $user->hasPermission('view-system-status')
    );
}
```

### Dynamic Fields

Create fields based on request context:

```php
public function fields(Request $request): array
{
    $fields = [
        Text::make('Name'),
    ];

    if ($request->user()->hasRole('admin')) {
        $fields[] = Text::make('Admin Notes');
    }

    return $fields;
}
```

### Complex Data Binding

Provide rich data to your Vue components:

```php
public function data(Request $request): array
{
    return [
        'charts' => [
            'user_growth' => $this->getUserGrowthData(),
            'revenue' => $this->getRevenueData(),
        ],
        'tables' => [
            'recent_users' => User::latest()->take(10)->get(),
            'top_products' => Product::orderBy('sales', 'desc')->take(5)->get(),
        ],
        'config' => [
            'currency' => config('app.currency'),
            'timezone' => config('app.timezone'),
        ],
    ];
}
```

## Error Handling

### Missing Components

If a Vue component is not found, the system displays a helpful error message with:
- Component name that was requested
- Suggestion to create the missing component
- Fallback UI to prevent application crashes

### Invalid Page Classes

The system validates page classes and throws descriptive errors for:
- Missing `$component` property
- Abstract page classes
- Classes that don't extend `Page`

## Best Practices

### 1. Naming Conventions

- **Page Classes**: Use descriptive names ending with `Page` (e.g., `UserReportPage`)
- **Components**: Match the `$component` property exactly
- **Groups**: Use consistent group names across related pages

### 2. Authorization

- Always implement `authorizedToViewAny()` for security
- Use specific permissions rather than broad role checks
- Consider different authorization levels for different page sections

### 3. Performance

- Keep `data()` methods efficient - they run on every page load
- Use caching for expensive operations
- Limit the amount of data passed to Vue components

### 4. Testing

- Write unit tests for page classes
- Test authorization logic thoroughly
- Verify Vue component rendering

## Integration with Resources

Custom Pages work alongside Resources in the same admin panel:

- **Shared Navigation**: Pages and Resources appear in the same menu
- **Shared Fields**: Use the same field components and validation
- **Shared Authorization**: Consistent permission patterns
- **Shared Styling**: Automatic Tailwind CSS styling

## Migration from Nova

Custom Pages provide functionality beyond Laravel Nova's capabilities:

| Feature | Nova | JTD Admin Panel |
|---------|------|-----------------|
| Custom Pages | ❌ | ✅ |
| Field Integration | ✅ | ✅ |
| Menu Integration | ✅ | ✅ |
| Authorization | ✅ | ✅ |
| Vue Components | ✅ | ✅ |

This makes JTD Admin Panel a powerful alternative that extends beyond traditional CRUD operations.

## Complete Examples

The JTD Admin Panel includes comprehensive examples demonstrating different Custom Pages patterns:

### 1. System Dashboard Page
**Location:** [examples/custom-pages/dashboard-page/](examples/custom-pages/dashboard-page/)

**Demonstrates:**
- Single-component Custom Page
- Real-time system metrics display
- Field integration for data display
- Actions for system management
- Responsive dashboard layout

**Use Case:** System monitoring and administration interface

### 2. Multi-Component Wizard
**Location:** [examples/custom-pages/wizard-page/](examples/custom-pages/wizard-page/)

**Demonstrates:**
- Multi-component architecture
- Step-by-step navigation
- Shared state across components
- Form data persistence
- Progress tracking

**Use Case:** User onboarding and setup processes

### 3. Settings Management Page
**Location:** [examples/custom-pages/settings-page/](examples/custom-pages/settings-page/)

**Demonstrates:**
- Form handling and validation
- Configuration management
- Settings persistence
- Conditional field display
- Success/error feedback

**Use Case:** Application configuration interfaces

### 4. Report Generation Page
**Location:** [examples/custom-pages/report-page/](examples/custom-pages/report-page/)

**Demonstrates:**
- Data visualization
- Export functionality
- Filter integration
- Chart and graph display
- PDF generation

**Use Case:** Analytics and reporting interfaces

## Testing Custom Pages

### Development Testing

1. **Create a test page**:
   ```bash
   php artisan admin-panel:make-page TestDashboard --group="Testing"
   ```

2. **Customize the page** with your specific requirements

3. **Build assets**:
   ```bash
   npm run build
   ```

4. **Access the page** at `/admin/pages/testdashboard`

### Automated Testing

#### Unit Testing Page Classes
```php
<?php

namespace Tests\Unit\Admin\Pages;

use Tests\TestCase;
use App\Admin\Pages\SystemDashboardPage;
use Illuminate\Http\Request;

class SystemDashboardPageTest extends TestCase
{
    public function test_page_has_required_components()
    {
        $this->assertNotEmpty(SystemDashboardPage::$components);
        $this->assertContains('SystemDashboard', SystemDashboardPage::$components);
    }

    public function test_fields_are_defined()
    {
        $page = new SystemDashboardPage();
        $request = Request::create('/');

        $fields = $page->fields($request);

        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
    }

    public function test_authorization_works()
    {
        $user = \App\Models\User::factory()->admin()->create();
        $request = Request::create('/');
        $request->setUserResolver(fn() => $user);

        $this->assertTrue(SystemDashboardPage::authorizedToViewAny($request));
    }
}
```

#### Integration Testing
```php
public function test_custom_page_loads_in_admin_panel()
{
    $this->actingAs(\App\Models\User::factory()->admin()->create());

    $response = $this->get('/admin/pages/systemdashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) =>
        $page->component('SystemDashboard')
             ->has('fields')
             ->has('data')
    );
}
```

### Performance Testing

Monitor Custom Pages performance:
- Page load times
- Component resolution speed
- Data fetching efficiency
- Memory usage during data() method execution
