<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Trend Metric Class.
 *
 * Base class for trend metrics that display time-series data as line charts.
 * Provides Nova-compatible helper methods for time-based aggregation operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Trend extends Metric
{
    /**
     * Calculate the trend data for the metric.
     */
    abstract public function calculate(Request $request): TrendResult;

    /**
     * Count records by days for the given model and range.
     */
    protected function countByDays(Request $request, string|Builder $model, ?string $column = null): TrendResult
    {
        return $this->aggregateByDays($request, $model, 'count', $column);
    }

    /**
     * Count records by weeks for the given model and range.
     */
    protected function countByWeeks(Request $request, string|Builder $model, ?string $column = null): TrendResult
    {
        return $this->aggregateByWeeks($request, $model, 'count', $column);
    }

    /**
     * Count records by months for the given model and range.
     */
    protected function countByMonths(Request $request, string|Builder $model, ?string $column = null): TrendResult
    {
        return $this->aggregateByMonths($request, $model, 'count', $column);
    }

    /**
     * Count records by hours for the given model and range.
     */
    protected function countByHours(Request $request, string|Builder $model, ?string $column = null): TrendResult
    {
        return $this->aggregateByHours($request, $model, 'count', $column);
    }

    /**
     * Count records by minutes for the given model and range.
     */
    protected function countByMinutes(Request $request, string|Builder $model, ?string $column = null): TrendResult
    {
        return $this->aggregateByMinutes($request, $model, 'count', $column);
    }

    /**
     * Sum values by days for the given model and range.
     */
    protected function sumByDays(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByDays($request, $model, 'sum', $column);
    }

    /**
     * Sum values by weeks for the given model and range.
     */
    protected function sumByWeeks(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByWeeks($request, $model, 'sum', $column);
    }

    /**
     * Sum values by months for the given model and range.
     */
    protected function sumByMonths(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByMonths($request, $model, 'sum', $column);
    }

    /**
     * Sum values by hours for the given model and range.
     */
    protected function sumByHours(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByHours($request, $model, 'sum', $column);
    }

    /**
     * Sum values by minutes for the given model and range.
     */
    protected function sumByMinutes(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByMinutes($request, $model, 'sum', $column);
    }

    /**
     * Calculate average by days for the given model and range.
     */
    protected function averageByDays(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByDays($request, $model, 'avg', $column);
    }

    /**
     * Calculate average by weeks for the given model and range.
     */
    protected function averageByWeeks(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByWeeks($request, $model, 'avg', $column);
    }

    /**
     * Calculate average by months for the given model and range.
     */
    protected function averageByMonths(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByMonths($request, $model, 'avg', $column);
    }

    /**
     * Calculate average by hours for the given model and range.
     */
    protected function averageByHours(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByHours($request, $model, 'avg', $column);
    }

    /**
     * Calculate average by minutes for the given model and range.
     */
    protected function averageByMinutes(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByMinutes($request, $model, 'avg', $column);
    }

    /**
     * Calculate maximum by days for the given model and range.
     */
    protected function maxByDays(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByDays($request, $model, 'max', $column);
    }

    /**
     * Calculate maximum by weeks for the given model and range.
     */
    protected function maxByWeeks(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByWeeks($request, $model, 'max', $column);
    }

    /**
     * Calculate maximum by months for the given model and range.
     */
    protected function maxByMonths(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByMonths($request, $model, 'max', $column);
    }

    /**
     * Calculate minimum by days for the given model and range.
     */
    protected function minByDays(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByDays($request, $model, 'min', $column);
    }

    /**
     * Calculate minimum by weeks for the given model and range.
     */
    protected function minByWeeks(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByWeeks($request, $model, 'min', $column);
    }

    /**
     * Calculate minimum by months for the given model and range.
     */
    protected function minByMonths(Request $request, string|Builder $model, string $column): TrendResult
    {
        return $this->aggregateByMonths($request, $model, 'min', $column);
    }

    /**
     * Aggregate data by days.
     */
    protected function aggregateByDays(Request $request, string|Builder $model, string $function, ?string $column = null): TrendResult
    {
        return $this->aggregateByUnit($request, $model, $function, $column, 'day');
    }

    /**
     * Aggregate data by weeks.
     */
    protected function aggregateByWeeks(Request $request, string|Builder $model, string $function, ?string $column = null): TrendResult
    {
        return $this->aggregateByUnit($request, $model, $function, $column, 'week');
    }

    /**
     * Aggregate data by months.
     */
    protected function aggregateByMonths(Request $request, string|Builder $model, string $function, ?string $column = null): TrendResult
    {
        return $this->aggregateByUnit($request, $model, $function, $column, 'month');
    }

    /**
     * Aggregate data by hours.
     */
    protected function aggregateByHours(Request $request, string|Builder $model, string $function, ?string $column = null): TrendResult
    {
        return $this->aggregateByUnit($request, $model, $function, $column, 'hour');
    }

    /**
     * Aggregate data by minutes.
     */
    protected function aggregateByMinutes(Request $request, string|Builder $model, string $function, ?string $column = null): TrendResult
    {
        return $this->aggregateByUnit($request, $model, $function, $column, 'minute');
    }

    /**
     * Aggregate data by a specific time unit.
     */
    protected function aggregateByUnit(Request $request, string|Builder $model, string $function, ?string $column, string $unit): TrendResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        $dateColumn = $this->getDateColumn();
        $selectRaw = $this->buildSelectRaw($function, $column, $dateColumn, $unit);

        if ($function === 'count' && $column) {
            $query->whereNotNull($column);
        }

        $results = $query
            ->selectRaw($selectRaw)
            ->groupBy('date_group')
            ->orderBy('date_group')
            ->get();

        $trend = [];
        foreach ($results as $result) {
            $trend[$result->date_group] = (float) $result->aggregate;
        }

        return $this->trendResult($trend);
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
     * Get the date column to use for grouping.
     */
    protected function getDateColumn(): string
    {
        return 'created_at';
    }

    /**
     * Build the database-agnostic SELECT raw statement.
     */
    protected function buildSelectRaw(string $function, ?string $column, string $dateColumn, string $unit): string
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        $dateGroupExpression = $this->getDateGroupExpression($dateColumn, $unit, $driver);

        if ($function === 'count') {
            return "{$dateGroupExpression} as date_group, COUNT(*) as aggregate";
        }

        return "{$dateGroupExpression} as date_group, {$function}({$column}) as aggregate";
    }

    /**
     * Get the database-specific date grouping expression.
     */
    protected function getDateGroupExpression(string $dateColumn, string $unit, string $driver): string
    {
        return match ($driver) {
            'mysql' => $this->getMySQLDateExpression($dateColumn, $unit),
            'pgsql' => $this->getPostgreSQLDateExpression($dateColumn, $unit),
            'sqlite' => $this->getSQLiteeDateExpression($dateColumn, $unit),
            'sqlsrv' => $this->getSQLServerDateExpression($dateColumn, $unit),
            default => $this->getSQLiteeDateExpression($dateColumn, $unit), // Default to SQLite for testing
        };
    }

    /**
     * Get MySQL date expression.
     */
    protected function getMySQLDateExpression(string $dateColumn, string $unit): string
    {
        $format = match ($unit) {
            'minute' => '%Y-%m-%d %H:%i',
            'hour' => '%Y-%m-%d %H:00',
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d',
        };

        return "DATE_FORMAT({$dateColumn}, '{$format}')";
    }

    /**
     * Get PostgreSQL date expression.
     */
    protected function getPostgreSQLDateExpression(string $dateColumn, string $unit): string
    {
        return match ($unit) {
            'minute' => "TO_CHAR({$dateColumn}, 'YYYY-MM-DD HH24:MI')",
            'hour' => "TO_CHAR({$dateColumn}, 'YYYY-MM-DD HH24:00')",
            'day' => "TO_CHAR({$dateColumn}, 'YYYY-MM-DD')",
            'week' => "TO_CHAR({$dateColumn}, 'YYYY-WW')",
            'month' => "TO_CHAR({$dateColumn}, 'YYYY-MM')",
            'year' => "TO_CHAR({$dateColumn}, 'YYYY')",
            default => "TO_CHAR({$dateColumn}, 'YYYY-MM-DD')",
        };
    }

    /**
     * Get SQLite date expression.
     */
    protected function getSQLiteeDateExpression(string $dateColumn, string $unit): string
    {
        return match ($unit) {
            'minute' => "strftime('%Y-%m-%d %H:%M', {$dateColumn})",
            'hour' => "strftime('%Y-%m-%d %H:00', {$dateColumn})",
            'day' => "strftime('%Y-%m-%d', {$dateColumn})",
            'week' => "strftime('%Y-%W', {$dateColumn})",
            'month' => "strftime('%Y-%m', {$dateColumn})",
            'year' => "strftime('%Y', {$dateColumn})",
            default => "strftime('%Y-%m-%d', {$dateColumn})",
        };
    }

    /**
     * Get SQL Server date expression.
     */
    protected function getSQLServerDateExpression(string $dateColumn, string $unit): string
    {
        return match ($unit) {
            'minute' => "FORMAT({$dateColumn}, 'yyyy-MM-dd HH:mm')",
            'hour' => "FORMAT({$dateColumn}, 'yyyy-MM-dd HH:00')",
            'day' => "FORMAT({$dateColumn}, 'yyyy-MM-dd')",
            'week' => "FORMAT({$dateColumn}, 'yyyy-ww')",
            'month' => "FORMAT({$dateColumn}, 'yyyy-MM')",
            'year' => "FORMAT({$dateColumn}, 'yyyy')",
            default => "FORMAT({$dateColumn}, 'yyyy-MM-dd')",
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

        $query->whereBetween($this->getDateColumn(), [$start, $end]);
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
}
