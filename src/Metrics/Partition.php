<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Partition Metric Class.
 *
 * Base class for partition metrics that display categorical data as pie charts.
 * Provides Nova-compatible helper methods for grouping and aggregation operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Partition extends Metric
{
    /**
     * Calculate the partition data for the metric.
     */
    abstract public function calculate(Request $request): PartitionResult;

    /**
     * Count records grouped by a column.
     */
    protected function count(Request $request, string|Builder $model, string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'count', null, $groupBy);
    }

    /**
     * Sum values grouped by a column.
     */
    protected function sum(Request $request, string|Builder $model, string $column, string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'sum', $column, $groupBy);
    }

    /**
     * Calculate average grouped by a column.
     */
    protected function average(Request $request, string|Builder $model, string $column, string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'avg', $column, $groupBy);
    }

    /**
     * Calculate maximum grouped by a column.
     */
    protected function max(Request $request, string|Builder $model, string $column, string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'max', $column, $groupBy);
    }

    /**
     * Calculate minimum grouped by a column.
     */
    protected function min(Request $request, string|Builder $model, string $column, string $groupBy): PartitionResult
    {
        return $this->aggregate($request, $model, 'min', $column, $groupBy);
    }

    /**
     * Perform aggregation with grouping.
     */
    protected function aggregate(Request $request, string|Builder $model, string $function, ?string $column, string $groupBy): PartitionResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        if ($function === 'count') {
            $selectRaw = "{$groupBy}, COUNT(*) as aggregate";
            if ($column) {
                $query->whereNotNull($column);
            }
        } else {
            $selectRaw = "{$groupBy}, {$function}({$column}) as aggregate";
        }

        $results = $query
            ->selectRaw($selectRaw)
            ->groupBy($groupBy)
            ->orderByDesc('aggregate')
            ->get();

        $partitions = [];
        foreach ($results as $result) {
            $key = $result->{$groupBy} ?? 'Unknown';
            $partitions[$key] = (float) $result->aggregate;
        }

        return $this->partitionResult($partitions);
    }

    /**
     * Count records with custom grouping logic.
     */
    protected function countWithCustomGrouping(Request $request, string|Builder $model, callable $groupingCallback): PartitionResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        $records = $query->get();
        $partitions = [];

        foreach ($records as $record) {
            $group = call_user_func($groupingCallback, $record);
            $partitions[$group] = ($partitions[$group] ?? 0) + 1;
        }

        // Sort by value descending
        arsort($partitions);

        return $this->partitionResult($partitions);
    }

    /**
     * Sum values with custom grouping logic.
     */
    protected function sumWithCustomGrouping(Request $request, string|Builder $model, string $column, callable $groupingCallback): PartitionResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        $records = $query->get();
        $partitions = [];

        foreach ($records as $record) {
            $group = call_user_func($groupingCallback, $record);
            $value = $record->{$column} ?? 0;
            $partitions[$group] = ($partitions[$group] ?? 0) + $value;
        }

        // Sort by value descending
        arsort($partitions);

        return $this->partitionResult($partitions);
    }

    /**
     * Group records by date ranges.
     */
    protected function countByDateRanges(Request $request, string|Builder $model, array $ranges, ?string $dateColumn = null): PartitionResult
    {
        $timezone = $this->getTimezone($request);
        $dateColumn = $dateColumn ?? 'created_at';

        $query = $this->buildQuery($model);
        $records = $query->get();

        $partitions = [];

        // Initialize all ranges with 0
        foreach ($ranges as $label => $range) {
            $partitions[$label] = 0;
        }

        foreach ($records as $record) {
            $recordDate = Carbon::parse($record->{$dateColumn})->setTimezone($timezone);

            foreach ($ranges as $label => $range) {
                [$start, $end] = $range;
                $startDate = Carbon::parse($start)->setTimezone($timezone);
                $endDate = Carbon::parse($end)->setTimezone($timezone);

                if ($recordDate->between($startDate, $endDate)) {
                    $partitions[$label]++;
                    break; // Record belongs to first matching range
                }
            }
        }

        return $this->partitionResult($partitions);
    }

    /**
     * Group records by numeric ranges.
     */
    protected function countByNumericRanges(Request $request, string|Builder $model, string $column, array $ranges): PartitionResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        $records = $query->get();
        $partitions = [];

        // Initialize all ranges with 0
        foreach ($ranges as $label => $range) {
            $partitions[$label] = 0;
        }

        foreach ($records as $record) {
            $value = $record->{$column} ?? 0;

            foreach ($ranges as $label => $range) {
                [$min, $max] = $range;

                if ($value >= $min && ($max === null || $value <= $max)) {
                    $partitions[$label]++;
                    break; // Record belongs to first matching range
                }
            }
        }

        return $this->partitionResult($partitions);
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
