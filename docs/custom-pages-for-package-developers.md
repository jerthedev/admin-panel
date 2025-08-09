# Custom Pages for Package Developers

**Target Audience:** Package developers creating Laravel packages that include Custom Pages  
**Prerequisites:** Read the main [Custom Pages Documentation](custom-pages.md) first

This document covers the additional considerations and setup required when creating Custom Pages within Laravel packages that extend the JTD Admin Panel.

## Overview

When developing a Laravel package that includes Custom Pages, you need to handle:

- **Manifest Registration**: Register your package's Custom Pages with the admin panel
- **Asset Publishing**: Ensure your Vue components are properly built and served
- **Service Provider Setup**: Integrate with the admin panel's discovery system
- **Component Resolution**: Handle package-specific component paths
- **Testing**: Test your Custom Pages in package context

## Package Integration Setup

### 1. Service Provider Registration

In your package's service provider, register your Custom Pages manifest:

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

        // Register your Custom Pages
        AdminPanel::pages([
            \YourVendor\YourPackage\Admin\Pages\BlogManagementPage::class,
            \YourVendor\YourPackage\Admin\Pages\PostModerationPage::class,
        ]);

        // Publish assets
        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/your-package'),
        ], 'your-package-assets');
    }
}
```

### 2. Manifest File Creation

Create a manifest file that maps your components to their built assets:

```json
{
  "Pages": {
    "BlogManagement": {
      "file": "assets/Pages/BlogManagement-abc123.js"
    },
    "PostModeration": {
      "file": "assets/Pages/PostModeration-def456.js"
    }
  }
}
```

**Manifest Location:** Place in your package's `dist/` or `public/` directory

### 3. Component Path Differences

#### Application vs Package Paths

| Context | Component Location | Resolution |
|---------|-------------------|------------|
| **Application** | `resources/js/admin-pages/Dashboard.vue` | Direct file resolution |
| **Package** | `src/resources/js/pages/BlogManagement.vue` | Manifest-based resolution |

#### Package Component Structure
```
your-package/
├── src/
│   ├── Admin/
│   │   └── Pages/
│   │       ├── BlogManagementPage.php
│   │       └── PostModerationPage.php
│   └── resources/
│       └── js/
│           └── pages/
│               ├── BlogManagement.vue
│               └── PostModeration.vue
├── dist/
│   ├── admin-pages-manifest.json
│   └── assets/
│       └── Pages/
│           ├── BlogManagement-abc123.js
│           └── PostModeration-def456.js
└── YourPackageServiceProvider.php
```

## Creating Custom Pages in Packages

### Page Class Example

```php
<?php

namespace YourVendor\YourPackage\Admin\Pages;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Pages\Page;

class BlogManagementPage extends Page
{
    /**
     * The Vue components for this page.
     */
    public static array $components = ['BlogManagement'];

    /**
     * The menu group this page belongs to.
     */
    public static ?string $group = 'Content Management';

    /**
     * The display title for this page.
     */
    public static ?string $title = 'Blog Management';

    /**
     * The icon for this page.
     */
    public static ?string $icon = 'document-text';

    /**
     * Get the fields for this page.
     */
    public function fields(Request $request): array
    {
        return [
            Text::make('Blog Title')
                ->rules('required', 'max:255')
                ->help('The main title for your blog'),

            Select::make('Status')->options([
                'draft' => 'Draft',
                'published' => 'Published',
                'archived' => 'Archived',
            ])->help('Current blog post status'),

            Boolean::make('Featured')
                ->help('Mark this post as featured'),

            Text::make('SEO Title')
                ->help('Title for search engine optimization'),
        ];
    }

    /**
     * Get custom data for this page.
     */
    public function data(Request $request): array
    {
        return [
            'blog_stats' => [
                'total_posts' => \YourVendor\YourPackage\Models\BlogPost::count(),
                'published_posts' => \YourVendor\YourPackage\Models\BlogPost::published()->count(),
                'draft_posts' => \YourVendor\YourPackage\Models\BlogPost::draft()->count(),
            ],
            'recent_posts' => \YourVendor\YourPackage\Models\BlogPost::latest()->take(5)->get(),
        ];
    }

    /**
     * Authorization for package Custom Pages.
     */
    public static function authorizedToViewAny(Request $request): bool
    {
        // Use your package's permission system
        return $request->user()?->can('manage-blog-content') ?? false;
    }
}
```

## Asset Building for Packages

### Build Configuration

Your package needs its own build process for Vue components:

```javascript
// vite.config.js in your package
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [vue()],
    build: {
        outDir: 'dist',
        rollupOptions: {
            input: {
                'BlogManagement': 'src/resources/js/pages/BlogManagement.vue',
                'PostModeration': 'src/resources/js/pages/PostModeration.vue',
            },
            output: {
                entryFileNames: 'assets/Pages/[name]-[hash].js',
                chunkFileNames: 'assets/chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]'
            }
        }
    }
})
```

### Manifest Generation

Generate the manifest file during your build process:

```javascript
// build-manifest.js
const fs = require('fs')
const path = require('path')

const distDir = path.join(__dirname, 'dist')
const manifestPath = path.join(distDir, 'admin-pages-manifest.json')

// Read built files and generate manifest
const manifest = {
    Pages: {}
}

// Scan for built component files
const assetsDir = path.join(distDir, 'assets', 'Pages')
if (fs.existsSync(assetsDir)) {
    const files = fs.readdirSync(assetsDir)
    
    files.forEach(file => {
        const componentName = file.split('-')[0] // Extract component name from hash
        manifest.Pages[componentName] = {
            file: `assets/Pages/${file}`
        }
    })
}

fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 2))
console.log('Manifest generated:', manifestPath)
```

## Testing Package Custom Pages

### Unit Testing Page Classes

```php
<?php

namespace YourVendor\YourPackage\Tests\Unit\Admin\Pages;

use Tests\TestCase;
use Illuminate\Http\Request;
use YourVendor\YourPackage\Admin\Pages\BlogManagementPage;

class BlogManagementPageTest extends TestCase
{
    public function test_page_has_required_properties()
    {
        $this->assertEquals(['BlogManagement'], BlogManagementPage::$components);
        $this->assertEquals('Content Management', BlogManagementPage::$group);
        $this->assertEquals('Blog Management', BlogManagementPage::$title);
    }

    public function test_fields_are_properly_defined()
    {
        $page = new BlogManagementPage();
        $request = Request::create('/');
        
        $fields = $page->fields($request);
        
        $this->assertIsArray($fields);
        $this->assertNotEmpty($fields);
        
        // Test specific field exists
        $titleField = collect($fields)->firstWhere('attribute', 'blog_title');
        $this->assertNotNull($titleField);
    }

    public function test_authorization_works()
    {
        $user = \App\Models\User::factory()->create(['role' => 'admin']);
        $request = Request::create('/');
        $request->setUserResolver(fn() => $user);
        
        $this->assertTrue(BlogManagementPage::authorizedToViewAny($request));
    }
}
```

### Integration Testing

```php
public function test_page_appears_in_admin_panel()
{
    $this->actingAs(\App\Models\User::factory()->admin()->create());
    
    $response = $this->get('/admin');
    
    $response->assertStatus(200);
    $response->assertSee('Blog Management'); // Page appears in menu
}

public function test_page_loads_correctly()
{
    $this->actingAs(\App\Models\User::factory()->admin()->create());
    
    $response = $this->get('/admin/pages/blogmanagement');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => 
        $page->component('BlogManagement')
             ->has('fields')
             ->has('data')
    );
}
```

## Key Differences from Application Development

### 1. Namespace Considerations
- Use your package's namespace for Page classes
- Ensure proper autoloading in composer.json
- Follow PSR-4 standards for class organization

### 2. Asset Management
- Package components are built separately from main application
- Manifest system handles component resolution
- Assets are published to public/vendor/your-package/

### 3. Component Resolution
- Package components resolve via manifest system
- Main application components resolve via file system
- Priority system handles conflicts between packages

### 4. Testing Context
- Test within package context using package test suite
- Integration tests verify compatibility with host applications
- Consider testing with multiple JTD Admin Panel versions

## Example: JTD-CMSBlogSystem Integration

For a complete real-world example, see how the JTD-CMSBlogSystem package integrates Custom Pages:

```php
// In JTD-CMSBlogSystem service provider
AdminPanel::registerCustomPageManifest([
    'package' => 'jerthedev/cms-blog-system',
    'manifest_url' => '/vendor/cms-blog-system/admin-pages-manifest.json',
    'priority' => 100,
    'base_url' => '/vendor/cms-blog-system',
]);

AdminPanel::pages([
    \JTD\CMSBlogSystem\Admin\Pages\BlogManagementPage::class,
    \JTD\CMSBlogSystem\Admin\Pages\PostModerationPage::class,
    \JTD\CMSBlogSystem\Admin\Pages\BlogAnalyticsPage::class,
]);
```

## Best Practices for Package Developers

1. **Use Semantic Versioning**: Version your manifest files for breaking changes
2. **Set Appropriate Priorities**: Use 100+ for third-party packages
3. **Handle Missing Dependencies**: Gracefully handle missing admin panel
4. **Document Component APIs**: Provide clear documentation for your Custom Pages
5. **Test Multi-Package Scenarios**: Ensure compatibility with other packages
6. **Follow Naming Conventions**: Use consistent component and class naming

## Related Documentation

- **Main Custom Pages Guide**: [custom-pages.md](custom-pages.md) - Complete Custom Pages documentation
- **Manifest System**: [package-manifest-registration.md](package-manifest-registration.md) - Detailed manifest system documentation
- **Installation Guide**: [installation.md](installation.md) - Basic package installation

For detailed API reference and advanced topics, refer to the main Custom Pages documentation which applies to both application and package development contexts.
