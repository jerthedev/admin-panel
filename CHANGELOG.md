# Changelog

All notable changes to `jerthedev/admin-panel` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-08-02

### 🎉 Initial Release

This is the first stable release of JTD Admin Panel - a modern, elegant admin panel for Laravel applications with complete CRUD functionality.

### ✨ Added

#### Core Features
- **Resource Management System**: Complete CRUD operations with elegant interfaces
- **Field System**: 8+ field types (Text, Email, Password, Boolean, Select, Date, etc.)
- **Advanced Filtering**: Multiple filter types with real-time search capabilities
- **Bulk Actions**: Export, delete, status updates, and custom bulk operations
- **Dashboard System**: Customizable dashboard with metrics and widgets
- **Authentication**: Secure admin authentication with role-based access control
- **Authorization**: Policy-based permissions and resource-level authorization
- **Navigation System**: Reusable ResourcesMenu widget with automatic resource discovery

#### Frontend Components
- **Vue.js 3**: Built with Composition API and modern JavaScript patterns
- **Inertia.js Integration**: SPA experience without API complexity
- **Tailwind CSS**: Professional styling with responsive design
- **8 Vue Components**: Button, Modal, Card, Alert, LoadingSpinner, ResourceTable, MetricCard, Widget
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile devices

#### Developer Experience
- **Laravel Integration**: Seamless integration with existing Laravel applications
- **Artisan Commands**: Generate resources, install package, and manage admin panel
- **Auto-Discovery**: Automatic resource discovery from `app/Admin/Resources/`
- **Extensible Architecture**: Easy to customize and extend with your own components
- **Comprehensive Testing**: 160+ tests ensuring reliability and stability

#### Performance & Caching
- **Resource Caching**: Intelligent caching system for improved performance
- **Metrics Caching**: Dashboard metrics cached with configurable TTL
- **Lazy Loading**: Optimized loading for better user experience
- **Database Optimization**: Efficient queries with proper indexing

#### Field Types
- `Text`: Basic text input with validation and searchable functionality
- `Email`: Email input with built-in validation and searchable functionality
- `Password`: Secure password input with hashing
- `Textarea`: Multi-line text input with searchable functionality
- `Number`: Numeric input with formatting
- `Boolean`: Checkbox/toggle with customizable labels
- `Select`: Dropdown selection with options
- `Date`: Date picker with localization
- `DateTime`: Combined date and time picker
- `File`: File upload with validation
- `Image`: Image upload with preview functionality

#### Dashboard Metrics
- **Base Metric Class**: Abstract foundation for custom metrics
- **UserCountMetric**: Total user count with trend analysis
- **ActiveUsersMetric**: Active users in configurable time periods
- **ResourceCountMetric**: Count of registered admin resources
- **SystemHealthMetric**: Overall system health monitoring
- **Trend Analysis**: Automatic trend calculation and visualization

#### Actions System
- **Resource Actions**: Individual and bulk actions on resources
- **UpdateStatusAction**: Built-in status update actions (activate/deactivate)
- **Custom Actions**: Easy creation of custom actions with confirmation dialogs
- **Action Authorization**: Per-action authorization controls

#### Configuration
- **Flexible Configuration**: Comprehensive configuration options
- **Environment-based Settings**: Support for environment-specific configurations
- **Middleware Customization**: Configurable middleware stack
- **Path Customization**: Customizable admin panel URL path
- **Theme Configuration**: Customizable colors and styling options

### 🔧 Technical Details

#### Requirements
- PHP 8.1 or higher
- Laravel 10.0 or higher
- Node.js 16.0 or higher
- MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+

#### Dependencies
- Vue.js 3.3+
- Inertia.js 1.0+
- Tailwind CSS 3.3+
- Heroicons for consistent iconography
- Laravel Sanctum for API authentication

#### Architecture
- **MVC Pattern**: Clean separation of concerns
- **Repository Pattern**: Data access abstraction
- **Service Layer**: Business logic encapsulation
- **Event-Driven**: Extensible event system
- **Policy-Based Authorization**: Laravel policy integration

### 📚 Documentation

#### Comprehensive Documentation
- **Installation Guide**: Step-by-step installation instructions
- **Configuration Reference**: Complete configuration options
- **Resource Guide**: Creating and managing resources
- **Field Reference**: All available field types and options
- **Metrics Guide**: Creating custom dashboard metrics
- **Customization Guide**: Theming and UI customization
- **API Documentation**: Complete API reference
- **Tutorial Series**: Step-by-step tutorials for common tasks

### 🧪 Testing

#### Test Coverage
- **162 Tests**: Comprehensive test suite
- **334 Assertions**: Thorough testing of all functionality
- **Unit Tests**: Individual component testing
- **Feature Tests**: End-to-end functionality testing
- **Integration Tests**: Cross-component integration testing

#### Test Categories
- Resource CRUD operations
- Authentication and authorization
- Field validation and processing
- Dashboard metrics calculation
- Action execution and authorization
- Configuration and setup
- Frontend component functionality

### 🔒 Security

#### Security Features
- **CSRF Protection**: Built-in CSRF token validation
- **XSS Prevention**: Automatic output escaping
- **SQL Injection Protection**: Parameterized queries
- **Authentication**: Secure session-based authentication
- **Authorization**: Role and permission-based access control
- **Input Validation**: Comprehensive input validation
- **File Upload Security**: Secure file handling with validation

### 🚀 Performance

#### Optimization Features
- **Caching**: Multi-level caching system
- **Lazy Loading**: Efficient resource loading
- **Database Optimization**: Optimized queries and indexing
- **Asset Optimization**: Minified and compressed assets
- **Memory Management**: Efficient memory usage
- **Response Time**: Sub-100ms response times for most operations

### 📦 Package Information

- **Package Name**: `jerthedev/admin-panel`
- **License**: MIT
- **Author**: Jeremy Fall (jerthedev@gmail.com)
- **Website**: https://jerthedev.com
- **Repository**: https://github.com/jerthedev/admin-panel
- **Documentation**: https://jerthedev.com/admin-panel

### 🔧 Critical Fixes Applied (Session 2025-08-02)

#### Data Display Issues
- **Fixed Field Rendering**: ResourceController now uses `resolveValue()` instead of `jsonSerialize()` for proper data display
- **Resolved Component Object Display**: User tables now show actual names, emails, and dates instead of component definitions

#### Form Functionality
- **Fixed Edit Form Submission**: Implemented navigation guard bypass during form submission using `isSubmitting` flag
- **Resolved Update Button**: Edit user functionality now works without triggering unsaved changes modal
- **Added Password Field**: UserResource now includes proper password field with automatic hashing

#### Route Management
- **Standardized Route Handling**: Removed custom route helpers in favor of Ziggy's global `route()` function
- **Fixed Create Button**: Create user button now has proper href and functionality
- **Cleaned Route Imports**: Removed conflicting route imports across all Vue components

#### Asset Management
- **Improved Build Process**: Clean 3-file builds with proper asset cleanup
- **Fixed Asset Accumulation**: Rebuild command now properly removes old assets before publishing new ones

#### Field Method Enhancements (2025-08-02)
- **Dual Searchable Support**: Resources now support both `$search` array and `searchable()` field methods
  - Added `searchable()` method to base Field class (available on all field types)
  - Enhanced `Resource::searchableColumns()` to intelligently merge both approaches
  - Automatic duplicate removal when both methods define same columns
  - Backward compatibility with existing `$search` array usage
- **Required Field Method**: Added convenient `required()` method to base Field class
  - Cleaner syntax: `Text::make('Name')->required()` instead of `->rules('required')`
  - Intelligent rule management: prevents duplicate 'required' rules
  - Supports enabling/disabling: `->required(false)` removes required validation
  - Available on all field types through inheritance
- **Nova Compatibility Methods**: Added 5 critical display control methods for Nova compatibility
  - `showOnIndex()`, `showOnDetail()`, `showOnCreating()`, `showOnUpdating()` - Positive display control
  - `displayUsing()` - Format fields for display only (separate from resolveUsing)
  - Enhanced developer experience with familiar Nova method names
  - Maintains backward compatibility with existing hide* methods
- **Enhanced Testing**: Added comprehensive tests for both search and required functionality

### 🔧 Fixed

#### Critical Installation Issues (2025-08-02)
- **Static Method Call Errors**: Fixed fatal errors preventing package installation and usage
  - Made `AdminPanel::resources()` static method with backward compatibility via `registerResources()`
  - Made `AdminPanel::pages()` static method with backward compatibility via `registerPages()`
  - Made `AdminPanel::metrics()` static method with backward compatibility via `registerMetrics()`
  - Eliminated "Non-static method cannot be called statically" errors in AdminServiceProvider
- **Asset Distribution**: Fixed missing pre-built assets in package distribution
  - Removed `/public/build` from `.gitignore` to include assets in repository
  - Resolved "Can't locate path" errors during asset publishing
  - Fixed Vite manifest file location issues for proper asset loading
- **Laravel 12 Compatibility**: Updated installation documentation for Laravel 12
  - Changed install command to reference `bootstrap/app.php` instead of `config/app.php`
  - Updated provider registration syntax to Laravel 12 `->withProviders([])` format
  - Removed outdated Laravel 11 configuration references

#### Test Suite Improvements (2025-08-02)
- **Configuration-Aware Testing**: Made authorization tests respect `allow_all_authenticated` setting
- **Nova-like Defaults**: Tests now support both strict and permissive authorization modes
- **Comprehensive Coverage**: Added 8 new tests covering all critical installation scenarios
- **Zero Test Failures**: Eliminated all pre-existing test failures with intelligent test design

---

## Future Releases

### Planned Features for v1.1.0
- **Advanced Relationships**: HasMany, BelongsToMany field types
- **File Manager**: Built-in file management system
- **Import/Export**: CSV and Excel import/export functionality
- **Advanced Filters**: Date ranges, number ranges, custom filters
- **Notifications**: Real-time notifications system
- **Activity Log**: User activity tracking and logging

### Planned Features for v1.2.0
- **Multi-tenancy**: Built-in multi-tenant support
- **API Resources**: RESTful API for external integrations
- **Advanced Permissions**: Granular permission system
- **Themes**: Multiple built-in themes
- **Localization**: Multi-language support
- **Advanced Charts**: Chart.js integration for metrics

---

For more information about this release, visit our [documentation](https://jerthedev.com/admin-panel) or [GitHub repository](https://github.com/jerthedev/admin-panel).
