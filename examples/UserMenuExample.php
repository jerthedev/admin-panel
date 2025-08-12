<?php

declare(strict_types=1);

/**
 * User Menu Example
 * 
 * This example demonstrates the complete AdminPanel::userMenu() functionality
 * including custom menu items, default logout preservation, and validation.
 */

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\Menu;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Support\AdminPanel;

// Example 1: Basic User Menu Customization
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        // Add user-specific profile link
        $menu->prepend(
            MenuItem::make("Profile ({$user->name})", "/admin/profile/{$user->id}")
                ->withIcon('user')
        );

        // Add settings link
        $menu->append(
            MenuItem::make('Account Settings', '/admin/settings')
                ->withIcon('cog')
        );
    }

    // Default logout link is automatically preserved at the end
    return $menu;
});

// Example 2: User Menu with Badges and Notifications
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        // Profile with dynamic badge
        $menu->prepend(
            MenuItem::make('My Profile', "/users/{$user->id}")
                ->withIcon('user')
        );

        // Messages with notification count
        $menu->append(
            MenuItem::make('Messages', '/messages')
                ->withIcon('mail')
                ->withBadge(function () use ($user) {
                    return $user->unreadMessages()->count();
                }, 'info')
        );

        // Notifications with conditional badge
        $menu->append(
            MenuItem::make('Notifications', '/notifications')
                ->withIcon('bell')
                ->withBadge(function () use ($user) {
                    $count = $user->unreadNotifications()->count();
                    return $count > 0 ? $count : null;
                }, 'warning')
        );
    }

    return $menu;
});

// Example 3: Role-Based User Menu
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        // Always show profile
        $menu->prepend(
            MenuItem::make('My Profile', "/profile/{$user->id}")
                ->withIcon('user')
        );

        // Admin-specific items
        if ($user->hasRole('admin')) {
            $menu->append(
                MenuItem::make('Admin Dashboard', '/admin/dashboard')
                    ->withIcon('shield')
                    ->withBadge('Admin', 'danger')
            );

            $menu->append(
                MenuItem::make('System Logs', '/admin/logs')
                    ->withIcon('document-text')
                    ->withBadge(function () {
                        return \Illuminate\Support\Facades\Log::getFiles()->count();
                    }, 'warning')
            );
        }

        // Manager-specific items
        if ($user->hasRole('manager')) {
            $menu->append(
                MenuItem::make('Team Reports', '/reports/team')
                    ->withIcon('chart-bar')
                    ->withBadge('Manager', 'info')
            );
        }

        // Subscription-based items
        if (method_exists($user, 'subscribed') && $user->subscribed('premium')) {
            $menu->append(
                MenuItem::make('Premium Features', '/premium')
                    ->withIcon('star')
                    ->withBadge('Pro', 'success')
            );
        }
    }

    return $menu;
});

// Example 4: User Menu with External Links
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        // Internal profile link
        $menu->prepend(
            MenuItem::make('Profile', "/users/{$user->id}")
                ->withIcon('user')
        );

        // External billing portal (for SaaS apps)
        if ($user->stripe_customer_id) {
            $menu->append(
                MenuItem::externalLink('Billing Portal', 'https://billing.stripe.com/session/create')
                    ->method('POST', ['customer_id' => $user->stripe_customer_id])
                    ->openInNewTab()
                    ->withIcon('credit-card')
                    ->withBadge('Billing', 'primary')
            );
        }

        // External support link
        $menu->append(
            MenuItem::externalLink('Help & Support', 'https://support.example.com')
                ->openInNewTab()
                ->withIcon('question-mark-circle')
        );

        // External documentation
        $menu->append(
            MenuItem::externalLink('Documentation', 'https://docs.example.com')
                ->openInNewTab()
                ->withIcon('book-open')
        );
    }

    return $menu;
});

// Example 5: Conditional User Menu Items
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        // Always show profile
        $menu->prepend(
            MenuItem::make('Profile', "/profile/{$user->id}")
                ->withIcon('user')
        );

        // Show different items based on user preferences
        $preferences = $user->preferences ?? [];

        if (in_array('notifications', $preferences)) {
            $menu->append(
                MenuItem::make('Notification Settings', '/settings/notifications')
                    ->withIcon('bell')
            );
        }

        if (in_array('privacy', $preferences)) {
            $menu->append(
                MenuItem::make('Privacy Settings', '/settings/privacy')
                    ->withIcon('shield-check')
            );
        }

        // Show API access for developers
        if ($user->hasPermission('api-access')) {
            $menu->append(
                MenuItem::make('API Keys', '/api/keys')
                    ->withIcon('key')
                    ->withBadge('Dev', 'info')
            );
        }

        // Show billing for paid users
        if ($user->subscription_status === 'active') {
            $menu->append(
                MenuItem::make('Subscription', '/subscription')
                    ->withIcon('credit-card')
                    ->withBadge('Active', 'success')
            );
        }
    }

    return $menu;
});

// Example 6: User Menu with Dynamic Content
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        // Profile with user avatar indicator
        $menu->prepend(
            MenuItem::make("Welcome, {$user->first_name}!", "/profile/{$user->id}")
                ->withIcon('user')
                ->withBadge($user->avatar ? '📷' : '👤', 'primary')
        );

        // Recent activity
        $menu->append(
            MenuItem::make('Recent Activity', '/activity')
                ->withIcon('clock')
                ->withBadge(function () use ($user) {
                    $recentCount = $user->activities()
                        ->where('created_at', '>=', now()->subHours(24))
                        ->count();
                    return $recentCount > 0 ? "Last 24h: {$recentCount}" : 'No recent activity';
                }, 'info')
        );

        // Storage usage
        $menu->append(
            MenuItem::make('Storage', '/storage')
                ->withIcon('server')
                ->withBadge(function () use ($user) {
                    $usedGB = round($user->storage_used / 1024 / 1024 / 1024, 1);
                    $limitGB = $user->storage_limit / 1024 / 1024 / 1024;
                    return "{$usedGB}GB / {$limitGB}GB";
                }, $user->storage_used > $user->storage_limit * 0.8 ? 'warning' : 'success')
        );
    }

    return $menu;
});

// Example 7: Validation Examples

// VALID: Only MenuItem objects
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $menu->append(MenuItem::make('Profile', '/profile'));
    $menu->append(MenuItem::make('Settings', '/settings'));
    return $menu;
});

// INVALID: MenuSection objects (will throw exception)
try {
    AdminPanel::userMenu(function (Request $request, Menu $menu) {
        $menu->append(\JTD\AdminPanel\Menu\MenuSection::make('Invalid Section'));
        return $menu;
    });
    
    $request = Request::create('/test');
    AdminPanel::resolveUserMenu($request); // This will throw InvalidArgumentException
} catch (InvalidArgumentException $e) {
    logger()->error('Invalid user menu configuration: ' . $e->getMessage());
}

// INVALID: MenuGroup objects (will throw exception)
try {
    AdminPanel::userMenu(function (Request $request, Menu $menu) {
        $menu->append(\JTD\AdminPanel\Menu\MenuGroup::make('Invalid Group'));
        return $menu;
    });
    
    $request = Request::create('/test');
    AdminPanel::resolveUserMenu($request); // This will throw InvalidArgumentException
} catch (InvalidArgumentException $e) {
    logger()->error('Invalid user menu configuration: ' . $e->getMessage());
}

// Example 8: Default Logout Link Preservation
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        // Add custom items
        $menu->prepend(MenuItem::make('Profile', "/profile/{$user->id}"));
        $menu->append(MenuItem::make('Settings', '/settings'));
        
        // Default "Sign out" link is automatically added at the end
        // unless you explicitly add your own logout link
    }

    return $menu;
    
    // Result will be:
    // 1. Profile
    // 2. Settings  
    // 3. Sign out (automatically added)
});

// Example 9: Custom Logout Link (Overrides Default)
AdminPanel::userMenu(function (Request $request, Menu $menu) {
    $user = $request->user();

    if ($user) {
        $menu->prepend(MenuItem::make('Profile', "/profile/{$user->id}"));
        
        // Custom logout link (prevents default from being added)
        $menu->append(
            MenuItem::make('Custom Logout', '/custom-logout')
                ->withIcon('arrow-right-on-rectangle')
                ->meta('method', 'post')
                ->meta('default', true) // Mark as default to prevent duplicate
        );
    }

    return $menu;
});
