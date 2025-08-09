# Settings Page Custom Page Example

This example demonstrates creating a comprehensive settings page Custom Page that handles form submission, validation, and configuration management.

## What This Example Covers

- **Form Handling**: Complete form submission and validation
- **Configuration Management**: Reading and writing application settings
- **Field Validation**: Client-side and server-side validation
- **Success/Error Handling**: User feedback for form operations
- **Conditional Fields**: Dynamic field display based on settings
- **Settings Persistence**: Saving configuration to database/config files

## Use Cases

This type of Custom Page is perfect for:
- **Application Settings**: Global application configuration
- **User Preferences**: User-specific settings and preferences
- **Feature Toggles**: Enabling/disabling application features
- **Integration Settings**: API keys, webhook URLs, external service configuration
- **Notification Settings**: Email, SMS, and push notification preferences
- **Security Settings**: Password policies, two-factor authentication

## Files in This Example

1. **ApplicationSettingsPage.php** - The page class with form handling
2. **ApplicationSettings.vue** - The Vue component with form interface
3. **form-handling.md** - Detailed form handling explanation

## Key Features Demonstrated

### Form Field Types
- **Text Fields**: For simple text input (site name, contact email)
- **Textarea Fields**: For longer text (descriptions, messages)
- **Boolean Fields**: For feature toggles and switches
- **Select Fields**: For dropdown options (timezones, themes)
- **Number Fields**: For numeric settings (limits, timeouts)
- **Password Fields**: For sensitive configuration (API keys)

### Form Validation
- **Client-side validation**: Immediate feedback in Vue component
- **Server-side validation**: Laravel validation rules in fields
- **Custom validation**: Business logic validation
- **Error display**: User-friendly error messages

### Settings Persistence
- **Database storage**: Using settings table or model
- **Configuration files**: Writing to config files
- **Cache integration**: Caching frequently accessed settings
- **Environment variables**: Managing environment-specific settings

## Installation Steps

1. **Copy the page class** to `app/Admin/Pages/ApplicationSettingsPage.php`
2. **Copy the Vue component** to `resources/js/admin-pages/ApplicationSettings.vue`
3. **Create settings table** (if using database storage):
   ```bash
   php artisan make:migration create_application_settings_table
   ```
4. **Run setup command** if needed:
   ```bash
   php artisan admin-panel:setup-custom-pages
   ```
5. **Compile assets**:
   ```bash
   npm run build
   ```
6. **Access the settings** at `/admin/pages/applicationsettings`

## Form Handling Flow

1. **Page Load**: Settings loaded from database/config and displayed in form
2. **User Input**: User modifies settings in Vue component
3. **Validation**: Real-time validation provides immediate feedback
4. **Form Submit**: Settings submitted via action to backend
5. **Processing**: Backend validates and saves settings
6. **Feedback**: Success/error message displayed to user
7. **State Update**: Form updated with new values

## Settings Categories

The example organizes settings into logical groups:

### General Settings
- Site name and description
- Contact information
- Default timezone and locale

### Feature Settings  
- Feature toggles and switches
- Module enable/disable options
- Experimental feature flags

### Security Settings
- Password requirements
- Session timeout
- Two-factor authentication

### Notification Settings
- Email notification preferences
- SMS notification settings
- Push notification configuration

### Integration Settings
- API keys and secrets
- Webhook URLs
- External service configuration

## Customization Ideas

- Add settings import/export functionality
- Implement settings versioning and rollback
- Add settings validation with custom rules
- Include settings backup and restore
- Add bulk settings operations
- Implement settings templates for different environments

## Advanced Features

### Conditional Settings Display
Show/hide settings based on other setting values or user permissions.

### Settings Validation
Implement complex validation rules that check setting combinations and dependencies.

### Real-time Settings
Settings that take effect immediately without requiring application restart.

### Settings Audit Trail
Track who changed what settings and when for compliance and debugging.

## Next Steps

After implementing this example:
- Explore the [Dashboard Page Example](../dashboard-page/) for display-focused pages
- Check the [Wizard Page Example](../wizard-page/) for multi-step interfaces
- Review the [Report Page Example](../report-page/) for data visualization
- See [Package Developer Guide](../../custom-pages-for-package-developers.md) for package integration
