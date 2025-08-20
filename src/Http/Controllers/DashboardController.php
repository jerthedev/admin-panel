<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use JTD\AdminPanel\Dashboards\Dashboard;
use JTD\AdminPanel\Dashboards\Main;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Dashboard Controller.
 *
 * Handles the admin panel dashboard with metrics, cards,
 * and overview information.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class DashboardController extends Controller
{
    /**
     * Display the admin panel dashboard.
     */
    public function index(Request $request): Response
    {
        // Use Main dashboard as default
        $dashboard = new Main;

        return $this->show($request, $dashboard);
    }

    /**
     * Display a specific dashboard by URI key.
     */
    public function showByUriKey(Request $request, string $uriKey): Response
    {
        $adminPanel = app(AdminPanel::class);

        // Find dashboard by URI key
        $dashboard = $adminPanel->findDashboardByUriKey($uriKey);

        if (! $dashboard) {
            abort(404, "Dashboard '{$uriKey}' not found.");
        }

        return $this->show($request, $dashboard);
    }

    /**
     * Display a specific dashboard.
     */
    public function show(Request $request, ?Dashboard $dashboard = null): Response
    {
        $adminPanel = app(AdminPanel::class);

        // Use Main dashboard if none specified
        if (! $dashboard) {
            $dashboard = new Main;
        }

        // Check authorization
        if (! $dashboard->authorizedToSee($request)) {
            abort(403, 'Unauthorized to view this dashboard.');
        }

        return Inertia::render('Dashboard', [
            'dashboard' => [
                'name' => $dashboard->name(),
                'uriKey' => $dashboard->uriKey(),
                'description' => $dashboard->description(),
                'icon' => $dashboard->icon(),
                'category' => $dashboard->category(),
                'showRefreshButton' => $dashboard->shouldShowRefreshButton(),
            ],
            'navigation' => $this->getDashboardNavigation($adminPanel, $request, $dashboard),
            'metrics' => $this->getMetrics($adminPanel, $request),
            'cards' => $this->getCards($dashboard, $request),
            'recentActivity' => $this->getRecentActivity($request),
            'quickActions' => $this->getQuickActions($adminPanel, $request),
            'systemInfo' => $this->getSystemInfo(),
        ]);
    }

    /**
     * Get dashboard navigation data.
     */
    protected function getDashboardNavigation(AdminPanel $adminPanel, Request $request, Dashboard $currentDashboard): array
    {
        $availableDashboards = $adminPanel->getNavigationDashboards($request);

        return [
            'currentDashboard' => [
                'name' => $currentDashboard->name(),
                'uriKey' => $currentDashboard->uriKey(),
                'description' => $currentDashboard->description(),
                'icon' => $currentDashboard->icon(),
                'category' => $currentDashboard->category(),
            ],
            'availableDashboards' => $availableDashboards->map(function (Dashboard $dashboard) {
                return [
                    'name' => $dashboard->name(),
                    'uriKey' => $dashboard->uriKey(),
                    'description' => $dashboard->description(),
                    'icon' => $dashboard->icon(),
                    'category' => $dashboard->category(),
                    'url' => $this->getDashboardUrl($dashboard),
                ];
            })->values()->toArray(),
            'breadcrumbs' => $this->getDashboardBreadcrumbs($currentDashboard),
            'preferences' => [
                'showBreadcrumbs' => config('admin-panel.dashboard.navigation.show_breadcrumbs', true),
                'showQuickSwitcher' => config('admin-panel.dashboard.navigation.show_quick_switcher', true),
                'enableKeyboardShortcuts' => config('admin-panel.dashboard.navigation.enable_keyboard_shortcuts', true),
                'maxHistoryItems' => config('admin-panel.dashboard.navigation.max_history_items', 10),
                'maxRecentItems' => config('admin-panel.dashboard.navigation.max_recent_items', 5),
                'persistState' => config('admin-panel.dashboard.navigation.persist_state', true),
                'showNavigationControls' => config('admin-panel.dashboard.navigation.show_navigation_controls', true),
                'showQuickActions' => config('admin-panel.dashboard.navigation.show_quick_actions', true),
                'showKeyboardHints' => config('admin-panel.dashboard.navigation.show_keyboard_hints', true),

                // Transition preferences
                'transitionDuration' => config('admin-panel.dashboard.transitions.transition_duration', 300),
                'showTransitionLoading' => config('admin-panel.dashboard.transitions.show_transition_loading', true),
                'loadingVariant' => config('admin-panel.dashboard.transitions.loading_variant', 'spinner'),
                'showTransitionProgress' => config('admin-panel.dashboard.transitions.show_transition_progress', true),
                'allowCancelTransition' => config('admin-panel.dashboard.transitions.allow_cancel_transition', true),
                'showTransitionErrors' => config('admin-panel.dashboard.transitions.show_transition_errors', true),
                'theme' => config('admin-panel.dashboard.transitions.theme', 'light'),
                'enableGestureNavigation' => config('admin-panel.dashboard.transitions.enable_gesture_navigation', false),
            ],
        ];
    }

    /**
     * Get dashboard breadcrumbs.
     */
    protected function getDashboardBreadcrumbs(Dashboard $dashboard): array
    {
        $breadcrumbs = [];

        // Always start with Dashboard home
        $breadcrumbs[] = [
            'label' => 'Dashboards',
            'href' => route('admin-panel.dashboard'),
            'icon' => 'HomeIcon',
            'isHome' => true,
        ];

        // Add current dashboard if not the main dashboard
        if ($dashboard->uriKey() !== 'main') {
            $breadcrumbs[] = [
                'label' => $dashboard->name(),
                'href' => null,
                'icon' => $dashboard->icon() ?: 'ChartBarIcon',
                'isCurrent' => true,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Get dashboard URL.
     */
    protected function getDashboardUrl(Dashboard $dashboard): string
    {
        if ($dashboard->uriKey() === 'main') {
            return route('admin-panel.dashboard');
        }

        return route('admin-panel.dashboards.show', ['uriKey' => $dashboard->uriKey()]);
    }

    /**
     * Get dashboard metrics.
     */
    protected function getMetrics(AdminPanel $adminPanel, Request $request): array
    {
        $metrics = [];

        // Get registered metrics from AdminPanel
        $registeredMetrics = $adminPanel->getMetrics();

        // Add default metrics if none are registered
        if ($registeredMetrics->isEmpty()) {
            $defaultMetrics = config('admin-panel.dashboard.default_metrics', []);
            $registeredMetrics = collect($defaultMetrics);
        }

        foreach ($registeredMetrics as $metricClass) {
            if (! class_exists($metricClass)) {
                continue;
            }

            $metricInstance = new $metricClass;

            if ($metricInstance->authorize($request)) {
                try {
                    $value = $metricInstance->calculate($request);
                    $trend = $metricInstance->trend($request);

                    $metrics[] = [
                        'name' => $metricInstance->name(),
                        'value' => $value,
                        'format' => $metricInstance->format(),
                        'icon' => $metricInstance->icon(),
                        'color' => $metricInstance->color(),
                        'trend' => $trend,
                        'formatted_value' => $this->formatMetricValue($value, $metricInstance->format()),
                    ];
                } catch (\Exception $e) {
                    // Log error but don't break the dashboard
                    logger()->error('Error calculating metric: '.$metricClass, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        return $metrics;
    }

    /**
     * Get cards from a dashboard instance.
     */
    protected function getCards(Dashboard $dashboard, Request $request): array
    {
        $cards = [];

        try {
            $dashboardCards = $dashboard->cards();

            foreach ($dashboardCards as $card) {
                try {
                    if ($card->authorize($request)) {
                        $cards[] = [
                            'component' => $card->component(),
                            'data' => $card->data($request),
                            'title' => $card->title(),
                            'size' => $card->size(),
                        ];
                    }
                } catch (\Exception $e) {
                    // Log error but don't break the dashboard
                    logger()->error('Error loading dashboard card: '.get_class($card), [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log error but don't break the dashboard
            logger()->error('Error loading dashboard cards from: '.get_class($dashboard), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $cards;
    }

    /**
     * Get recent activity.
     */
    protected function getRecentActivity(Request $request): array
    {
        // This would typically integrate with an activity log system
        // For now, return sample data
        return [
            [
                'id' => 1,
                'type' => 'created',
                'resource' => 'User',
                'resource_id' => 123,
                'user' => $request->user()?->name ?? 'System',
                'description' => 'Created new user: John Doe',
                'created_at' => now()->subMinutes(5)->toISOString(),
            ],
            [
                'id' => 2,
                'type' => 'updated',
                'resource' => 'Post',
                'resource_id' => 456,
                'user' => $request->user()?->name ?? 'System',
                'description' => 'Updated post: Sample Blog Post',
                'created_at' => now()->subMinutes(15)->toISOString(),
            ],
            [
                'id' => 3,
                'type' => 'deleted',
                'resource' => 'Comment',
                'resource_id' => 789,
                'user' => $request->user()?->name ?? 'System',
                'description' => 'Deleted spam comment',
                'created_at' => now()->subHour()->toISOString(),
            ],
        ];
    }

    /**
     * Get quick actions.
     */
    protected function getQuickActions(AdminPanel $adminPanel, Request $request): array
    {
        $actions = [];
        $resources = $adminPanel->getNavigationResources();

        foreach ($resources->take(6) as $resource) {
            if ($resource->authorizedToCreate($request)) {
                $actions[] = [
                    'label' => "Create {$resource::singularLabel()}",
                    'href' => route('admin-panel.resources.create', $resource::uriKey()),
                    'icon' => 'PlusIcon',
                    'color' => 'blue',
                ];
            }
        }

        // Add system actions
        $actions[] = [
            'label' => 'Clear Cache',
            'href' => '#',
            'icon' => 'TrashIcon',
            'color' => 'amber',
            'action' => 'clear-cache',
        ];

        $actions[] = [
            'label' => 'View Logs',
            'href' => '#',
            'icon' => 'DocumentTextIcon',
            'color' => 'gray',
            'action' => 'view-logs',
        ];

        return $actions;
    }

    /**
     * Get system information.
     */
    protected function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'admin_panel_version' => '1.0.0',
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'timezone' => config('app.timezone'),
            'locale' => config('app.locale'),
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision).' '.$units[$i];
    }

    /**
     * Format a metric value based on its format type.
     */
    protected function formatMetricValue(mixed $value, string $format): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        $numericValue = (float) $value;

        return match ($format) {
            'currency' => '$'.number_format($numericValue, 2),
            'percentage' => number_format($numericValue, 1).'%',
            'number' => number_format($numericValue),
            default => (string) $value,
        };
    }
}
