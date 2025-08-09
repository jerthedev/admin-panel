# System Dashboard Custom Page Example

This example demonstrates creating a comprehensive system dashboard Custom Page that displays server information, performance metrics, and system configuration options.

## What This Example Covers

- **Field Integration**: Using various field types for display and interaction
- **Data Binding**: Fetching and displaying real-time system data
- **Actions**: Implementing custom actions for system management
- **Metrics**: Displaying key performance indicators
- **Authorization**: Restricting access to admin users only
- **Vue Component**: Creating a responsive dashboard interface

## Use Cases

This type of Custom Page is perfect for:
- **System Administration**: Server monitoring and management
- **Application Health**: Performance metrics and diagnostics
- **Configuration Management**: System settings and toggles
- **Operations Dashboard**: Real-time operational data

## Files in This Example

1. **SystemDashboardPage.php** - The page class with fields, data, and actions
2. **SystemDashboard.vue** - The Vue component with dashboard layout
3. **explanation.md** - Detailed breakdown of implementation choices

## Key Features Demonstrated

### Field Types Used
- `Text::make()` - For displaying server information
- `Number::make()` - For numeric metrics with formatting
- `Boolean::make()` - For system toggles and status indicators
- `Select::make()` - For configuration dropdowns

### Data Integration
- Real-time system metrics
- Server configuration data
- Performance indicators
- Status information

### Vue Component Features
- Responsive grid layout
- Real-time data updates
- Interactive controls
- Consistent admin panel theming

## Installation Steps

1. **Copy the page class** to `app/Admin/Pages/SystemDashboardPage.php`
2. **Copy the Vue component** to `resources/js/admin-pages/SystemDashboard.vue`
3. **Run the setup command** if you haven't already:
   ```bash
   php artisan admin-panel:setup-custom-pages
   ```
4. **Compile assets**:
   ```bash
   npm run build
   ```
5. **Access the dashboard** at `/admin/pages/systemdashboard`

## Customization Ideas

- Add more system metrics (disk usage, network stats)
- Integrate with monitoring services (New Relic, DataDog)
- Add system log viewing capabilities
- Include database performance metrics
- Add server management actions (restart services, clear caches)

## Next Steps

After implementing this example:
- Explore the [Settings Page Example](../settings-page/) for form handling
- Check the [Report Page Example](../report-page/) for data visualization
- Review the [Wizard Page Example](../wizard-page/) for multi-step interfaces
