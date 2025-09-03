# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## ⚠️ IMPORTANT: Tailwind CSS v4 Compatibility

This package uses **Tailwind CSS v4** which has significant syntax changes from v3. When writing or modifying styles, you MUST use Tailwind v4 syntax:

### Tailwind v4 Syntax Requirements

#### 1. Opacity Modifiers
**❌ WRONG (v3 syntax):**
```css
bg-black bg-opacity-50
text-red-500 text-opacity-75
border-gray-300 border-opacity-25
ring-4 ring-blue-500 ring-opacity-20
```

**✅ CORRECT (v4 syntax):**
```css
bg-black/50
text-red-500/75
border-gray-300/25
ring-4 ring-blue-500/20
```

#### 2. Scoped Styles in Vue Components
All Vue components with `<style scoped>` that use Tailwind utilities MUST include:
```css
<style scoped>
@import '../../../css/admin.css' reference;
/* Adjust the path based on component location */
</style>
```

#### 3. Common v4 Changes
- `bg-opacity-*`, `text-opacity-*`, `border-opacity-*`, `ring-opacity-*` → Use slash notation
- `divide-opacity-*` → `divide-{color}/{opacity}`
- `placeholder-opacity-*` → `placeholder-{color}/{opacity}`
- Dark mode: `dark:bg-gray-800 dark:bg-opacity-50` → `dark:bg-gray-800/50`
- Hover states: `hover:bg-opacity-75` needs base color → `hover:bg-black/75`

#### 4. Package Dependencies
- Uses `@tailwindcss/vite` plugin (NOT PostCSS approach)
- Inertia.js v2.0 for Laravel 12 compatibility
- Vue 3 with Composition API

### Fix Scripts Available
- `npm run fix:tailwind` - Fix scoped styles reference directive
- `npm run fix:tailwind:dry` - Preview fixes without applying
- `node fix-tailwind-v4-opacity.js` - Update opacity syntax

## Commands

### Frontend Development
```bash
# Install dependencies
npm install

# Start development server with hot reload
npm run dev

# Build for production
npm run build

# Run frontend tests
npm test                  # Run all Vitest tests
npm run test:watch       # Run tests in watch mode
npm run test:coverage    # Generate coverage report
npm run test:ui          # Open Vitest UI

# E2E tests with Playwright
npm run test:e2e         # Run E2E tests
npm run test:e2e:headed  # Run E2E tests with browser UI
npm run test:e2e:debug   # Debug E2E tests
npm run test:e2e:ui      # Open Playwright UI
```

### Backend Development
```bash
# Install PHP dependencies
composer install

# Run PHP tests
composer test            # Run PHPUnit tests
composer test-coverage   # Generate coverage report

# Code quality
composer analyse         # Run PHPStan static analysis
composer format          # Format code with Laravel Pint
composer format-test     # Check formatting without changes

# Laravel Artisan commands
php artisan admin-panel:install         # Install the admin panel package
php artisan admin-panel:resource        # Generate a new resource
php artisan admin-panel:card           # Create a new card
php artisan admin-panel:dashboard      # Create a new dashboard
php artisan admin-panel:field          # Create a custom field
php artisan admin-panel:page           # Create a custom page
php artisan admin-panel:list-resources # List all registered resources
php artisan admin-panel:clear-cache    # Clear admin panel cache
```

## Architecture

### Package Structure
This is a Laravel package that provides a Nova-like admin panel with Vue.js/Inertia.js frontend. The package follows Laravel package conventions with service providers, config publishing, and asset compilation.

### Backend Architecture (PHP/Laravel)
- **Service Provider**: `src/AdminPanelServiceProvider.php` - Main entry point, registers routes, middleware, commands
- **Resources**: `src/Resources/` - Base Resource class and traits for CRUD operations
- **Fields**: `src/Fields/` - Field types (TextField, BooleanField, etc.) with validation and display logic
- **Actions**: `src/Actions/` - Bulk and individual actions for resources
- **Filters**: `src/Filters/` - Resource filtering system
- **Cards/Metrics**: `src/Cards/`, `src/Metrics/` - Dashboard widgets and analytics
- **Dashboards**: `src/Dashboards/` - Dashboard management system
- **HTTP Layer**: `src/Http/Controllers/` - Inertia controllers for admin panel routes
- **Middleware**: `src/Http/Middleware/` - Admin authentication and authorization

### Frontend Architecture (Vue.js/Inertia.js)
- **Entry Point**: `resources/js/app.js` - Initializes Vue, Inertia, Pinia stores
- **Pages**: `resources/js/pages/` - Inertia page components (Resources, Dashboard, etc.)
- **Components**: `resources/js/components/` - Reusable Vue components
- **Layouts**: `resources/js/Layouts/` - Admin panel layout wrappers
- **Stores**: `resources/js/stores/` - Pinia state management for navigation, caching, preferences
- **Composables**: `resources/js/composables/` - Vue composition API utilities
- **Services**: `resources/js/services/` - API clients and utility services

### Key Integration Points
- **Inertia.js Bridge**: Handles SPA navigation without API, server-side routing with client-side rendering
- **Vite Plugin**: Custom plugin in `vite/` for dynamic page discovery and hot reload
- **Tailwind v4**: Uses new Vite plugin approach (`@tailwindcss/vite`) instead of PostCSS
- **Media Library**: Integration with Spatie Media Library for file uploads
- **Ziggy**: Route generation for JavaScript using Laravel named routes

### Resource System
Resources are the core concept - they map Eloquent models to admin CRUD interfaces:
1. Resource class defines fields, filters, actions, validation
2. Controller handles CRUD operations through Inertia responses
3. Vue pages render forms and tables based on resource definition
4. Field classes handle display, validation, and storage logic

### Dashboard System
- Dashboards contain Cards (widgets) arranged in a grid
- Cards can be metrics (ValueCard, TrendCard) or custom components
- Dashboard navigation and caching handled by Pinia stores
- Mobile-optimized with gesture support

## Testing Strategy
- **PHP**: PHPUnit for unit/feature tests, focus on Resources, Fields, Actions
- **JavaScript**: Vitest for component tests with 90% coverage threshold
- **E2E**: Playwright for critical user flows (resource CRUD, dashboard interaction)
- **Static Analysis**: PHPStan for PHP, TypeScript for Vue components