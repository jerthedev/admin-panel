<?php

declare(strict_types=1);

/**
 * Custom Menu Example
 * 
 * This example demonstrates how to use the AdminPanel::mainMenu() and 
 * AdminPanel::userMenu() methods to completely customize the admin panel navigation.
 * 
 * Place this code in your AdminPanelServiceProvider or AppServiceProvider boot() method.
 */

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\Menu;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;

// Example 1: Basic Main Menu Customization
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Dashboard section with icon
        MenuSection::dashboard('MainDashboard')->icon('chart-bar'),

        // Business section with grouped items
        MenuSection::make('Business', [
            MenuItem::resource('UserResource'),
            MenuItem::resource('LicenseResource'),
            MenuItem::link('Refunds', '/admin/refunds'),
        ])->icon('briefcase')->collapsible(),

        // Content management section
        MenuSection::make('Content', [
            MenuItem::resource('PostResource'),
            MenuItem::resource('CategoryResource'),
            MenuItem::link('Media Library', '/admin/media'),
        ])->icon('document-text')->collapsible(),

        // External links section
        MenuSection::make('External', [
            MenuItem::externalLink('Stripe Dashboard', 'https://dashboard.stripe.com'),
            MenuItem::externalLink('Analytics', 'https://analytics.google.com'),
        ])->icon('external-link'),
    ];
});

// Example 2: Advanced Main Menu with Conditional Logic
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();
    $sections = [];

    // Always show dashboard
    $sections[] = MenuSection::dashboard('MainDashboard')->icon('chart-bar');

    // User management (always visible to authenticated users)
    if ($user) {
        $sections[] = MenuSection::make('Users', [
            MenuItem::resource('UserResource')
                ->withBadge(fn() => \App\Models\User::count(), 'info'),
            MenuItem::link('User Reports', '/admin/reports/users'),
        ])->icon('users');
    }

    // Admin-only sections
    if ($user && $user->is_admin) {
        $sections[] = MenuSection::make('Administration', [
            MenuItem::link('System Settings', '/admin/system')
                ->withIcon('cog'),
            MenuItem::link('Logs', '/admin/logs')
                ->withIcon('document-text')
                ->withBadge(fn() => \Illuminate\Support\Facades\Log::getFiles()->count(), 'warning'),
            MenuItem::link('Cache Management', '/admin/cache')
                ->withIcon('refresh'),
        ])->icon('shield')->collapsible();

        $sections[] = MenuSection::make('Monitoring', [
            MenuItem::externalLink('Server Status', 'https://status.example.com'),
            MenuItem::externalLink('Error Tracking', 'https://sentry.io'),
        ])->icon('chart-line');
    }

    // Feature flags or environment-specific sections
    if (config('app.env') === 'local') {
        $sections[] = MenuSection::make('Development', [
            MenuItem::link('API Documentation', '/docs/api'),
            MenuItem::link('Database Browser', '/admin/database'),
            MenuItem::externalLink('Telescope', '/telescope'),
        ])->icon('code')->withBadge('DEV', 'danger');
    }

    return $sections;
});

// Example 3: User Menu Customization
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        // Add user-specific items at the top
        $menu->prepend(
            MenuItem::make("Profile ({$user->name})", "/admin/profile/{$user->id}")
                ->withIcon('user')
        );

        // Add subscription info if applicable
        if (method_exists($user, 'subscribed') && $user->subscribed()) {
            $menu->append(
                MenuItem::make('Subscription', '/admin/subscription')
                    ->withIcon('credit-card')
                    ->withBadge('Pro', 'success')
            );
        }
    }

    // Standard menu items
    $menu->append(
        MenuItem::make('Settings', '/admin/settings')
            ->withIcon('cog')
    );

    $menu->append(
        MenuItem::make('Help & Support', '/admin/help')
            ->withIcon('question-mark-circle')
    );

    // Admin-specific items
    if ($user && $user->is_admin) {
        $menu->append(
            MenuItem::make('System Admin', '/admin/system')
                ->withIcon('shield')
                ->withBadge('Admin', 'danger')
        );
    }

    // Notifications with dynamic badge
    $menu->append(
        MenuItem::make('Notifications', '/admin/notifications')
            ->withIcon('bell')
            ->withBadge(function () use ($user) {
                if ($user) {
                    return $user->unreadNotifications()->count();
                }
                return 0;
            }, 'info')
    );

    return $menu;
});

// Example 4: Complex Menu with Authorization and Groups
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();
    
    return [
        // Dashboard (always visible)
        MenuSection::make('Overview', [
            MenuItem::dashboard('MainDashboard'),
            MenuItem::link('Quick Stats', '/admin/stats'),
        ])->icon('chart-bar'),

        // Customer Management
        MenuSection::make('Customers', [
            MenuItem::resource('UserResource'),
            MenuItem::resource('CompanyResource'),
            MenuItem::link('Customer Support', '/admin/support'),
        ])->icon('users')
          ->canSee(fn() => $user && $user->can('manage-customers'))
          ->withBadge(fn() => \App\Models\User::whereDate('created_at', today())->count(), 'success'),

        // Product Management
        MenuSection::make('Products', [
            MenuItem::resource('ProductResource'),
            MenuItem::resource('CategoryResource'),
            MenuItem::link('Inventory', '/admin/inventory')
                ->withBadge(fn() => \App\Models\Product::where('stock', '<', 10)->count(), 'warning'),
        ])->icon('shopping-bag')
          ->canSee(fn() => $user && $user->can('manage-products'))
          ->collapsible(),

        // Financial
        MenuSection::make('Financial', [
            MenuItem::resource('OrderResource'),
            MenuItem::resource('InvoiceResource'),
            MenuItem::link('Revenue Reports', '/admin/reports/revenue'),
            MenuItem::externalLink('Stripe Dashboard', 'https://dashboard.stripe.com'),
        ])->icon('currency-dollar')
          ->canSee(fn() => $user && $user->can('view-financial'))
          ->collapsible(),

        // System Administration (admin only)
        MenuSection::make('System', [
            MenuItem::link('User Permissions', '/admin/permissions'),
            MenuItem::link('System Logs', '/admin/logs'),
            MenuItem::link('Backup Management', '/admin/backups'),
            MenuItem::link('API Keys', '/admin/api-keys'),
        ])->icon('cog')
          ->canSee(fn() => $user && $user->hasRole('super-admin'))
          ->collapsible()
          ->withBadge('Admin', 'danger'),
    ];
});

// Example 5: Menu with Dynamic Content Based on User Preferences
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();
    $preferences = $user ? $user->admin_preferences ?? [] : [];
    
    $sections = [
        MenuSection::dashboard('MainDashboard')->icon('chart-bar'),
    ];

    // Add sections based on user preferences
    if (in_array('sales', $preferences)) {
        $sections[] = MenuSection::make('Sales', [
            MenuItem::resource('OrderResource'),
            MenuItem::link('Sales Reports', '/admin/reports/sales'),
        ])->icon('trending-up');
    }

    if (in_array('marketing', $preferences)) {
        $sections[] = MenuSection::make('Marketing', [
            MenuItem::resource('CampaignResource'),
            MenuItem::link('Email Templates', '/admin/emails'),
        ])->icon('megaphone');
    }

    if (in_array('support', $preferences)) {
        $sections[] = MenuSection::make('Support', [
            MenuItem::resource('TicketResource'),
            MenuItem::link('Knowledge Base', '/admin/kb'),
        ])->icon('support');
    }

    // Always add settings
    $sections[] = MenuSection::make('Settings', [
        MenuItem::link('Preferences', '/admin/preferences'),
        MenuItem::link('Account', '/admin/account'),
    ])->icon('cog');

    return $sections;
});
