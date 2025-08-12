<?php

declare(strict_types=1);

/**
 * Menu Authorization Example
 * 
 * This example demonstrates the complete menu authorization functionality
 * including canSee() methods, filtering, caching, and performance optimization.
 */

use Illuminate\Http\Request;
use JTD\AdminPanel\Menu\MenuGroup;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;

// Example 1: Basic Authorization with canSee()
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Public Section', [
            MenuItem::make('Public Item', '/public'),
            
            MenuItem::make('User Only Item', '/user-only')
                ->canSee(function ($request) {
                    return $request->user() !== null;
                }),
            
            MenuItem::make('Admin Only Item', '/admin-only')
                ->canSee(function ($request) {
                    return $request->user() && $request->user()->is_admin;
                }),
        ]),
    ];
});

// Example 2: Section and Group Authorization
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Section visible only to authenticated users
        MenuSection::make('User Management', [
            MenuItem::resource('UserResource'),
            MenuItem::link('User Reports', '/reports/users'),
        ])->canSee(function ($request) {
            return $request->user() !== null;
        }),

        // Section visible only to admins
        MenuSection::make('System Administration', [
            MenuItem::link('System Settings', '/admin/settings'),
            MenuItem::link('Audit Logs', '/admin/logs'),
            
            MenuGroup::make('Advanced Tools', [
                MenuItem::link('Database Console', '/admin/database'),
                MenuItem::link('Cache Management', '/admin/cache'),
            ])->canSee(function ($request) {
                return $request->user() && $request->user()->hasRole('super-admin');
            }),
        ])->canSee(function ($request) {
            return $request->user() && $request->user()->is_admin;
        }),
    ];
});

// Example 3: Laravel Authorization Integration
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Content Management', [
            MenuItem::resource('PostResource')
                ->canSee(fn($req) => $req->user()?->can('view-posts')),
            
            MenuItem::link('Create Post', '/posts/create')
                ->canSee(fn($req) => $req->user()?->can('create-posts')),
            
            MenuItem::link('Moderate Posts', '/posts/moderate')
                ->canSee(fn($req) => $req->user()?->can('moderate-posts')),
            
            MenuGroup::make('Advanced', [
                MenuItem::link('Bulk Actions', '/posts/bulk'),
                MenuItem::link('Import/Export', '/posts/import-export'),
            ])->canSee(fn($req) => $req->user()?->can('manage-posts')),
        ]),

        MenuSection::make('User Management', [
            MenuItem::resource('UserResource')
                ->canSee(fn($req) => $req->user()?->can('view-users')),
            
            MenuItem::link('User Roles', '/users/roles')
                ->canSee(fn($req) => $req->user()?->can('manage-roles')),
            
            MenuItem::link('Permissions', '/users/permissions')
                ->canSee(fn($req) => $req->user()?->can('manage-permissions')),
        ])->canSee(fn($req) => $req->user()?->can('access-user-management')),
    ];
});

// Example 4: Performance Optimization with Caching
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Performance Optimized', [
            // Cache expensive authorization checks
            MenuItem::link('Expensive Check', '/expensive')
                ->canSee(function ($request) {
                    // Simulate expensive operation (database query, API call, etc.)
                    sleep(1);
                    return $request->user() && $request->user()->hasComplexPermission();
                })
                ->cacheAuth(300), // Cache for 5 minutes

            // Cache role-based checks
            MenuItem::link('Role Check', '/role-check')
                ->canSee(function ($request) {
                    // Complex role calculation
                    return $request->user() && $request->user()->calculateEffectiveRole() === 'manager';
                })
                ->cacheAuth(600), // Cache for 10 minutes

            // Cache API-based authorization
            MenuItem::externalLink('External Service', 'https://api.example.com')
                ->canSee(function ($request) {
                    // API call to check permissions
                    $response = file_get_contents('https://api.example.com/check-access');
                    return json_decode($response, true)['has_access'] ?? false;
                })
                ->cacheAuth(1800), // Cache for 30 minutes
        ]),
    ];
});

// Example 5: Conditional Menu Structure
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();
    
    return [
        // Always visible dashboard
        MenuSection::make('Dashboard')
            ->path('/dashboard'),

        // Conditional sections based on user type
        ...$user && $user->is_customer ? [
            MenuSection::make('My Account', [
                MenuItem::link('Profile', '/profile'),
                MenuItem::link('Billing', '/billing'),
                MenuItem::link('Support Tickets', '/support'),
            ]),
        ] : [],

        ...$user && $user->is_vendor ? [
            MenuSection::make('Vendor Portal', [
                MenuItem::link('My Products', '/vendor/products'),
                MenuItem::link('Sales Analytics', '/vendor/analytics'),
                MenuItem::link('Payouts', '/vendor/payouts'),
            ]),
        ] : [],

        ...$user && $user->is_admin ? [
            MenuSection::make('Administration', [
                MenuItem::resource('UserResource'),
                MenuItem::resource('OrderResource'),
                MenuItem::link('System Settings', '/admin/settings'),
            ]),
        ] : [],
    ];
});

// Example 6: Team-Based Authorization
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Team Management', [
            // Show only current user's team
            MenuItem::filter('My Team Members', 'UserResource')
                ->applies('TeamFilter', $request->user()?->team_id)
                ->canSee(fn($req) => $req->user()?->team_id !== null),

            // Team lead only features
            MenuItem::link('Team Settings', '/team/settings')
                ->canSee(function ($request) {
                    $user = $request->user();
                    return $user && $user->team_role === 'lead';
                }),

            // Department head features
            MenuGroup::make('Department Management', [
                MenuItem::link('All Teams', '/department/teams'),
                MenuItem::link('Budget Overview', '/department/budget'),
            ])->canSee(function ($request) {
                $user = $request->user();
                return $user && $user->department_role === 'head';
            }),
        ])->canSee(fn($req) => $req->user() !== null),
    ];
});

// Example 7: Feature Flag Integration
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Feature Flagged Items', [
            // Beta features
            MenuItem::link('Beta Dashboard', '/beta/dashboard')
                ->canSee(function ($request) {
                    return $request->user() && 
                           $request->user()->hasFeatureFlag('beta_dashboard') &&
                           $request->user()->can('access-beta-features');
                }),

            // A/B test features
            MenuItem::link('New Analytics', '/analytics/v2')
                ->canSee(function ($request) {
                    $user = $request->user();
                    return $user && 
                           $user->isInExperiment('analytics_v2') &&
                           $user->can('view-analytics');
                })
                ->cacheAuth(60), // Cache for 1 minute

            // Premium features
            MenuGroup::make('Premium Features', [
                MenuItem::link('Advanced Reports', '/reports/advanced'),
                MenuItem::link('API Access', '/api/dashboard'),
            ])->canSee(function ($request) {
                $user = $request->user();
                return $user && 
                       $user->subscription_tier === 'premium' &&
                       $user->subscription_status === 'active';
            })
              ->cacheAuth(300), // Cache subscription checks
        ]),
    ];
});

// Example 8: Multi-Tenant Authorization
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Tenant Management', [
            // Tenant admin features
            MenuItem::link('Tenant Settings', '/tenant/settings')
                ->canSee(function ($request) {
                    $user = $request->user();
                    return $user && $user->isTenantAdmin($user->current_tenant_id);
                }),

            // Cross-tenant features (super admin only)
            MenuItem::link('All Tenants', '/admin/tenants')
                ->canSee(function ($request) {
                    return $request->user() && $request->user()->is_super_admin;
                }),

            // Tenant-specific resources
            MenuItem::filter('Tenant Users', 'UserResource')
                ->applies('TenantFilter', $request->user()?->current_tenant_id)
                ->canSee(function ($request) {
                    $user = $request->user();
                    return $user && $user->can('view-tenant-users', $user->current_tenant_id);
                }),
        ])->canSee(fn($req) => $req->user() !== null),
    ];
});

// Example 9: Time-Based Authorization
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Time-Sensitive Features', [
            // Business hours only
            MenuItem::link('Live Support', '/support/live')
                ->canSee(function ($request) {
                    $now = now();
                    $isBusinessHours = $now->hour >= 9 && $now->hour < 17 && $now->isWeekday();
                    return $request->user() && $isBusinessHours;
                }),

            // Maintenance window
            MenuItem::link('System Maintenance', '/admin/maintenance')
                ->canSee(function ($request) {
                    $user = $request->user();
                    $isMaintenanceWindow = now()->hour >= 2 && now()->hour < 4;
                    return $user && $user->is_admin && $isMaintenanceWindow;
                }),

            // Trial period features
            MenuItem::link('Trial Features', '/trial')
                ->canSee(function ($request) {
                    $user = $request->user();
                    return $user && 
                           $user->trial_ends_at &&
                           $user->trial_ends_at->isFuture();
                })
                ->cacheAuth(3600), // Cache for 1 hour
        ]),
    ];
});

// Example 10: Authorization with Clear Cache Methods
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Cache Management', [
            MenuItem::link('Clear Auth Cache', '/admin/clear-auth-cache')
                ->canSee(function ($request) {
                    return $request->user() && $request->user()->is_admin;
                })
                ->cacheAuth(60)
                ->withMeta(['cache_key' => 'admin_auth_check']),

            // Clear cache when user permissions change
            MenuItem::link('User Permissions', '/admin/permissions')
                ->canSee(function ($request) {
                    $user = $request->user();
                    // Clear cache when permissions are updated
                    if ($user && $user->permissions_updated_at > now()->subMinutes(5)) {
                        // Force cache refresh for recent permission changes
                        return $user->can('manage-permissions');
                    }
                    return $user && $user->can('manage-permissions');
                })
                ->cacheAuth(300),
        ])->cacheAuth(120), // Cache section authorization too
    ];
});
