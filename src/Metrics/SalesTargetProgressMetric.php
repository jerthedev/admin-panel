<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Sales Target Progress Metric.
 *
 * Example Progress metric that demonstrates sales progress towards monthly/quarterly targets.
 * Shows the current sales amount compared to a target value with progress bar visualization
 * and percentage completion.
 *
 * This metric serves as a reference implementation for Progress metrics, showcasing:
 * - Target value configuration and percentage calculation
 * - Custom formatting (currency, percentage, etc.)
 * - Color customization based on progress level
 * - Dynamic target calculation based on time periods
 * - Caching for performance optimization
 * - Configurable model support
 * - Range selection for different target periods
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class SalesTargetProgressMetric extends Progress
{
    /**
     * The metric's display name.
     */
    public string $name = 'Sales Target Progress';

    /**
     * The order model class to query.
     */
    protected string $orderModel = 'App\Models\Order';

    /**
     * Cache duration in minutes.
     */
    protected int $cacheMinutes = 10;

    /**
     * Default monthly sales target.
     */
    protected float $monthlyTarget = 50000.00;

    /**
     * Default quarterly sales target.
     */
    protected float $quarterlyTarget = 150000.00;

    /**
     * Default yearly sales target.
     */
    protected float $yearlyTarget = 600000.00;

    /**
     * Calculate the progress data for the metric.
     */
    public function calculate(Request $request): ProgressResult
    {
        // Use caching for performance
        $cacheKey = $this->getCacheKey($request);

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            $target = $this->getTargetForRange($request);

            return $this->sum($request, $this->orderModel, 'total', $target)
                ->currency('$')
                ->avoidUnwantedProgress();
        });
    }

    /**
     * Calculate progress with dynamic target based on historical performance.
     */
    public function calculateWithDynamicTarget(Request $request): ProgressResult
    {
        $cacheKey = $this->getCacheKey($request, 'dynamic');

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->progressWithDynamicTarget(
                $request,
                $this->orderModel,
                fn ($query) => $query->sum('total'),
                fn ($request, $range, $timezone) => $this->calculateDynamicTarget($request, $range, $timezone),
            )->currency('$');
        });
    }

    /**
     * Calculate progress compared to previous period.
     */
    public function calculateComparedToPrevious(Request $request): ProgressResult
    {
        $cacheKey = $this->getCacheKey($request, 'previous');

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            return $this->progressComparedToPrevious($request, $this->orderModel, 'sum', 'total')
                ->currency('$')
                ->suffix(' vs previous period');
        });
    }

    /**
     * Calculate order count progress towards target.
     */
    public function calculateOrderCountProgress(Request $request): ProgressResult
    {
        $cacheKey = $this->getCacheKey($request, 'count');

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            $target = $this->getOrderCountTargetForRange($request);

            return $this->count($request, $this->orderModel, $target)
                ->suffix(' orders')
                ->avoidUnwantedProgress();
        });
    }

    /**
     * Calculate average order value progress towards target.
     */
    public function calculateAverageOrderValueProgress(Request $request): ProgressResult
    {
        $cacheKey = $this->getCacheKey($request, 'avg');

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($request) {
            $target = $this->getAverageOrderValueTarget();

            return $this->average($request, $this->orderModel, 'total', $target)
                ->currency('$')
                ->suffix(' avg order value');
        });
    }

    /**
     * Get the ranges available for this metric.
     */
    public function ranges(): array
    {
        return [
            7 => '7 Days',
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
            'MTD' => 'Month To Date',
            'QTD' => 'Quarter To Date',
            'YTD' => 'Year To Date',
        ];
    }

    /**
     * Determine the cache duration for this metric.
     */
    public function cacheFor(): int
    {
        return $this->cacheMinutes * 60; // Convert to seconds
    }

    /**
     * Set the order model to query.
     */
    public function orderModel(string $model): static
    {
        $this->orderModel = $model;

        return $this;
    }

    /**
     * Set the monthly sales target.
     */
    public function monthlyTarget(float $target): static
    {
        $this->monthlyTarget = $target;

        return $this;
    }

    /**
     * Set the quarterly sales target.
     */
    public function quarterlyTarget(float $target): static
    {
        $this->quarterlyTarget = $target;

        return $this;
    }

    /**
     * Set the yearly sales target.
     */
    public function yearlyTarget(float $target): static
    {
        $this->yearlyTarget = $target;

        return $this;
    }

    /**
     * Set the cache duration in minutes.
     */
    public function cacheForMinutes(int $minutes): static
    {
        $this->cacheMinutes = $minutes;

        return $this;
    }

    /**
     * Get the target value for the selected range.
     */
    protected function getTargetForRange(Request $request): float
    {
        $range = $request->get('range', 30);

        return match ($range) {
            'MTD' => $this->monthlyTarget,
            'QTD' => $this->quarterlyTarget,
            'YTD' => $this->yearlyTarget,
            7 => $this->monthlyTarget * (7 / 30), // Pro-rated weekly target
            30 => $this->monthlyTarget,
            60 => $this->monthlyTarget * 2, // 2 months
            90 => $this->quarterlyTarget,
            default => $this->monthlyTarget * ((int) $range / 30), // Pro-rated target
        };
    }

    /**
     * Get the order count target for the selected range.
     */
    protected function getOrderCountTargetForRange(Request $request): int
    {
        $range = $request->get('range', 30);

        return match ($range) {
            'MTD' => 100, // 100 orders per month
            'QTD' => 300, // 300 orders per quarter
            'YTD' => 1200, // 1200 orders per year
            7 => 25, // ~25 orders per week
            30 => 100,
            60 => 200,
            90 => 300,
            default => (int) ((int) $range * 3.33), // ~3.33 orders per day
        };
    }

    /**
     * Get the average order value target.
     */
    protected function getAverageOrderValueTarget(): float
    {
        return 500.00; // $500 average order value target
    }

    /**
     * Calculate dynamic target based on historical performance.
     */
    protected function calculateDynamicTarget(Request $request, string|int $range, string $timezone): float
    {
        // Calculate target as 110% of the same period last year
        $query = $this->buildQuery($this->orderModel);

        // Apply date range for same period last year
        [$start, $end] = $this->calculateDateRange($range, $timezone);
        $lastYearStart = $start->copy()->subYear();
        $lastYearEnd = $end->copy()->subYear();

        $query->whereBetween('created_at', [$lastYearStart, $lastYearEnd]);
        $lastYearSales = $query->sum('total');

        // Target is 110% of last year's performance
        return $lastYearSales * 1.10;
    }

    /**
     * Generate a cache key for the metric.
     */
    protected function getCacheKey(Request $request, string $suffix = ''): string
    {
        $range = $request->get('range', 30);
        $timezone = $request->get('timezone', config('app.timezone', 'UTC'));

        $key = sprintf(
            'admin_panel_metric_sales_target_progress_%s_%s_%s',
            md5($this->orderModel),
            $range,
            md5($timezone),
        );

        if ($suffix) {
            $key .= '_'.$suffix;
        }

        return $key;
    }

    /**
     * Get the URI key for the metric.
     */
    public function uriKey(): string
    {
        return 'sales-target-progress';
    }

    /**
     * Determine if the metric should be displayed.
     */
    public function authorize(Request $request): bool
    {
        // Only show to users who can view sales data
        return $request->user()?->can('viewAny', $this->orderModel) ?? false;
    }

    /**
     * Get help text for the metric.
     */
    public function help(): ?string
    {
        return 'Shows the current sales progress towards the target with progress bar visualization.';
    }

    /**
     * Get the metric's icon.
     */
    public function icon(): string
    {
        return 'chart-bar';
    }

    /**
     * Get additional metadata for the metric.
     */
    public function meta(): array
    {
        return [
            'model' => $this->orderModel,
            'monthly_target' => $this->monthlyTarget,
            'quarterly_target' => $this->quarterlyTarget,
            'yearly_target' => $this->yearlyTarget,
            'cache_minutes' => $this->cacheMinutes,
            'help' => $this->help(),
            'icon' => $this->icon(),
        ];
    }
}
