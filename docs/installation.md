# Installation Guide

This guide will walk you through installing and setting up the JTD Admin Panel in your Laravel application.

## Requirements

Before installing, ensure your system meets these requirements:

- **PHP**: 8.1 or higher
- **Laravel**: 10.0 or higher
- **Node.js**: 16.0 or higher (for frontend compilation)
- **Database**: MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+

## Step 1: Install the Package

Install the package via Composer:

```bash
composer require jerthedev/admin-panel
```

## Step 2: Run the Installation Command

Run the installation command to set up the admin panel:

```bash
php artisan admin-panel:install
```

This command will:

1. **Publish Configuration**: Copy the configuration file to `config/admin-panel.php`
2. **Publish Assets**: Copy frontend assets and compile them
3. **Set up Routes**: Register admin panel routes
4. **Configure Authentication**: Set up authentication middleware
5. **Create Directories**: Create necessary directories for resources

### Installation Options

The install command accepts several options:

```bash
# Install with example resources
php artisan admin-panel:install --with-examples

# Skip asset compilation
php artisan admin-panel:install --skip-assets

# Force overwrite existing files
php artisan admin-panel:install --force
```

## Step 3: Configure Authentication

### Option 1: Use Existing User Model

If you have an existing User model, update it to work with the admin panel:

```php
// In your User model
public function isAdmin(): bool
{
    return $this->is_admin; // or your admin logic
}
```

Add an `is_admin` column to your users table:

```bash
php artisan make:migration add_is_admin_to_users_table
```

```php
// In the migration
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('is_admin')->default(false);
    });
}
```

### Option 2: Custom Authorization

Configure custom authorization in `config/admin-panel.php`:

```php
'auth' => [
    'guard' => 'web',
    'authorize' => function ($user, $request) {
        // Your custom authorization logic
        return $user->hasRole('admin') || $user->can('access-admin-panel');
    },
],
```

## Step 4: Create Your First Admin User

Create an admin user to access the panel:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'is_admin' => true,
]);
```

## Step 5: Compile Frontend Assets

If you skipped asset compilation during installation, compile them now:

```bash
npm install
npm run build
```

For development with hot reloading:

```bash
npm run dev
```

## Step 6: Access the Admin Panel

Visit your admin panel at:

```
http://your-app.test/admin
```

Log in with the admin user you created.

## Configuration

### Basic Configuration

The main configuration file is located at `config/admin-panel.php`. Key settings include:

```php
return [
    // Admin panel path
    'path' => 'admin',
    
    // Middleware applied to admin routes
    'middleware' => ['web', 'admin.auth'],
    
    // Authentication settings
    'auth' => [
        'guard' => 'web',
        'user_model' => \App\Models\User::class,
    ],
    
    // Theme settings
    'theme' => [
        'default' => 'light', // 'light' or 'dark'
        'allow_user_toggle' => true,
    ],
];
```

### Environment Variables

You can override configuration using environment variables:

```env
ADMIN_PANEL_PATH=admin
ADMIN_PANEL_GUARD=web
ADMIN_PANEL_ALLOW_ALL=false
```

## Troubleshooting

### Common Issues

**1. 404 Error on Admin Routes**

Make sure the service provider is registered. In Laravel 11+, it should be auto-discovered. For older versions, add to `config/app.php`:

```php
'providers' => [
    // ...
    JTD\AdminPanel\AdminPanelServiceProvider::class,
],
```

**2. Assets Not Loading**

Ensure assets are published and compiled:

```bash
php artisan vendor:publish --provider="JTD\AdminPanel\AdminPanelServiceProvider" --tag="assets" --force
npm run build
```

**3. Authentication Issues**

Check your User model has the required methods and your database has the necessary columns:

```php
// User model should have
public function isAdmin(): bool
{
    return $this->is_admin;
}
```

**4. Permission Denied**

Ensure your web server has write permissions to:
- `storage/` directory
- `public/` directory
- `bootstrap/cache/` directory

### Getting Help

If you encounter issues:

1. Check the [troubleshooting guide](troubleshooting.md)
2. Review the [configuration documentation](configuration.md)
3. Search existing [GitHub issues](https://github.com/jerthedev/admin-panel/issues)
4. Create a new issue with detailed information

## Next Steps

Now that you have the admin panel installed:

1. [Create your first resource](resources.md)
2. [Learn about field types](fields.md)
3. [Set up filters and actions](filters.md)
4. [Customize the interface](customization.md)

## Updating

To update the package:

```bash
composer update jerthedev/admin-panel
php artisan admin-panel:install --force
```

This will update the package and republish any changed assets or configuration files.
