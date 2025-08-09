# Package Manifest Registration System

The Package Manifest Registration System allows multiple packages to register their custom page manifests with the JTD Admin Panel, enabling multi-package custom page support with priority-based resolution.

## Overview

This system implements **JTDAP-71: Package Manifest Registration System** and provides:

- **Multi-package support**: Multiple packages can register custom pages
- **Priority-based resolution**: Lower numbers = higher priority (main app = 0)
- **Graceful degradation**: Packages work even if manifest files don't exist
- **Automatic aggregation**: All manifests are combined and injected into frontend
- **Conflict resolution**: Clear priority system prevents component conflicts

## Usage

### For Package Developers

Register your package's custom page manifest in your service provider:

```php
<?php

namespace YourVendor\YourPackage;

use Illuminate\Support\ServiceProvider;
use JTD\AdminPanel\Support\AdminPanel;

class YourPackageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register your custom page manifest
        AdminPanel::registerCustomPageManifest([
            'package' => 'yourvendor/your-package',
            'manifest_url' => '/vendor/your-package/admin-pages-manifest.json',
            'priority' => 100, // Higher numbers = lower priority
            'base_url' => '/vendor/your-package',
        ]);
    }
}
```

### Configuration Options

| Field | Required | Description | Default |
|-------|----------|-------------|---------|
| `package` | Yes | Unique package identifier (vendor/name) | - |
| `manifest_url` | Yes | Path to your manifest JSON file | - |
| `priority` | No | Resolution priority (lower = higher priority) | 100 |
| `base_url` | No | Base URL for your package assets | '' |

### Priority System

- **Main application**: Priority 0 (highest priority)
- **Core packages**: Priority 50-99
- **Third-party packages**: Priority 100+ (recommended)

### Manifest File Format

Your manifest file should follow this structure:

```json
{
  "Pages": {
    "ComponentName": {
      "file": "assets/Pages/ComponentName-abc123.js"
    },
    "AnotherComponent": {
      "file": "assets/Pages/AnotherComponent-def456.js"
    }
  }
}
```

## Frontend Integration

The system automatically:

1. **Aggregates all manifests** from registered packages
2. **Injects them into the frontend** via `window.adminPanelComponentManifests`
3. **Resolves components by priority** when conflicts occur
4. **Provides graceful fallbacks** for missing components

### Frontend Structure

```javascript
window.adminPanelComponentManifests = {
  'app': {
    'Pages/ComponentName': '/build/assets/Pages/ComponentName-abc123.js'
  },
  'yourvendor/your-package': {
    'Pages/PackageComponent': '/vendor/your-package/assets/Pages/PackageComponent-def456.js'
  }
}
```

## API Reference

### AdminPanel::registerCustomPageManifest()

Static method for registering manifests in service providers.

```php
AdminPanel::registerCustomPageManifest(array $config): void
```

### CustomPageManifestRegistry Methods

```php
// Check if package is registered
$registry->hasPackage(string $package): bool

// Get specific package manifest
$registry->getByPackage(string $package): ?array

// Get all registered manifests
$registry->all(): Collection

// Get aggregated manifest for frontend
$registry->getAggregatedManifest(): array

// Remove a package
$registry->unregister(string $package): bool

// Clear all manifests
$registry->clear(): void

// Get registration statistics
$registry->count(): int
```

## Error Handling

The system provides graceful error handling:

- **Missing manifest files**: Package is still registered with empty components
- **Invalid JSON**: Logged as warning, package gets empty components
- **Duplicate packages**: Throws `InvalidArgumentException`
- **Invalid configuration**: Throws `InvalidArgumentException` with clear message

## Examples

### Basic Package Registration

```php
// In your package's service provider
AdminPanel::registerCustomPageManifest([
    'package' => 'jerthedev/cms-blog-system',
    'manifest_url' => '/vendor/cms-blog-system/admin-pages-manifest.json',
    'priority' => 100,
    'base_url' => '/vendor/cms-blog-system',
]);
```

### High Priority Package

```php
// For core/important packages
AdminPanel::registerCustomPageManifest([
    'package' => 'jerthedev/admin-core',
    'manifest_url' => '/vendor/admin-core/admin-pages-manifest.json',
    'priority' => 50, // Higher priority than regular packages
    'base_url' => '/vendor/admin-core',
]);
```

### Development/Testing

```php
// For testing or development packages
AdminPanel::registerCustomPageManifest([
    'package' => 'dev/test-package',
    'manifest_url' => '/test/manifest.json',
    'priority' => 999, // Lowest priority
    'base_url' => '/test',
]);
```

## Best Practices

1. **Use semantic package names**: Follow `vendor/package` convention
2. **Set appropriate priorities**: Core packages 50-99, third-party 100+
3. **Handle missing files gracefully**: System will continue working
4. **Test with multiple packages**: Ensure no component name conflicts
5. **Document your components**: Provide clear component documentation
6. **Version your manifests**: Consider versioning for breaking changes

## Troubleshooting

### Package Not Appearing

1. Check if package is registered: `$registry->hasPackage('your/package')`
2. Verify manifest file exists and is valid JSON
3. Check browser console for loading errors
4. Ensure priority is set correctly

### Component Conflicts

1. Check component names for duplicates across packages
2. Verify priority settings (lower number = higher priority)
3. Use unique component names within your package

### Performance Issues

1. Keep manifest files small and focused
2. Use appropriate caching strategies
3. Consider lazy loading for large component sets

## Related Documentation

- [Custom Pages Overview](custom-pages.md)
- [Component Resolution System](component-resolution.md)
- [Package Development Guide](package-development.md)
