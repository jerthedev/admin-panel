# Custom Pages API Reference

Complete API reference for the JTD Admin Panel Custom Pages system.

## Page Base Class

### Properties

#### `$components` (array, required)
Array of Vue component names for this page. Supports single and multi-component pages.

```php
// Single component
public static array $components = ['SystemDashboard'];

// Multi-component page
public static array $components = [
    'OnboardingWizard',    // Primary component
    'WizardStep1',         // Additional component
    'WizardStep2',         // Additional component
];
```

**Component Resolution:**
- Components resolve from `resources/js/admin-pages/` directory
- First component is primary (loads at base route)
- Additional components accessible via `/admin/pages/pagename/componentname`

#### `$group` (string, optional)
Menu group for navigation organization.

```php
public static ?string $group = 'Content Management';
public static ?string $group = 'System';
public static ?string $group = 'Reports';
```

**Default:** `null` (appears in default group)

#### `$title` (string, optional)
Display title for the page.

```php
public static ?string $title = 'User Analytics Dashboard';
```

**Default:** Generated from class name (e.g., `SystemDashboardPage` → "System Dashboard")

#### `$icon` (string, optional)
Heroicon name for menu item.

```php
public static ?string $icon = 'chart-bar';
public static ?string $icon = 'shield-check';
public static ?string $icon = 'cog';
```

**Default:** `null` (no icon displayed)  
**Icon Library:** [Heroicons](https://heroicons.com/) outline icons

### Required Methods

#### `fields(Request $request): array`
Define fields for the page using the admin panel's field system.

**Signature:**
```php
abstract public function fields(Request $request): array;
```

**Return Value:** Array of field instances

**Example:**
```php
public function fields(Request $request): array
{
    return [
        Text::make('Name')->rules('required'),
        Email::make('Email')->rules('email'),
        Select::make('Status')->options([
            'active' => 'Active',
            'inactive' => 'Inactive',
        ]),
        Boolean::make('Featured'),
        Number::make('Priority')->min(1)->max(100),
    ];
}
```

**Field Integration:**
- Uses same field system as Resources
- All field types supported (Text, Select, Boolean, etc.)
- Field validation and formatting applied automatically
- Fields passed to Vue component as `fields` prop

### Optional Methods

#### `data(Request $request): array`
Provide custom data to the Vue component.

**Signature:**
```php
public function data(Request $request): array;
```

**Return Value:** Array of custom data

**Example:**
```php
public function data(Request $request): array
{
    return [
        'statistics' => [
            'total_users' => User::count(),
            'active_sessions' => $this->getActiveSessions(),
        ],
        'recent_activity' => $this->getRecentActivity(),
        'configuration' => [
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
        ],
    ];
}
```

**Usage:** Data is passed to Vue component as `data` prop

#### `actions(Request $request): array`
Define actions available on the page.

**Signature:**
```php
public function actions(Request $request): array;
```

**Return Value:** Array of Action instances

**Example:**
```php
public function actions(Request $request): array
{
    return [
        new RefreshDataAction(),
        new ExportReportAction(),
        new ClearCacheAction(),
    ];
}
```

**Action Integration:**
- Actions passed to Vue component as `actions` prop
- Can be executed via frontend action system
- Support bulk operations and confirmations

#### `metrics(Request $request): array`
Define metrics to display on the page.

**Signature:**
```php
public function metrics(Request $request): array;
```

**Return Value:** Array of Metric instances

**Example:**
```php
public function metrics(Request $request): array
{
    return [
        new TotalUsersMetric(),
        new ActiveSessionsMetric(),
        new SystemHealthMetric(),
    ];
}
```

#### `authorizedToViewAny(Request $request): bool`
Control page access authorization.

**Signature:**
```php
public static function authorizedToViewAny(Request $request): bool;
```

**Return Value:** Boolean indicating if user can access page

**Example:**
```php
public static function authorizedToViewAny(Request $request): bool
{
    $user = $request->user();
    
    return $user && (
        $user->hasRole('admin') || 
        $user->hasPermission('view-system-dashboard')
    );
}
```

**Default:** Returns `true` (allow all authenticated users)

### Static Helper Methods

#### `routeName(): string`
Get the route name for this page.

**Example Return:** `"admin-panel.pages.systemdashboard"`

#### `uriPath(): string`
Get the URI path for this page.

**Example Return:** `"pages/systemdashboard"`

#### `label(): string`
Get the display label for this page.

**Example Return:** `"System Dashboard"`

#### `group(): ?string`
Get the menu group for this page.

**Example Return:** `"System"`

#### `hasMultipleComponents(): bool`
Check if page has multiple components.

**Returns:** `true` if `$components` array has more than one component

#### `getComponents(): array`
Get all component names for this page.

**Returns:** The `$components` array

## Component Resolution System

### Resolution Priority

1. **Manifest-based resolution** (for packages)
2. **File-based resolution** (for applications)
3. **Package fallback resolution**
4. **Error handling** (component not found)

### Frontend Integration

#### Component Props
All Custom Page Vue components receive these props:

```javascript
const props = defineProps({
    page: Object,      // Page metadata
    fields: Array,     // Field definitions
    actions: Array,    // Available actions
    metrics: Array,    // Page metrics
    data: Object,      // Custom data
})
```

#### Global Component Manifests
```javascript
// Available in frontend as:
window.adminPanelComponentManifests = {
    'app': {
        'SystemDashboard': '/build/assets/SystemDashboard-abc123.js'
    },
    'yourvendor/your-package': {
        'BlogManagement': '/vendor/your-package/assets/Pages/BlogManagement-def456.js'
    }
}
```

## Route Generation

### Automatic Routes

| Page Components | Generated Routes |
|----------------|------------------|
| `['Dashboard']` | `/admin/pages/dashboard` |
| `['Wizard', 'Step1']` | `/admin/pages/wizard`<br>`/admin/pages/wizard/Step1` |

### Route Naming Convention

| Route Type | Pattern | Example |
|------------|---------|---------|
| **Primary** | `admin-panel.pages.{pagename}` | `admin-panel.pages.dashboard` |
| **Component** | `admin-panel.pages.{pagename}.component` | `admin-panel.pages.wizard.component` |

### Route Parameters

Multi-component pages accept a `component` parameter:
```
/admin/pages/wizard/Step1
```
Where `Step1` must match a component name in the `$components` array.

## Error Handling

### Common Errors

#### Component Not Found
**Error:** Vue component file doesn't exist  
**Solution:** Ensure component file exists in correct location

#### Invalid Page Class
**Error:** Page class doesn't extend `Page` or missing required properties  
**Solution:** Verify class inheritance and required `$components` property

#### Authorization Failure
**Error:** User cannot access page  
**Solution:** Check `authorizedToViewAny()` implementation

#### Manifest Registration Failure
**Error:** Package manifest not found or invalid  
**Solution:** Verify manifest file exists and has valid JSON structure

### Debug Mode

Enable debug mode for detailed error information:

```php
// config/admin-panel.php
'debug' => env('ADMIN_PANEL_DEBUG', false),
```

When enabled, provides:
- Detailed component resolution logs
- Manifest loading information
- Route registration details
- Authorization check results
