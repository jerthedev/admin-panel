<?php

declare(strict_types=1);

/**
 * MenuItem Factory Methods Example
 *
 * This example demonstrates all the available MenuItem factory methods
 * and their enhanced functionality for creating Nova-compatible menu items.
 */

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;

// Example 1: Basic Factory Methods
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Navigation Examples', [
            // Internal link
            MenuItem::link('Dashboard', '/admin/dashboard')
                ->withIcon('chart-bar'),

            // Resource link
            MenuItem::resource('UserResource')
                ->withIcon('users')
                ->withBadge(fn() => \App\Models\User::count(), 'info'),

            // Dashboard link
            MenuItem::dashboard('AnalyticsDashboard')
                ->withIcon('chart-line'),

            // External link
            MenuItem::externalLink('Documentation', 'https://docs.example.com')
                ->withIcon('book-open')
                ->openInNewTab(),
        ])->icon('navigation'),
    ];
});

// Example 2: Advanced Factory Methods
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Advanced Examples', [
            // Lens link (future compatibility)
            MenuItem::lens('UserResource', 'MostValuableUsers')
                ->withIcon('star')
                ->withBadge('Top', 'success'),

            // Filtered resource link with URL parameters
            MenuItem::filter('Active Users', 'UserResource')
                ->applies('StatusFilter', 'active')
                ->withIcon('users')
                ->withBadge(fn() => \App\Models\User::where('status', 'active')->count(), 'success'),
                // URL: /admin/resources/users?filters[status]=active

            // Complex filtered resource with multiple filters
            MenuItem::filter('Premium Laravel Users', 'UserResource')
                ->applies('EmailFilter', '@laravel.com')
                ->applies('SubscriptionFilter', 'premium')
                ->applies('ColumnFilter', 'verified', ['column' => 'email_verified_at'])
                ->withIcon('shield-check')
                ->withBadge('VIP', 'warning'),
                // URL: /admin/resources/users?filters[email]=@laravel.com&filters[subscription]=premium&filters[column_email_verified_at]=verified
        ])->icon('star'),
    ];
});

// Example 3: External Links with HTTP Methods
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('API Examples', [
            // Simple external link
            MenuItem::externalLink('Stripe Dashboard', 'https://dashboard.stripe.com')
                ->openInNewTab()
                ->withIcon('credit-card'),

            // External link with POST method
            MenuItem::externalLink('Logout', 'https://api.example.com/logout')
                ->method('POST')
                ->withIcon('logout'),

            // External link with data and headers
            MenuItem::externalLink('API Action', 'https://api.example.com/action')
                ->method('POST', [
                    'action' => 'sync',
                    'timestamp' => time(),
                ], [
                    'Authorization' => 'Bearer ' . config('services.api.token'),
                    'Content-Type' => 'application/json',
                ])
                ->withIcon('refresh')
                ->withBadge('Sync', 'info'),

            // External link with PUT method
            MenuItem::externalLink('Update Status', 'https://api.example.com/status')
                ->method('PUT', ['status' => 'active'])
                ->openInNewTab(false) // Explicitly don't open in new tab
                ->withIcon('check-circle'),
        ])->icon('globe'),
    ];
});

// Example 4: Authorization Examples
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();

    return [
        MenuSection::make('Authorized Examples', [
            // Always visible
            MenuItem::link('Public Dashboard', '/dashboard')
                ->withIcon('home'),

            // User-based authorization
            MenuItem::link('User Profile', '/profile')
                ->canSee(function ($request) {
                    return $request->user() !== null;
                })
                ->withIcon('user'),

            // Admin-only items
            MenuItem::link('Admin Panel', '/admin/system')
                ->canSee(fn($request) => $request->user()?->is_admin)
                ->withIcon('shield')
                ->withBadge('Admin', 'danger'),

            // Permission-based authorization
            MenuItem::resource('UserResource')
                ->canSee(function ($request) {
                    return $request->user()?->can('manage-users');
                })
                ->withIcon('users'),

            // Complex authorization logic
            MenuItem::filter('Financial Reports', 'OrderResource')
                ->applies('DateFilter', 'last_month')
                ->canSee(function ($request) {
                    $user = $request->user();
                    return $user && (
                        $user->hasRole('finance') ||
                        $user->hasRole('admin') ||
                        $user->can('view-financial-reports')
                    );
                })
                ->withIcon('chart-bar')
                ->withBadge('Restricted', 'warning'),
        ])->icon('lock'),
    ];
});

// Example 5: Complex Menu with All Features
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();

    return [
        // Business Intelligence Section
        MenuSection::make('Business Intelligence', [
            // Dashboard with conditional badge
            MenuItem::dashboard('SalesDashboard')
                ->withIcon('trending-up')
                ->withBadge(function () {
                    $todaySales = \App\Models\Order::whereDate('created_at', today())->count();
                    return $todaySales > 0 ? $todaySales : null;
                }, 'success')
                ->canSee(fn($req) => $req->user()?->can('view-sales')),

            // Lens with dynamic content
            MenuItem::lens('UserResource', 'HighValueCustomers')
                ->withIcon('star')
                ->withBadge(fn() => 'Top ' . \App\Models\User::where('lifetime_value', '>', 10000)->count(), 'warning')
                ->canSee(fn($req) => $req->user()?->hasRole(['sales', 'admin'])),

            // Complex filtered view
            MenuItem::filter('Recent High-Value Orders', 'OrderResource')
                ->applies('AmountFilter', '1000', ['operator' => '>='])
                ->applies('DateFilter', 'last_7_days')
                ->applies('StatusFilter', 'completed')
                ->withIcon('currency-dollar')
                ->withBadge(function () {
                    return \App\Models\Order::where('amount', '>=', 1000)
                        ->where('created_at', '>=', now()->subDays(7))
                        ->where('status', 'completed')
                        ->count();
                }, 'info')
                ->canSee(fn($req) => $req->user()?->can('view-orders')),
        ])->icon('chart-bar')->collapsible(),

        // External Integrations Section
        MenuSection::make('External Tools', [
            // Stripe Dashboard
            MenuItem::externalLink('Stripe Dashboard', 'https://dashboard.stripe.com')
                ->openInNewTab()
                ->withIcon('credit-card')
                ->canSee(fn($req) => $req->user()?->can('manage-payments')),

            // Analytics with custom data
            MenuItem::externalLink('Google Analytics', 'https://analytics.google.com')
                ->openInNewTab()
                ->withIcon('chart-line')
                ->withMeta(['category' => 'analytics', 'priority' => 'high']),

            // API webhook trigger
            MenuItem::externalLink('Sync Data', config('services.webhook.sync_url'))
                ->method('POST', [
                    'source' => 'admin_panel',
                    'user_id' => $user?->id,
                    'timestamp' => now()->toISOString(),
                ], [
                    'Authorization' => 'Bearer ' . config('services.webhook.token'),
                    'X-Source' => 'admin-panel',
                ])
                ->withIcon('refresh')
                ->withBadge('Sync', 'primary')
                ->canSee(fn($req) => $req->user()?->hasRole('admin')),
        ])->icon('external-link')->collapsible(),

        // Development Tools (environment-specific)
        MenuSection::make('Development', [
            MenuItem::externalLink('Telescope', '/telescope')
                ->openInNewTab()
                ->withIcon('search')
                ->withBadge('DEV', 'danger'),

            MenuItem::externalLink('Horizon', '/horizon')
                ->openInNewTab()
                ->withIcon('clock')
                ->withBadge('Queue', 'info'),

            MenuItem::link('API Documentation', '/docs/api')
                ->withIcon('book-open'),
        ])->icon('code')
          ->withBadge('DEV', 'danger')
          ->canSee(fn($req) => config('app.env') === 'local')
          ->collapsible(),
    ];
});

// Example 6: User Menu with Factory Methods
AdminPanel::userMenu(function (Request $request, \JTD\AdminPanel\Menu\Menu $menu) {
    $user = $request->user();

    if ($user) {
        // User profile with dynamic label
        $menu->prepend(
            MenuItem::link("Profile ({$user->name})", "/admin/users/{$user->id}")
                ->withIcon('user')
        );

        // Subscription management (if applicable)
        if (method_exists($user, 'subscribed') && $user->subscribed()) {
            $menu->append(
                MenuItem::externalLink('Billing Portal', 'https://billing.stripe.com/session/create')
                    ->method('POST', ['customer_id' => $user->stripe_id])
                    ->openInNewTab()
                    ->withIcon('credit-card')
                    ->withBadge('Pro', 'success')
            );
        }
    }

    // Settings
    $menu->append(
        MenuItem::link('Settings', '/admin/settings')
            ->withIcon('cog')
    );

    // Help with external link
    $menu->append(
        MenuItem::externalLink('Help & Support', 'https://support.example.com')
            ->openInNewTab()
            ->withIcon('question-mark-circle')
    );

    // Admin tools (conditional)
    if ($user?->is_admin) {
        $menu->append(
            MenuItem::filter('System Logs', 'LogResource')
                ->applies('LevelFilter', 'error')
                ->withIcon('exclamation-triangle')
                ->withBadge(function () {
                    // Count error logs from last 24 hours
                    return \Illuminate\Support\Facades\Log::getFiles()
                        ->filter(fn($file) => str_contains($file, 'error'))
                        ->count();
                }, 'danger')
        );
    }

    return $menu;
});

// Example 8: Enhanced Filtered Resource Examples
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Filtered Resources', [
            // Basic single filter with URL parameters
            MenuItem::filter('Active Users', 'UserResource')
                ->applies('StatusFilter', 'active')
                ->withIcon('users')
                ->withBadge(fn() => 'Active', 'success'),
                // Generates: /admin/resources/users?filters[status]=active

            // Multiple filters
            MenuItem::filter('Premium Active Users', 'UserResource')
                ->applies('StatusFilter', 'active')
                ->applies('SubscriptionFilter', 'premium')
                ->withIcon('star')
                ->withBadge('Premium', 'warning'),
                // Generates: /admin/resources/users?filters[status]=active&filters[subscription]=premium

            // Filter with constructor parameters
            MenuItem::filter('High Value Orders', 'OrderResource')
                ->applies('AmountFilter', '1000', ['operator' => '>='])
                ->applies('StatusFilter', 'completed')
                ->withIcon('currency-dollar')
                ->withBadge('High Value', 'info'),
                // Generates: /admin/resources/orders?filters[amount_>=]=1000&filters[status]=completed

            // Complex filter with multiple parameters
            MenuItem::filter('Recent Premium Subscriptions', 'SubscriptionResource')
                ->applies('DateFilter', 'last_30_days', ['field' => 'created_at'])
                ->applies('TypeFilter', 'premium', ['column' => 'subscription_type'])
                ->applies('StatusFilter', 'active')
                ->withIcon('credit-card')
                ->withBadge('Recent', 'primary'),
                // Generates: /admin/resources/subscriptions?filters[date_created_at]=last_30_days&filters[type_subscription_type]=premium&filters[status]=active

            // Email domain filter
            MenuItem::filter('Company Users', 'UserResource')
                ->applies('EmailDomainFilter', '@company.com')
                ->withIcon('building')
                ->withBadge('Internal', 'secondary'),
                // Generates: /admin/resources/users?filters[email_domain]=@company.com

            // Date range filter
            MenuItem::filter('This Month Orders', 'OrderResource')
                ->applies('DateRangeFilter', 'this_month', ['start_field' => 'created_at', 'end_field' => 'created_at'])
                ->withIcon('calendar')
                ->withBadge('Monthly', 'info'),
                // Generates: /admin/resources/orders?filters[date_range_created_at_created_at]=this_month

        ])->icon('filter'),
    ];
});

// Example 9: Filtered Resources with Authorization
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Admin Filtered Views', [
            // Admin-only filtered view
            MenuItem::filter('Admin Users', 'UserResource')
                ->applies('RoleFilter', 'admin')
                ->canSee(fn($req) => $req->user()?->hasRole('admin'))
                ->withIcon('shield')
                ->withBadge('Admin', 'danger'),

            // Manager-level filtered view
            MenuItem::filter('Team Performance', 'UserResource')
                ->applies('TeamFilter', 'current_user_team')
                ->applies('PerformanceFilter', 'above_average')
                ->canSee(fn($req) => $req->user()?->hasRole(['manager', 'admin']))
                ->withIcon('chart-bar')
                ->withBadge('Team', 'success'),

            // Financial data (restricted access)
            MenuItem::filter('High Revenue Orders', 'OrderResource')
                ->applies('AmountFilter', '10000', ['operator' => '>='])
                ->applies('StatusFilter', 'completed')
                ->canSee(fn($req) => $req->user()?->can('view-financial-data'))
                ->withIcon('currency-dollar')
                ->withBadge('Financial', 'warning'),

        ])->icon('lock'),
    ];
});
