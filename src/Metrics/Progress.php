<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Progress Metric Class.
 *
 * Base class for progress metrics that display progress towards a target value
 * as progress bars. Provides Nova-compatible helper methods for progress calculations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Progress extends Metric
{
    /**
     * Calculate the progress data for the metric.
     */
    abstract public function calculate(Request $request): ProgressResult;

    /**
     * Count records towards a target.
     */
    protected function count(Request $request, string|Builder $model, mixed $target, ?string $column = null): ProgressResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->countForRange($model, $column, $range, $timezone);

        return $this->progressResult($current, $target);
    }

    /**
     * Sum values towards a target.
     */
    protected function sum(Request $request, string|Builder $model, string $column, mixed $target): ProgressResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->sumForRange($model, $column, $range, $timezone);

        return $this->progressResult($current, $target);
    }

    /**
     * Calculate average towards a target.
     */
    protected function average(Request $request, string|Builder $model, string $column, mixed $target): ProgressResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->averageForRange($model, $column, $range, $timezone);

        return $this->progressResult($current, $target);
    }

    /**
     * Calculate maximum towards a target.
     */
    protected function max(Request $request, string|Builder $model, string $column, mixed $target): ProgressResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->maxForRange($model, $column, $range, $timezone);

        return $this->progressResult($current, $target);
    }

    /**
     * Calculate minimum towards a target.
     */
    protected function min(Request $request, string|Builder $model, string $column, mixed $target): ProgressResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->minForRange($model, $column, $range, $timezone);

        return $this->progressResult($current, $target);
    }

    /**
     * Create progress result with dynamic target calculation.
     */
    protected function progressWithDynamicTarget(Request $request, string|Builder $model, callable $valueCallback, callable $targetCallback): ProgressResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        $current = call_user_func($valueCallback, $query);
        $target = call_user_func($targetCallback, $request, $range, $timezone);

        return $this->progressResult($current, $target);
    }

    /**
     * Create progress result with percentage-based target.
     */
    protected function progressWithPercentageTarget(Request $request, string|Builder $model, string $column, float $targetPercentage): ProgressResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        // Get total possible value
        $totalQuery = $this->buildQuery($model);
        $total = $totalQuery->sum($column);

        // Get current value for the range
        $currentQuery = $this->buildQuery($model);
        $this->applyDateRange($currentQuery, $range, $timezone);
        $current = $currentQuery->sum($column);

        // Calculate target based on percentage of total
        $target = $total * ($targetPercentage / 100);

        return $this->progressResult($current, $target);
    }

    /**
     * Create progress result comparing to previous period.
     */
    protected function progressComparedToPrevious(Request $request, string|Builder $model, string $function, ?string $column = null): ProgressResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->aggregateForRange($model, $function, $column, $range, $timezone);
        $previous = $this->aggregateForPreviousRange($model, $function, $column, $range, $timezone);

        // Use previous period as the target
        return $this->progressResult($current, $previous);
    }

    /**
     * Get the selected range from the request.
     */
    protected function getSelectedRange(Request $request): string|int
    {
        return $request->get('range', 30);
    }

    /**
     * Get the timezone from the request.
     */
    protected function getTimezone(Request $request): string
    {
        return $request->get('timezone', config('app.timezone', 'UTC'));
    }

    /**
     * Count records for a specific range.
     */
    protected function countForRange(string|Builder $model, ?string $column, string|int $range, string $timezone): int
    {
        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        if ($column) {
            return $query->whereNotNull($column)->count();
        }

        return $query->count();
    }

    /**
     * Sum values for a specific range.
     */
    protected function sumForRange(string|Builder $model, string $column, string|int $range, string $timezone): float
    {
        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        return (float) $query->sum($column);
    }

    /**
     * Calculate average for a specific range.
     */
    protected function averageForRange(string|Builder $model, string $column, string|int $range, string $timezone): float
    {
        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        return (float) $query->avg($column);
    }

    /**
     * Calculate maximum for a specific range.
     */
    protected function maxForRange(string|Builder $model, string $column, string|int $range, string $timezone): mixed
    {
        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        return $query->max($column);
    }

    /**
     * Calculate minimum for a specific range.
     */
    protected function minForRange(string|Builder $model, string $column, string|int $range, string $timezone): mixed
    {
        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        return $query->min($column);
    }

    /**
     * Perform aggregation for a specific range.
     */
    protected function aggregateForRange(string|Builder $model, string $function, ?string $column, string|int $range, string $timezone): mixed
    {
        return match ($function) {
            'count' => $this->countForRange($model, $column, $range, $timezone),
            'sum' => $this->sumForRange($model, $column, $range, $timezone),
            'avg' => $this->averageForRange($model, $column, $range, $timezone),
            'max' => $this->maxForRange($model, $column, $range, $timezone),
            'min' => $this->minForRange($model, $column, $range, $timezone),
            default => 0,
        };
    }

    /**
     * Perform aggregation for the previous range.
     */
    protected function aggregateForPreviousRange(string|Builder $model, string $function, ?string $column, string|int $range, string $timezone): mixed
    {
        $query = $this->buildQuery($model);
        $this->applyPreviousDateRange($query, $range, $timezone);

        return match ($function) {
            'count' => $column ? $query->whereNotNull($column)->count() : $query->count(),
            'sum' => (float) $query->sum($column),
            'avg' => (float) $query->avg($column),
            'max' => $query->max($column),
            'min' => $query->min($column),
            default => 0,
        };
    }

    /**
     * Build the base query for the model.
     */
    protected function buildQuery(string|Builder $model): Builder
    {
        if ($model instanceof Builder) {
            return clone $model;
        }

        return $model::query();
    }

    /**
     * Apply date range constraints to the query.
     */
    protected function applyDateRange(Builder $query, string|int $range, string $timezone): void
    {
        [$start, $end] = $this->calculateDateRange($range, $timezone);

        $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Apply previous date range constraints to the query.
     */
    protected function applyPreviousDateRange(Builder $query, string|int $range, string $timezone): void
    {
        [$start, $end] = $this->calculatePreviousDateRange($range, $timezone);

        $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Calculate the date range for the given range value.
     */
    protected function calculateDateRange(string|int $range, string $timezone): array
    {
        $now = Carbon::now($timezone);

        return match ($range) {
            'TODAY' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'MTD' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'QTD' => [$now->copy()->startOfQuarter(), $now->copy()->endOfDay()],
            'YTD' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            default => [$now->copy()->subDays((int) $range), $now],
        };
    }

    /**
     * Calculate the previous date range for comparison.
     */
    protected function calculatePreviousDateRange(string|int $range, string $timezone): array
    {
        $now = Carbon::now($timezone);

        return match ($range) {
            'TODAY' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'MTD' => [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()],
            'QTD' => [$now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()],
            'YTD' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            default => [$now->copy()->subDays((int) $range * 2), $now->copy()->subDays((int) $range)],
        };
    }
}
