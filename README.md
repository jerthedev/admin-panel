# JTD Admin Panel

A modern, feature-rich admin panel package for Laravel applications built with Vue.js and Inertia.js.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jerthedev/admin-panel.svg?style=flat-square)](https://packagist.org/packages/jerthedev/admin-panel)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/jerthedev/admin-panel/run-tests?label=tests)](https://github.com/jerthedev/admin-panel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/jerthedev/admin-panel/Check%20&%20fix%20styling?label=code%20style)](https://github.com/jerthedev/admin-panel/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jerthedev/admin-panel.svg?style=flat-square)](https://packagist.org/packages/jerthedev/admin-panel)

## Features

### 🚀 Core Features
- **Resource Management**: Complete CRUD operations with elegant interfaces
- **Field System**: 8+ field types with validation and custom rules
- **Advanced Filtering**: Multiple filter types with real-time search
- **Bulk Actions**: Export, delete, status updates, and custom actions
- **Authentication**: Secure admin authentication with role-based access
- **Authorization**: Policy-based permissions and resource-level authorization

### 🎨 Modern Frontend
- **Vue.js 3**: Built with Composition API and modern JavaScript
- **Inertia.js**: SPA experience without API complexity
- **Tailwind CSS**: Professional styling with dark theme support
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Real-time Updates**: Live search, filtering, and form validation

### 🛠️ Developer Experience
- **Laravel Integration**: Seamless integration with existing Laravel apps
- **Artisan Commands**: Generate resources, install package, and more
- **Extensible**: Easy to customize and extend with your own components
- **Well Tested**: Comprehensive test suite with 95%+ coverage
- **Documentation**: Complete documentation with examples

## Requirements

- PHP 8.1+
- Laravel 10.0+
- Node.js 16+ (for frontend compilation)

## Installation

Install the package via Composer:

```bash
composer require jerthedev/admin-panel
```

Run the installation command:

```bash
php artisan admin-panel:install
```

This will:
- Publish configuration files
- Publish and compile frontend assets
- Set up authentication middleware
- Create example resources (optional)

## Quick Start

### 1. Create Your First Resource

```bash
php artisan admin-panel:resource PostResource --model=Post
```

### 2. Define Fields and Behavior

```php
<?php

namespace App\AdminPanel;

use JTD\AdminPanel\Resource;
use JTD\AdminPanel\Fields\TextField;
use JTD\AdminPanel\Fields\TextareaField;
use JTD\AdminPanel\Fields\BooleanField;

class PostResource extends Resource
{
    public static string $model = \App\Models\Post::class;

    public static string $title = 'title';

    public static array $search = ['title', 'content'];

    public function fields(Request $request): array
    {
        return [
            TextField::make('Title')
                ->sortable()
                ->rules('required', 'max:255'),

            TextareaField::make('Content')
                ->rules('required'),

            BooleanField::make('Published', 'is_published')
                ->sortable(),
        ];
    }
}
```

### 3. Register Your Resource

In your `AppServiceProvider`:

```php
use JTD\AdminPanel\Support\AdminPanel;

public function boot()
{
    AdminPanel::register([
        \App\AdminPanel\PostResource::class,
    ]);
}
```

### 4. Access Your Admin Panel

Visit `/admin` in your browser and log in with an admin user.

## Documentation

- [Installation Guide](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Creating Resources](docs/resources.md)
- [Field Types](docs/fields.md)
- [Filters](docs/filters.md)
- [Actions](docs/actions.md)
- [Authentication](docs/authentication.md)
- [Customization](docs/customization.md)
- [API Reference](docs/api-reference.md)

## Testing

Run the test suite:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jeremy Fall](https://github.com/jerthedev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
