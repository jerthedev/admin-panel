<?php

/**
 * Dashboard Menu Integration Examples
 * 
 * This file demonstrates various ways to integrate dashboards with the
 * Nova v5 compatible menu system including custom sections, grouping,
 * badges, and advanced menu features.
 */

use Illuminate\Http\Request;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Concerns\HasMenuIntegration;
use JTD\AdminPanel\Menu\MenuItem;
use JTD\AdminPanel\Menu\MenuSection;
use JTD\AdminPanel\Support\AdminPanel;

// Example 1: Basic Dashboard Menu Integration
class AnalyticsDashboard extends Dashboard
{
    use HasMenuIntegration;

    public function name(): string
    {
        return 'Analytics Dashboard';
    }

    public function description(): string
    {
        return 'Comprehensive analytics and metrics overview';
    }

    public function category(): string
    {
        return 'Analytics';
    }

    public function icon(): string
    {
        return 'chart-bar';
    }

    // Custom menu configuration
    public function menu(Request $request): MenuItem
    {
        return parent::menu($request)
            ->withBadge(fn() => $this->getActiveUsersCount(), 'success')
            ->quickAccess()
            ->withMenuIcon('chart-line');
    }

    private function getActiveUsersCount(): int
    {
        // Example: return active users count
        return 1234;
    }
}

// Example 2: Dashboard with Advanced Menu Features
class SalesDashboard extends Dashboard
{
    use HasMenuIntegration;

    public function name(): string
    {
        return 'Sales Dashboard';
    }

    public function description(): string
    {
        return 'Sales performance and revenue tracking';
    }

    public function category(): string
    {
        return 'Business';
    }

    public function icon(): string
    {
        return 'trending-up';
    }

    // Configure menu integration
    public function boot(): void
    {
        $this->withMenuBadge(fn($request) => $this->getTodaySales($request), 'warning')
             ->quickAccess()
             ->withMenuPriority(10)
             ->menuVisibleWhen(fn($request) => $request->user()?->can('view-sales'));
    }

    private function getTodaySales(Request $request): ?string
    {
        $sales = 15420; // Example sales amount
        return $sales > 0 ? '$' . number_format($sales) : null;
    }
}

// Example 3: Custom Menu Registration
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Main dashboard (always first)
        MenuItem::dashboard(new \App\Dashboards\MainDashboard())
            ->withIcon('home'),

        // Business Intelligence Section
        MenuSection::make('Business Intelligence', [
            MenuItem::dashboard(AnalyticsDashboard::class)
                ->withBadge(fn() => 'Live', 'success'),
            
            MenuItem::dashboard(SalesDashboard::class)
                ->withBadge(fn() => '$15.4K', 'warning'),
            
            MenuItem::dashboard('ReportsDashboard')
                ->withIcon('document-text'),
        ])->icon('chart-bar')->collapsible(),

        // Quick Access Section
        MenuSection::make('Quick Access', [
            ...AdminPanel::quickAccessDashboards($request, 3)->toArray()
        ])->icon('lightning-bolt')->collapsible(),

        // Favorites Section
        MenuSection::make('Favorites', [
            ...AdminPanel::favoriteDashboards($request)->toArray()
        ])->icon('star')->collapsible(),

        // Auto-generated dashboard sections by category
        ...AdminPanel::dashboardMenuSections($request)->toArray(),
    ];
});

// Example 4: Dashboard Group Factory
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Main dashboard
        MenuSection::dashboard(new \App\Dashboards\MainDashboard()),

        // Business dashboards group
        MenuSection::dashboardGroup('Business Intelligence', [
            AnalyticsDashboard::class,
            SalesDashboard::class,
            'ReportsDashboard',
        ], [
            'icon' => 'briefcase',
            'collapsible' => true,
            'badge' => ['value' => 'New', 'type' => 'info'],
            'canSee' => fn($req) => $req->user()?->can('view-business-data'),
        ]),

        // System dashboards group
        MenuSection::dashboardGroup('System', [
            'SystemMonitoringDashboard',
            'SecurityDashboard',
            'PerformanceDashboard',
        ], [
            'icon' => 'cog',
            'collapsible' => true,
        ]),
    ];
});

// Example 5: Smart Auto-Generated Menu
AdminPanel::mainMenu(function (Request $request) {
    // Use the smart dashboard menu builder
    return AdminPanel::dashboardMenu($request, [
        'show_quick_access' => true,
        'show_favorites' => true,
        'group_by_category' => true,
        'quick_access_limit' => 5,
    ]);
});

// Example 6: Mixed Dashboard and Resource Menu
AdminPanel::mainMenu(function (Request $request) {
    return [
        // Dashboard section
        MenuSection::make('Overview', [
            MenuItem::dashboard('MainDashboard'),
            MenuItem::dashboard(AnalyticsDashboard::class),
        ])->icon('home'),

        // Content management
        MenuSection::make('Content', [
            MenuItem::resource('PostResource'),
            MenuItem::resource('CategoryResource'),
            MenuItem::link('Media Library', '/admin/media'),
        ])->icon('document-text'),

        // Business dashboards
        MenuSection::make('Business', [
            MenuItem::dashboard(SalesDashboard::class),
            MenuItem::dashboard('RevenueReportsDashboard'),
            MenuItem::externalLink('Stripe Dashboard', 'https://dashboard.stripe.com'),
        ])->icon('briefcase'),

        // User management
        MenuSection::make('Users', [
            MenuItem::resource('UserResource'),
            MenuItem::dashboard('UserAnalyticsDashboard'),
            MenuItem::filter('Active Users', 'UserResource')
                ->applies('StatusFilter', 'active'),
        ])->icon('users'),
    ];
});

// Example 7: Conditional Dashboard Menu
AdminPanel::mainMenu(function (Request $request) {
    $user = $request->user();
    $menu = [];

    // Always show main dashboard
    $menu[] = MenuItem::dashboard('MainDashboard');

    // Admin-only dashboards
    if ($user?->hasRole('admin')) {
        $menu[] = MenuSection::make('Administration', [
            MenuItem::dashboard('AdminDashboard'),
            MenuItem::dashboard('SystemMonitoringDashboard'),
            MenuItem::dashboard('UserManagementDashboard'),
        ])->icon('shield-check');
    }

    // Sales team dashboards
    if ($user?->can('view-sales')) {
        $menu[] = MenuSection::make('Sales', [
            MenuItem::dashboard(SalesDashboard::class),
            MenuItem::dashboard('LeadsDashboard'),
            MenuItem::dashboard('RevenueReportsDashboard'),
        ])->icon('trending-up');
    }

    // Analytics dashboards (for analysts and managers)
    if ($user?->hasAnyRole(['analyst', 'manager', 'admin'])) {
        $menu[] = MenuSection::make('Analytics', [
            MenuItem::dashboard(AnalyticsDashboard::class),
            MenuItem::dashboard('CustomerAnalyticsDashboard'),
            MenuItem::dashboard('ProductAnalyticsDashboard'),
        ])->icon('chart-bar');
    }

    return $menu;
});

// Example 8: Dashboard with Custom Badge Logic
class CustomerSupportDashboard extends Dashboard
{
    use HasMenuIntegration;

    public function name(): string
    {
        return 'Customer Support';
    }

    public function category(): string
    {
        return 'Support';
    }

    public function menu(Request $request): MenuItem
    {
        return parent::menu($request)
            ->withBadge(function () {
                $openTickets = $this->getOpenTicketsCount();
                if ($openTickets > 10) {
                    return ['value' => $openTickets, 'type' => 'danger'];
                } elseif ($openTickets > 5) {
                    return ['value' => $openTickets, 'type' => 'warning'];
                } elseif ($openTickets > 0) {
                    return ['value' => $openTickets, 'type' => 'info'];
                }
                return null;
            })
            ->withMenuIcon('support');
    }

    private function getOpenTicketsCount(): int
    {
        // Example: return open support tickets count
        return 7;
    }
}

// Example 9: Nova v5 Compatible Menu Registration
// This works exactly like Nova's menu system
AdminPanel::mainMenu(function (Request $request) {
    return [
        MenuSection::make('Dashboards', [
            MenuItem::dashboard(\App\Dashboards\MainDashboard::class),
            MenuItem::dashboard(\App\Dashboards\AnalyticsDashboard::class),
            MenuItem::dashboard(\App\Dashboards\SalesDashboard::class),
        ]),
        
        MenuSection::make('Resources', [
            MenuItem::resource(\App\Nova\User::class),
            MenuItem::resource(\App\Nova\Post::class),
        ]),
    ];
});

// Example 10: Dashboard Menu with Search and Filtering
AdminPanel::mainMenu(function (Request $request) {
    // Get all dashboard menu items
    $dashboardItems = AdminPanel::dashboardMenuItems($request);
    
    // Filter based on search query if provided
    $search = $request->get('menu_search');
    if ($search) {
        $dashboardItems = $dashboardItems->filter(function ($item) use ($search) {
            return str_contains(strtolower($item->label), strtolower($search)) ||
                   str_contains(strtolower($item->meta['dashboard_description'] ?? ''), strtolower($search));
        });
    }

    return [
        MenuSection::make('Dashboards', $dashboardItems->toArray())
            ->icon('view-grid')
            ->meta('searchable', true),
    ];
});
