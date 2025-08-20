<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Value Metric Class.
 *
 * Base class for value metrics that display a single value with optional
 * comparison to a previous time period. Provides Nova-compatible helper
 * methods for common aggregation operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Value extends Metric
{
    /**
     * Calculate the value of the metric.
     */
    abstract public function calculate(Request $request): ValueResult;

    /**
     * Count records for the given model and range.
     */
    protected function count(Request $request, string|Builder $model, ?string $column = null): ValueResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->countForRange($model, $column, $range, $timezone);
        $previous = $this->countForPreviousRange($model, $column, $range, $timezone);

        return $this->result($current)->previous($previous);
    }

    /**
     * Sum values for the given model and range.
     */
    protected function sum(Request $request, string|Builder $model, string $column): ValueResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->sumForRange($model, $column, $range, $timezone);
        $previous = $this->sumForPreviousRange($model, $column, $range, $timezone);

        return $this->result($current)->previous($previous);
    }

    /**
     * Calculate average for the given model and range.
     */
    protected function average(Request $request, string|Builder $model, string $column): ValueResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->averageForRange($model, $column, $range, $timezone);
        $previous = $this->averageForPreviousRange($model, $column, $range, $timezone);

        return $this->result($current)->previous($previous);
    }

    /**
     * Calculate maximum for the given model and range.
     */
    protected function max(Request $request, string|Builder $model, string $column): ValueResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->maxForRange($model, $column, $range, $timezone);
        $previous = $this->maxForPreviousRange($model, $column, $range, $timezone);

        return $this->result($current)->previous($previous);
    }

    /**
     * Calculate minimum for the given model and range.
     */
    protected function min(Request $request, string|Builder $model, string $column): ValueResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $current = $this->minForRange($model, $column, $range, $timezone);
        $previous = $this->minForPreviousRange($model, $column, $range, $timezone);

        return $this->result($current)->previous($previous);
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
     * Count records for the previous range.
     */
    protected function countForPreviousRange(string|Builder $model, ?string $column, string|int $range, string $timezone): int
    {
        $query = $this->buildQuery($model);
        $this->applyPreviousDateRange($query, $range, $timezone);

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
     * Sum values for the previous range.
     */
    protected function sumForPreviousRange(string|Builder $model, string $column, string|int $range, string $timezone): float
    {
        $query = $this->buildQuery($model);
        $this->applyPreviousDateRange($query, $range, $timezone);

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
     * Calculate average for the previous range.
     */
    protected function averageForPreviousRange(string|Builder $model, string $column, string|int $range, string $timezone): float
    {
        $query = $this->buildQuery($model);
        $this->applyPreviousDateRange($query, $range, $timezone);

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
     * Calculate maximum for the previous range.
     */
    protected function maxForPreviousRange(string|Builder $model, string $column, string|int $range, string $timezone): mixed
    {
        $query = $this->buildQuery($model);
        $this->applyPreviousDateRange($query, $range, $timezone);

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
     * Calculate minimum for the previous range.
     */
    protected function minForPreviousRange(string|Builder $model, string $column, string|int $range, string $timezone): mixed
    {
        $query = $this->buildQuery($model);
        $this->applyPreviousDateRange($query, $range, $timezone);

        return $query->min($column);
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
