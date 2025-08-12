<?php

declare(strict_types=1);

/**
 * Badge System Example
 * 
 * This example demonstrates the complete badge system functionality
 * including static badges, dynamic badges, conditional badges, and caching.
 */

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\Badge;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;

// Example 1: Basic Badge Types
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Badge Types', [
            // Static badges with different types
            MenuItem::link('Primary Badge', '/primary')
                ->withBadge('Primary', 'primary'),
            
            MenuItem::link('Success Badge', '/success')
                ->withBadge('Success', 'success'),
            
            MenuItem::link('Warning Badge', '/warning')
                ->withBadge('Warning', 'warning'),
            
            MenuItem::link('Danger Badge', '/danger')
                ->withBadge('Danger', 'danger'),
            
            MenuItem::link('Info Badge', '/info')
                ->withBadge('Info', 'info'),
            
            MenuItem::link('Secondary Badge', '/secondary')
                ->withBadge('Secondary', 'secondary'),
        ])->icon('tag'),
    ];
});

// Example 2: Dynamic Badges with Closures
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Dynamic Badges', [
            // Simple closure badge
            MenuItem::resource('UserResource')
                ->withBadge(function () {
                    return \App\Models\User::count();
                }, 'info'),

            // Badge with request context
            MenuItem::link('Current User', '/profile')
                ->withBadge(function ($request) {
                    $user = $request->user();
                    return $user ? $user->name : 'Guest';
                }, 'primary'),

            // Complex calculation badge
            MenuItem::link('Revenue Today', '/revenue')
                ->withBadge(function () {
                    $revenue = \App\Models\Order::whereDate('created_at', today())
                        ->sum('amount');
                    return '$' . number_format($revenue);
                }, 'success'),

            // Badge with time-based content
            MenuItem::link('System Status', '/status')
                ->withBadge(function () {
                    $uptime = now()->diffInHours(cache('system_start_time', now()));
                    return $uptime . 'h uptime';
                }, 'info'),
        ])->icon('chart-bar'),
    ];
});

// Example 3: Badge Instances with Custom Logic
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Badge Instances', [
            // Static Badge instance
            MenuItem::link('Featured', '/featured')
                ->withBadge(Badge::make('Featured', 'warning')),

            // Dynamic Badge instance
            MenuItem::link('Live Orders', '/orders')
                ->withBadge(Badge::make(function () {
                    return \App\Models\Order::where('status', 'processing')->count();
                }, 'danger')),

            // Badge instance with caching
            MenuItem::link('Expensive Calculation', '/expensive')
                ->withBadge(Badge::make(function () {
                    // Simulate expensive operation
                    sleep(1);
                    return 'Calculated: ' . rand(1, 100);
                }, 'info')->cache(300)), // Cache for 5 minutes
        ])->icon('star'),
    ];
});

// Example 4: Conditional Badges
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Conditional Badges', [
            // Simple conditional badge
            MenuItem::link('Admin Panel', '/admin')
                ->withBadgeIf('Admin', 'danger', function ($request) {
                    return $request->user()?->is_admin;
                }),

            // Conditional badge with complex logic
            MenuItem::resource('OrderResource')
                ->withBadgeIf('Urgent', 'warning', function ($request) {
                    $urgentOrders = \App\Models\Order::where('priority', 'urgent')
                        ->where('status', 'pending')
                        ->count();
                    return $urgentOrders > 0;
                }),

            // Multiple conditional badges (last one wins)
            MenuItem::link('Notifications', '/notifications')
                ->withBadge('Default', 'primary')
                ->withBadgeIf('New Messages', 'info', function ($request) {
                    return $request->user()?->unreadMessages()->count() > 0;
                })
                ->withBadgeIf('Urgent!', 'danger', function ($request) {
                    return $request->user()?->unreadMessages()
                        ->where('priority', 'urgent')->count() > 0;
                }),

            // Conditional badge with closure value
            MenuItem::link('Tasks', '/tasks')
                ->withBadgeIf(function ($request) {
                    $count = $request->user()?->tasks()->incomplete()->count() ?? 0;
                    return $count > 0 ? $count . ' pending' : null;
                }, 'warning', function ($request) {
                    return $request->user() !== null;
                }),
        ])->icon('bell'),
    ];
});

// Example 5: Badge Caching for Performance
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Cached Badges', [
            // Cache expensive database queries
            MenuItem::link('User Analytics', '/analytics/users')
                ->withBadge(function () {
                    // Expensive analytics calculation
                    $activeUsers = \App\Models\User::where('last_login_at', '>=', now()->subDays(30))->count();
                    $totalUsers = \App\Models\User::count();
                    $percentage = round(($activeUsers / $totalUsers) * 100);
                    return $percentage . '% active';
                }, 'success')
                ->cacheBadge(3600), // Cache for 1 hour

            // Cache API calls
            MenuItem::externalLink('External Service', 'https://api.example.com')
                ->withBadge(function () {
                    // Simulate API call
                    $response = file_get_contents('https://api.example.com/status');
                    $data = json_decode($response, true);
                    return $data['status'] ?? 'Unknown';
                }, 'info')
                ->cacheBadge(300), // Cache for 5 minutes

            // Cache with different TTLs based on content
            MenuItem::link('System Health', '/health')
                ->withBadge(function () {
                    $errors = \Illuminate\Support\Facades\Log::getFiles()
                        ->filter(fn($file) => str_contains($file, 'error'))
                        ->count();
                    return $errors > 0 ? $errors . ' errors' : 'Healthy';
                }, $errors > 0 ? 'danger' : 'success')
                ->cacheBadge(60), // Cache for 1 minute
        ])->icon('zap'),
    ];
});

// Example 6: Section Badges with Conditional Logic
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();
    
    return [
        // Section with static badge
        MenuSection::make('Dashboard')
            ->withBadge('Live', 'success')
            ->icon('chart-bar'),

        // Section with dynamic badge
        MenuSection::make('Orders', [
            MenuItem::resource('OrderResource'),
            MenuItem::link('Order Reports', '/reports/orders'),
        ])->withBadge(function () {
            return \App\Models\Order::whereDate('created_at', today())->count() . ' today';
        }, 'info')
          ->cacheBadge(300)
          ->icon('shopping-cart'),

        // Section with conditional badge
        MenuSection::make('Administration', [
            MenuItem::link('User Management', '/admin/users'),
            MenuItem::link('System Settings', '/admin/settings'),
        ])->withBadgeIf('Admin', 'danger', function ($request) {
            return $request->user()?->hasRole('admin');
        })
          ->canSee(fn($req) => $req->user()?->can('access-admin'))
          ->icon('shield'),

        // Section with complex badge logic
        MenuSection::make('Reports', [
            MenuItem::link('Sales Reports', '/reports/sales'),
            MenuItem::link('User Reports', '/reports/users'),
        ])->withBadge(function ($request) {
            $user = $request->user();
            if (!$user) return null;
            
            $pendingReports = \App\Models\Report::where('status', 'pending')
                ->where('assigned_to', $user->id)
                ->count();
                
            return $pendingReports > 0 ? $pendingReports . ' pending' : 'Up to date';
        }, function ($request) {
            $user = $request->user();
            if (!$user) return 'primary';
            
            $pendingReports = \App\Models\Report::where('status', 'pending')
                ->where('assigned_to', $user->id)
                ->count();
                
            return $pendingReports > 5 ? 'danger' : ($pendingReports > 0 ? 'warning' : 'success');
        })
          ->cacheBadge(120) // Cache for 2 minutes
          ->icon('document-text'),
    ];
});

// Example 7: Badge Performance Optimization
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Performance Optimized', [
            // Batch database queries for multiple badges
            MenuItem::link('Users Overview', '/users/overview')
                ->withBadge(function () {
                    // Single query for multiple metrics
                    $stats = \App\Models\User::selectRaw('
                        COUNT(*) as total,
                        COUNT(CASE WHEN last_login_at >= ? THEN 1 END) as active
                    ', [now()->subDays(30)])->first();
                    
                    return $stats->active . '/' . $stats->total . ' active';
                }, 'info')
                ->cacheBadge(1800), // Cache for 30 minutes

            // Use cached values when possible
            MenuItem::link('Quick Stats', '/stats')
                ->withBadge(function () {
                    return cache()->remember('quick_stats_badge', 600, function () {
                        // Expensive calculation only runs every 10 minutes
                        return 'Stats: ' . \App\Models\Order::sum('amount');
                    });
                }),

            // Conditional caching based on user role
            MenuItem::link('Admin Dashboard', '/admin/dashboard')
                ->withBadge(function ($request) {
                    return 'Admin: ' . \App\Models\User::where('is_admin', true)->count();
                }, 'danger')
                ->cacheBadge($request->user()?->is_admin ? 3600 : 60), // Longer cache for admins
        ])->icon('lightning-bolt'),
    ];
});
