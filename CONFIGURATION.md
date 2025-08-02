# Configuration Guide

This guide covers all configuration options available in the JTD Admin Panel package.

## 📋 Table of Contents

- [Publishing Configuration](#publishing-configuration)
- [Basic Configuration](#basic-configuration)
- [Authentication Configuration](#authentication-configuration)
- [Asset Configuration](#asset-configuration)
- [Database Configuration](#database-configuration)
- [Advanced Configuration](#advanced-configuration)
- [Environment Variables](#environment-variables)

## 🚀 Publishing Configuration

After installation, publish the configuration file:

```bash
php artisan vendor:publish --tag=admin-panel-config
```

This creates `config/admin-panel.php` with all available options.

## ⚙️ Basic Configuration

### Admin Panel Path

Configure the URL path for the admin panel:

```php
// config/admin-panel.php
'path' => 'admin', // Default: /admin
```

**Examples:**
- `'admin'` → `/admin`
- `'dashboard'` → `/dashboard`
- `'backend'` → `/backend`

### Admin Panel Name

Set the display name for your admin panel:

```php
'name' => 'Admin Panel', // Appears in page titles and navigation
```

### Pagination

Configure default pagination settings:

```php
'pagination' => [
    'per_page' => 25,           // Default items per page
    'per_page_options' => [10, 25, 50, 100], // Available options
],
```

## 🔐 Authentication Configuration

### Guard Configuration

Specify which authentication guard to use:

```php
'auth' => [
    'guard' => 'web',           // Laravel guard name
    'password_broker' => 'users', // Password reset broker
],
```

### Login Configuration

Configure login behavior:

```php
'login' => [
    'redirect_to' => '/admin/dashboard', // After login redirect
    'logout_redirect_to' => '/admin/login', // After logout redirect
    'remember_me' => true,      // Enable "Remember Me" checkbox
],
```

## 🎨 Asset Configuration

### Theme Configuration

Configure the admin panel theme:

```php
'theme' => [
    'default' => 'light',       // 'light' or 'dark'
    'allow_user_toggle' => true, // Allow users to switch themes
],
```

### Asset Publishing

Configure how assets are handled:

```php
'assets' => [
    'auto_publish' => true,     // Auto-publish assets on install
    'version' => '1.0.0',       // Asset version for cache busting
],
```

## 🗄️ Database Configuration

### Table Prefix

Set a prefix for admin panel tables:

```php
'database' => [
    'prefix' => 'admin_',       // Prefix for admin tables
    'connection' => null,       // Use default connection
],
```

### Soft Deletes

Configure soft delete behavior:

```php
'soft_deletes' => [
    'enabled' => true,          // Enable soft deletes
    'show_deleted' => false,    // Show deleted items by default
],
```

## 🔧 Advanced Configuration

### Resource Configuration

Configure default resource behavior:

```php
'resources' => [
    'per_page' => 25,           // Default pagination
    'search_debounce' => 300,   // Search delay in milliseconds
    'auto_refresh' => false,    // Auto-refresh resource lists
],
```

### Field Configuration

Configure field defaults:

```php
'fields' => [
    'date_format' => 'Y-m-d',   // Default date format
    'datetime_format' => 'Y-m-d H:i:s', // Default datetime format
    'timezone' => 'UTC',        // Default timezone
],
```

### Cache Configuration

Configure caching behavior:

```php
'cache' => [
    'enabled' => true,          // Enable caching
    'ttl' => 3600,             // Cache TTL in seconds
    'prefix' => 'admin_panel_', // Cache key prefix
],
```

## 🌍 Environment Variables

You can override configuration using environment variables:

```env
# .env file
ADMIN_PANEL_PATH=admin
ADMIN_PANEL_NAME="My Admin Panel"
ADMIN_PANEL_THEME=light
ADMIN_PANEL_PER_PAGE=25
ADMIN_PANEL_AUTH_GUARD=web
```

### Available Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `ADMIN_PANEL_PATH` | `admin` | Admin panel URL path |
| `ADMIN_PANEL_NAME` | `Admin Panel` | Display name |
| `ADMIN_PANEL_THEME` | `light` | Default theme |
| `ADMIN_PANEL_PER_PAGE` | `25` | Default pagination |
| `ADMIN_PANEL_AUTH_GUARD` | `web` | Authentication guard |

## 🔄 Configuration Examples

### Multi-tenant Setup

```php
// config/admin-panel.php
'path' => env('ADMIN_PANEL_PATH', 'admin'),
'name' => env('ADMIN_PANEL_NAME', config('app.name') . ' Admin'),
'auth' => [
    'guard' => env('ADMIN_PANEL_GUARD', 'admin'),
],
```

### High-traffic Setup

```php
// config/admin-panel.php
'cache' => [
    'enabled' => true,
    'ttl' => 7200, // 2 hours
],
'pagination' => [
    'per_page' => 50, // Larger page size
],
```

## 🔧 Troubleshooting

### Common Configuration Issues

**Assets not loading:**
```bash
php artisan admin-panel:rebuild-assets
```

**Authentication not working:**
- Check `auth.guard` configuration
- Verify guard exists in `config/auth.php`

**Routes not working:**
- Check `path` configuration
- Clear route cache: `php artisan route:clear`

For more help, see [INSTALLATION.md](INSTALLATION.md) or [open an issue](https://github.com/jerthedev/admin-panel/issues).
