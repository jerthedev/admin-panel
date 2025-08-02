<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use JTD\AdminPanel\Support\AdminPanel;

/**
 * Dashboard Controller
 *
 * Handles the admin panel dashboard with metrics, widgets,
 * and overview information.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Http\Controllers
 */
class DashboardController extends Controller
{
    /**
     * Display the admin panel dashboard.
     */
    public function index(Request $request): Response
    {
        $adminPanel = app(AdminPanel::class);

        return Inertia::render('Dashboard', [
            'metrics' => $this->getMetrics($adminPanel, $request),
            'widgets' => $this->getWidgets($adminPanel, $request),
            'recentActivity' => $this->getRecentActivity($request),
            'quickActions' => $this->getQuickActions($adminPanel, $request),
            'systemInfo' => $this->getSystemInfo(),
        ]);
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
            if (!class_exists($metricClass)) {
                continue;
            }

            $metricInstance = new $metricClass();

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
                    logger()->error('Error calculating metric: ' . $metricClass, [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }

        return $metrics;
    }

    /**
     * Get dashboard widgets.
     */
    protected function getWidgets(AdminPanel $adminPanel, Request $request): array
    {
        $widgets = [];
        $defaultWidgets = config('admin-panel.dashboard.default_widgets', []);

        foreach ($defaultWidgets as $widget) {
            if (class_exists($widget)) {
                $widgetInstance = new $widget();

                if ($widgetInstance->authorize($request)) {
                    $widgets[] = [
                        'component' => $widgetInstance->component(),
                        'data' => $widgetInstance->data($request),
                        'title' => $widgetInstance->title(),
                        'size' => $widgetInstance->size(),
                    ];
                }
            }
        }

        return $widgets;
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

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Format a metric value based on its format type.
     */
    protected function formatMetricValue(mixed $value, string $format): string
    {
        if (!is_numeric($value)) {
            return (string) $value;
        }

        $numericValue = (float) $value;

        return match ($format) {
            'currency' => '$' . number_format($numericValue, 2),
            'percentage' => number_format($numericValue, 1) . '%',
            'number' => number_format($numericValue),
            default => (string) $value,
        };
    }
}
