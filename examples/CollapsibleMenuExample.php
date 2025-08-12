<?php

declare(strict_types=1);

/**
 * Collapsible Menu Example
 * 
 * This example demonstrates the complete collapsible menu functionality
 * including sections, groups, state persistence, and validation.
 */

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;

// Example 1: Basic Collapsible Sections
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Non-collapsible section with direct path
        MenuSection::make('Dashboard')
            ->path('/admin/dashboard')
            ->icon('chart-bar'),

        // Collapsible section (expanded by default)
        MenuSection::make('User Management', [
            MenuItem::resource('UserResource'),
            MenuItem::resource('RoleResource'),
            MenuItem::link('User Analytics', '/analytics/users'),
        ])->collapsible()
          ->icon('users'),

        // Collapsible section (collapsed by default)
        MenuSection::make('System Administration', [
            MenuItem::link('System Settings', '/admin/settings'),
            MenuItem::link('Cache Management', '/admin/cache'),
            MenuItem::link('Log Viewer', '/admin/logs'),
        ])->collapsible()
          ->collapsed()
          ->icon('cog'),
    ];
});

// Example 2: Collapsible Groups within Sections
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Business Management', [
            // Non-collapsible group
            MenuGroup::make('Quick Actions', [
                MenuItem::link('New Order', '/orders/create'),
                MenuItem::link('New Customer', '/customers/create'),
            ]),

            // Collapsible group (expanded)
            MenuGroup::make('Licensing', [
                MenuItem::resource('LicenseResource'),
                MenuItem::link('License Reports', '/reports/licenses'),
                MenuItem::link('Renewal Notifications', '/licenses/renewals'),
            ])->collapsible(),

            // Collapsible group (collapsed)
            MenuGroup::make('Financial Reports', [
                MenuItem::link('Revenue Reports', '/reports/revenue'),
                MenuItem::link('Tax Reports', '/reports/taxes'),
                MenuItem::externalLink('Stripe Dashboard', 'https://dashboard.stripe.com'),
            ])->collapsible()
              ->collapsed(),
        ])->icon('briefcase'),
    ];
});

// Example 3: Custom State IDs for Persistence
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Section with custom state ID
        MenuSection::make('Content Management', [
            MenuGroup::make('Posts', [
                MenuItem::resource('PostResource'),
                MenuItem::link('Post Categories', '/posts/categories'),
                MenuItem::link('Post Analytics', '/analytics/posts'),
            ])->collapsible()
              ->stateId('cms_posts_group'),

            MenuGroup::make('Media', [
                MenuItem::link('Media Library', '/media'),
                MenuItem::link('File Manager', '/files'),
                MenuItem::link('Storage Usage', '/storage'),
            ])->collapsible()
              ->collapsed()
              ->stateId('cms_media_group'),
        ])->collapsible()
          ->stateId('content_management_section')
          ->icon('document-text'),

        // Another section with custom state ID
        MenuSection::make('E-commerce', [
            MenuItem::resource('ProductResource'),
            MenuItem::resource('OrderResource'),
            MenuItem::link('Inventory', '/inventory'),
        ])->collapsible()
          ->stateId('ecommerce_section')
          ->icon('shopping-cart'),
    ];
});

// Example 4: Complex Nested Structure
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Dashboard (always visible, non-collapsible)
        MenuSection::make('Overview')
            ->path('/admin/dashboard')
            ->icon('home'),

        // Business Intelligence (collapsible section with groups)
        MenuSection::make('Business Intelligence', [
            MenuGroup::make('Sales Analytics', [
                MenuItem::dashboard('SalesDashboard'),
                MenuItem::lens('OrderResource', 'HighValueOrders'),
                MenuItem::link('Sales Forecasting', '/analytics/sales/forecast'),
            ])->collapsible()
              ->stateId('bi_sales_group'),

            MenuGroup::make('Customer Analytics', [
                MenuItem::lens('UserResource', 'TopCustomers'),
                MenuItem::link('Customer Segmentation', '/analytics/customers/segments'),
                MenuItem::link('Churn Analysis', '/analytics/customers/churn'),
            ])->collapsible()
              ->collapsed()
              ->stateId('bi_customers_group'),

            MenuGroup::make('Product Analytics', [
                MenuItem::link('Product Performance', '/analytics/products'),
                MenuItem::link('Inventory Turnover', '/analytics/inventory'),
                MenuItem::externalLink('Google Analytics', 'https://analytics.google.com'),
            ])->collapsible()
              ->stateId('bi_products_group'),
        ])->collapsible()
          ->stateId('business_intelligence_section')
          ->icon('chart-line'),

        // Operations (collapsible section)
        MenuSection::make('Operations', [
            MenuGroup::make('Order Management', [
                MenuItem::resource('OrderResource'),
                MenuItem::filter('Pending Orders', 'OrderResource')
                    ->applies('StatusFilter', 'pending'),
                MenuItem::link('Shipping Labels', '/orders/shipping'),
            ])->collapsible()
              ->stateId('ops_orders_group'),

            MenuGroup::make('Inventory Management', [
                MenuItem::resource('ProductResource'),
                MenuItem::filter('Low Stock Items', 'ProductResource')
                    ->applies('StockFilter', 'low'),
                MenuItem::link('Reorder Reports', '/inventory/reorder'),
            ])->collapsible()
              ->collapsed()
              ->stateId('ops_inventory_group'),
        ])->collapsible()
          ->collapsed()
          ->stateId('operations_section')
          ->icon('truck'),

        // System (non-collapsible section for critical items)
        MenuSection::make('System', [
            MenuItem::link('System Health', '/system/health'),
            MenuItem::link('Backup Status', '/system/backups'),
            MenuItem::externalLink('Server Monitoring', 'https://monitoring.example.com'),
        ])->icon('server'),
    ];
});

// Example 5: Conditional Collapsible State
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();
    
    return [
        // Admin section - collapsed for non-admins, expanded for admins
        MenuSection::make('Administration', [
            MenuItem::link('User Management', '/admin/users'),
            MenuItem::link('System Settings', '/admin/settings'),
            MenuItem::link('Audit Logs', '/admin/logs'),
        ])->collapsible()
          ->collapsed(!($user && $user->is_admin)) // Expanded for admins
          ->stateId('admin_section')
          ->canSee(fn($req) => $req->user()?->can('access-admin'))
          ->icon('shield'),

        // Reports section - state based on user preference
        MenuSection::make('Reports', [
            MenuGroup::make('Financial Reports', [
                MenuItem::link('Revenue', '/reports/revenue'),
                MenuItem::link('Expenses', '/reports/expenses'),
            ])->collapsible()
              ->collapsed(!($user && in_array('finance', $user->preferences ?? [])))
              ->stateId('finance_reports_group'),

            MenuGroup::make('Operational Reports', [
                MenuItem::link('Performance', '/reports/performance'),
                MenuItem::link('Usage', '/reports/usage'),
            ])->collapsible()
              ->collapsed(!($user && in_array('operations', $user->preferences ?? [])))
              ->stateId('ops_reports_group'),
        ])->collapsible()
          ->stateId('reports_section')
          ->icon('document-chart-bar'),
    ];
});

// Example 6: State Persistence Best Practices
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Use descriptive, stable state IDs
        MenuSection::make('Customer Relationship Management', [
            MenuGroup::make('Contacts', [
                MenuItem::resource('ContactResource'),
                MenuItem::link('Contact Import', '/contacts/import'),
            ])->collapsible()
              ->stateId('crm_contacts'), // Short, descriptive ID

            MenuGroup::make('Communications', [
                MenuItem::link('Email Templates', '/emails/templates'),
                MenuItem::link('SMS Templates', '/sms/templates'),
                MenuItem::link('Campaign Manager', '/campaigns'),
            ])->collapsible()
              ->collapsed()
              ->stateId('crm_communications'),

            MenuGroup::make('Analytics', [
                MenuItem::link('Contact Analytics', '/analytics/contacts'),
                MenuItem::link('Campaign Performance', '/analytics/campaigns'),
            ])->collapsible()
              ->stateId('crm_analytics'),
        ])->collapsible()
          ->stateId('crm_main') // Stable ID that won't change
          ->icon('users'),

        // Avoid auto-generated IDs for important sections
        MenuSection::make('Integration Hub', [
            MenuItem::externalLink('Zapier', 'https://zapier.com/dashboard'),
            MenuItem::externalLink('Slack', 'https://slack.com/apps'),
            MenuItem::link('API Documentation', '/docs/api'),
        ])->collapsible()
          ->stateId('integrations') // Custom ID for consistency
          ->icon('puzzle-piece'),
    ];
});

// Example 7: Validation Examples (These will throw exceptions)

// INVALID: Collapsible section with path
try {
    MenuSection::make('Invalid Section')
        ->collapsible()
        ->path('/invalid'); // This will throw InvalidArgumentException
} catch (InvalidArgumentException $e) {
    // Handle validation error
    logger()->warning('Invalid menu configuration: ' . $e->getMessage());
}

// INVALID: Section with path made collapsible
try {
    MenuSection::make('Another Invalid Section')
        ->path('/another-invalid')
        ->collapsible(); // This will throw InvalidArgumentException
} catch (InvalidArgumentException $e) {
    // Handle validation error
    logger()->warning('Invalid menu configuration: ' . $e->getMessage());
}

// VALID: Proper separation of concerns
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Direct link sections (non-collapsible)
        MenuSection::make('Quick Dashboard')
            ->path('/admin/dashboard')
            ->icon('zap'),

        // Container sections (collapsible)
        MenuSection::make('Management Tools', [
            MenuItem::resource('UserResource'),
            MenuItem::resource('OrderResource'),
            MenuItem::link('Reports', '/reports'),
        ])->collapsible()
          ->icon('tools'),
    ];
});
