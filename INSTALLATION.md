# Installation Guide

This guide will walk you through installing and setting up the JTD Admin Panel in your Laravel application.

## 📋 Requirements

Before installing, ensure your system meets these requirements:

### Server Requirements
- **PHP**: 8.1 or higher
- **Laravel**: 10.0 or higher
- **Database**: MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+
- **Memory**: 512MB minimum (1GB recommended)

### Frontend Requirements
- **Node.js**: 16.0 or higher
- **NPM**: 8.0 or higher (or Yarn 1.22+)

### PHP Extensions
- BCMath PHP Extension
- Ctype PHP Extension
- Fileinfo PHP Extension
- JSON PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- PDO PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension

## 🚀 Installation Steps

### Step 1: Install via Composer

```bash
composer require jerthedev/admin-panel
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag="admin-panel-config"
```

This creates `config/admin-panel.php` with default configuration.

### Step 3: Publish and Run Migrations

```bash
php artisan vendor:publish --tag="admin-panel-migrations"
php artisan migrate
```

### Step 4: Install Frontend Assets

```bash
php artisan admin-panel:install
```

This command will:
- Create the `package.json` file with required dependencies
- Set up Vite configuration
- Create basic Tailwind CSS configuration
- Set up PostCSS configuration

### Step 5: Install NPM Dependencies

```bash
npm install
```

### Step 6: Build Assets

For development:
```bash
npm run dev
```

For production:
```bash
npm run build
```

### Step 7: Configure Authentication (Optional)

If you want to use custom authentication logic, update your User model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // Add this method to control admin access
    public function canAccessAdmin(): bool
    {
        return $this->is_admin ?? false;
    }
}
```

## ⚙️ Configuration

### Basic Configuration

Edit `config/admin-panel.php`:

```php
return [
    // Admin panel URL path
    'path' => 'admin',
    
    // Middleware applied to admin routes
    'middleware' => ['web'],
    
    // Authentication configuration
    'auth' => [
        'guard' => 'web',
        'login_route' => 'admin-panel.login',
    ],
    
    // Resource auto-discovery
    'resources' => [
        'auto_discovery' => true,
        'discovery_path' => 'app/Admin/Resources',
    ],
];
```

### Environment Variables

Add these to your `.env` file:

```env
# Admin Panel Configuration
ADMIN_PANEL_PATH=admin
ADMIN_PANEL_CACHE_TTL=3600
ADMIN_PANEL_DEBUG=false
```

## 🔧 Post-Installation Setup

### 1. Create Admin User

Create an admin user to access the panel:

```bash
php artisan tinker
```

```php
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@example.com';
$user->password = bcrypt('password');
$user->is_admin = true;
$user->save();
```

### 2. Create Your First Resource

Create a directory for admin resources:

```bash
mkdir -p app/Admin/Resources
```

Create your first resource:

```php
<?php
// app/Admin/Resources/UserResource.php

namespace App\Admin\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Resources\Resource;

class UserResource extends Resource
{
    public static string $model = User::class;
    public static string $title = 'name';
    public static array $search = ['name', 'email'];

    public function fields(Request $request): array
    {
        return [
            Text::make('Name')->sortable()->rules('required'),
            Email::make('Email')->sortable()->rules('required', 'email'),
            Boolean::make('Admin', 'is_admin'),
        ];
    }
}
```

### 3. Access the Admin Panel

Visit `http://your-app.test/admin` in your browser.

## 🔍 Verification

### Check Installation

Run this command to verify everything is working:

```bash
php artisan admin-panel:check
```

### Test Routes

Verify routes are registered:

```bash
php artisan route:list --name=admin-panel
```

### Check Assets

Ensure assets are compiled:

```bash
ls -la public/build/
```

## 🚨 Troubleshooting

### Common Issues

#### 1. "Class not found" errors
```bash
composer dump-autoload
```

#### 2. Assets not loading
```bash
npm run build
php artisan config:clear
```

#### 3. Permission denied errors
```bash
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

#### 4. Database connection errors
Check your `.env` database configuration:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 5. Node.js version issues
Use Node Version Manager (nvm):
```bash
nvm install 16
nvm use 16
```

### Getting Help

If you encounter issues:

1. Check the [documentation](https://jerthedev.com/admin-panel)
2. Search [GitHub issues](https://github.com/jerthedev/admin-panel/issues)
3. Create a new issue with:
   - Laravel version
   - PHP version
   - Error message
   - Steps to reproduce

## 🎉 Next Steps

After successful installation:

1. **Create Resources**: Add more resources for your models
2. **Customize Dashboard**: Add metrics and widgets
3. **Set up Permissions**: Configure user roles and permissions
4. **Customize Theme**: Modify colors and styling
5. **Add Custom Fields**: Create custom field types

## 📚 Further Reading

- [Configuration Guide](CONFIGURATION.md)
- [Creating Resources](docs/resources.md)
- [Dashboard Metrics](docs/metrics.md)
- [Customization Guide](docs/customization.md)
