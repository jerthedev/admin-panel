# Custom Pages Artisan Commands

Complete reference for all artisan commands related to Custom Pages in the JTD Admin Panel.

## Overview

The JTD Admin Panel provides several artisan commands to streamline Custom Pages development:

- **`admin-panel:make-page`** - Create new Custom Pages with Vue components
- **`admin-panel:setup-custom-pages`** - Set up development environment for Custom Pages
- **`admin-panel:rebuild-assets`** - Rebuild and republish Custom Page assets

## admin-panel:make-page

Create a new Custom Page with associated Vue component(s).

### Signature
```bash
php artisan admin-panel:make-page {name} [options]
```

### Arguments

#### `name` (required)
The name of the Custom Page to create.

**Examples:**
```bash
php artisan admin-panel:make-page SystemDashboard
php artisan admin-panel:make-page UserReport  
php artisan admin-panel:make-page OnboardingWizard
```

**Naming Convention:**
- Use PascalCase for the name
- Don't include "Page" suffix (automatically added)
- Use descriptive names that indicate the page's purpose

### Options

#### `--components=` (optional)
Comma-separated list of component names for multi-component pages.

**Examples:**
```bash
# Single component (default behavior)
php artisan admin-panel:make-page Dashboard

# Multi-component wizard
php artisan admin-panel:make-page OnboardingWizard --components="OnboardingWizard,WizardStep1,WizardStep2,WizardStep3"

# Multi-component settings page
php artisan admin-panel:make-page SystemSettings --components="SystemSettings,GeneralSettings,SecuritySettings"
```

**Component Resolution:**
- First component is primary (loads at base route)
- Additional components accessible via routing
- All components created in `resources/js/admin-pages/`

#### `--group=` (optional)
The menu group for the page.

**Examples:**
```bash
php artisan admin-panel:make-page Dashboard --group="System"
php artisan admin-panel:make-page UserReport --group="Reports"
php artisan admin-panel:make-page BlogManagement --group="Content Management"
```

**Common Groups:**
- System
- Content Management
- Reports
- User Management
- Settings
- Analytics

#### `--icon=` (optional)
Heroicon name for the menu item.

**Examples:**
```bash
php artisan admin-panel:make-page Dashboard --icon="chart-bar"
php artisan admin-panel:make-page Settings --icon="cog"
php artisan admin-panel:make-page Security --icon="shield-check"
```

**Icon Reference:** [Heroicons Outline Icons](https://heroicons.com/)

#### `--force` (optional)
Overwrite existing files without confirmation.

**Example:**
```bash
php artisan admin-panel:make-page Dashboard --force
```

**Use Case:** Regenerating pages during development

### Complete Examples

#### Basic Dashboard Page
```bash
php artisan admin-panel:make-page SystemDashboard --group="System" --icon="server"
```

**Creates:**
- `app/Admin/Pages/SystemDashboardPage.php`
- `resources/js/admin-pages/SystemDashboard.vue`

#### Multi-Component Wizard
```bash
php artisan admin-panel:make-page OnboardingWizard \
    --components="OnboardingWizard,CompanyInfo,UserPreferences,FinalSetup" \
    --group="Setup" \
    --icon="academic-cap"
```

**Creates:**
- `app/Admin/Pages/OnboardingWizardPage.php`
- `resources/js/admin-pages/OnboardingWizard.vue`
- `resources/js/admin-pages/CompanyInfo.vue`
- `resources/js/admin-pages/UserPreferences.vue`
- `resources/js/admin-pages/FinalSetup.vue`

#### Settings Page with Sections
```bash
php artisan admin-panel:make-page ApplicationSettings \
    --components="ApplicationSettings,GeneralSettings,SecuritySettings,NotificationSettings" \
    --group="Administration" \
    --icon="cog"
```

## admin-panel:setup-custom-pages

Set up the development environment for Custom Pages.

### Signature
```bash
php artisan admin-panel:setup-custom-pages [options]
```

### What It Does

1. **Creates Directories**:
   - `app/Admin/Pages/` - For Custom Page classes
   - `resources/js/admin-pages/` - For Vue components

2. **Configures Vite**:
   - Updates `vite.config.js` with Custom Pages configuration
   - Sets up component resolution paths
   - Configures build output for Custom Pages

3. **Creates Example Files** (unless `--no-example`):
   - Example Custom Page class
   - Example Vue component
   - Documentation files

4. **Updates Service Provider**:
   - Registers Custom Pages discovery
   - Sets up route registration

### Options

#### `--force` (optional)
Overwrite existing files and configuration.

**Example:**
```bash
php artisan admin-panel:setup-custom-pages --force
```

#### `--no-example` (optional)
Skip creating example Custom Page files.

**Example:**
```bash
php artisan admin-panel:setup-custom-pages --no-example
```

### Vite Configuration

The command automatically updates your `vite.config.js`:

```javascript
// Added to vite.config.js
export default defineConfig({
    // ... existing config
    resolve: {
        alias: {
            '@admin-pages': path.resolve(__dirname, 'resources/js/admin-pages'),
        },
    },
    build: {
        rollupOptions: {
            input: {
                // Custom Pages components will be added here
                ...glob.sync('resources/js/admin-pages/**/*.vue').reduce((entries, file) => {
                    const name = path.basename(file, '.vue')
                    entries[name] = file
                    return entries
                }, {}),
            },
        },
    },
})
```

## admin-panel:rebuild-assets

Rebuild and republish Custom Page assets.

### Signature
```bash
php artisan admin-panel:rebuild-assets [options]
```

### What It Does

1. **Rebuilds Vue Components**: Compiles all Custom Page Vue components
2. **Updates Manifests**: Regenerates component manifest files
3. **Publishes Assets**: Copies built assets to public directory
4. **Clears Caches**: Clears component resolution caches

### Options

#### `--dev` (optional)
Build assets in development mode with source maps.

#### `--watch` (optional)
Watch for file changes and rebuild automatically.

**Example:**
```bash
php artisan admin-panel:rebuild-assets --dev --watch
```

## Command Workflow Examples

### Creating a New Custom Page

```bash
# 1. Set up Custom Pages environment (first time only)
php artisan admin-panel:setup-custom-pages

# 2. Create your Custom Page
php artisan admin-panel:make-page BlogDashboard --group="Content" --icon="document-text"

# 3. Customize the generated files
# Edit app/Admin/Pages/BlogDashboardPage.php
# Edit resources/js/admin-pages/BlogDashboard.vue

# 4. Build assets
npm run build

# 5. Test your page
# Visit /admin/pages/blogdashboard
```

### Creating a Multi-Component Wizard

```bash
# 1. Create wizard with multiple components
php artisan admin-panel:make-page SetupWizard \
    --components="SetupWizard,BasicInfo,Preferences,Completion" \
    --group="Setup" \
    --icon="cog"

# 2. Customize each component
# Edit app/Admin/Pages/SetupWizardPage.php
# Edit resources/js/admin-pages/SetupWizard.vue (primary)
# Edit resources/js/admin-pages/BasicInfo.vue
# Edit resources/js/admin-pages/Preferences.vue  
# Edit resources/js/admin-pages/Completion.vue

# 3. Build and test
npm run build
```

### Development Workflow

```bash
# During development, use watch mode for automatic rebuilding
npm run dev

# Or use the rebuild command with watch
php artisan admin-panel:rebuild-assets --dev --watch

# For production builds
npm run build
php artisan admin-panel:rebuild-assets
```

## Troubleshooting Commands

### Check Custom Pages Status
```bash
php artisan admin-panel:check
```
Shows status of Custom Pages setup, registered pages, and component resolution.

### Clear Custom Pages Cache
```bash
php artisan admin-panel:clear-cache
```
Clears all Custom Pages related caches including page discovery and component resolution.

### Validate Custom Pages
```bash
php artisan admin-panel:doctor
```
Runs comprehensive health checks on Custom Pages setup including:
- Page class validation
- Component file existence
- Route registration status
- Manifest file validation

## Command Integration with Package Development

### For Package Developers

When developing packages with Custom Pages:

```bash
# In your package directory
php artisan admin-panel:make-page BlogManagement --group="Content Management"

# Move generated files to package structure
mv app/Admin/Pages/BlogManagementPage.php src/Admin/Pages/
mv resources/js/admin-pages/BlogManagement.vue src/resources/js/pages/

# Update namespaces and build configuration for package
```

### Testing Package Custom Pages

```bash
# Test package Custom Pages in development environment
php artisan admin-panel:check --package="yourvendor/your-package"

# Rebuild package assets
php artisan admin-panel:rebuild-assets --package="yourvendor/your-package"
```

## Best Practices

### Command Usage
1. **Use descriptive names**: Choose clear, descriptive names for your Custom Pages
2. **Organize with groups**: Use consistent group names across related pages
3. **Choose appropriate icons**: Select icons that clearly represent the page function
4. **Plan multi-component structure**: Think through component organization before creation

### Development Workflow
1. **Set up environment first**: Run `setup-custom-pages` before creating pages
2. **Use watch mode**: Enable watch mode during active development
3. **Test frequently**: Build and test after each significant change
4. **Clear caches**: Clear caches when troubleshooting issues

### Production Deployment
1. **Build for production**: Use production builds for deployment
2. **Rebuild assets**: Run rebuild command after package updates
3. **Validate setup**: Use doctor command to verify production setup
4. **Monitor performance**: Check Custom Pages performance in production

## Related Documentation

- **Custom Pages Guide**: [custom-pages.md](../custom-pages.md) - Complete Custom Pages documentation
- **API Reference**: [custom-pages-api.md](../api/custom-pages-api.md) - Detailed API documentation
- **Package Development**: [custom-pages-for-package-developers.md](../custom-pages-for-package-developers.md) - Package integration guide
