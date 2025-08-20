<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Table Metric Class.
 *
 * Base class for table metrics that display tabular data with columns and actions.
 * Provides Nova-compatible helper methods for data formatting and table operations.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
abstract class Table extends Metric
{
    /**
     * Calculate the table data for the metric.
     */
    abstract public function calculate(Request $request): TableResult;

    /**
     * Create a table result from a model query.
     */
    protected function fromModel(Request $request, string|Builder $model, array $columns = []): TableResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        // Apply sorting if requested
        $this->applySorting($query, $request);

        // Apply pagination if requested
        $limit = $this->getLimit($request);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $records = $query->get();
        $data = $this->transformRecords($records, $columns);

        return $this->tableResult($data);
    }

    /**
     * Create a table result from a collection.
     */
    protected function fromCollection(Collection $collection, array $columns = []): TableResult
    {
        $data = $this->transformRecords($collection, $columns);

        return $this->tableResult($data);
    }

    /**
     * Create a table result from raw data.
     */
    protected function fromArray(array $data): TableResult
    {
        return $this->tableResult($data);
    }

    /**
     * Create a table result with aggregated data.
     */
    protected function fromAggregation(Request $request, string|Builder $model, string $groupBy, string $function, ?string $column = null): TableResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        if ($function === 'count') {
            $selectRaw = "{$groupBy}, COUNT(*) as value";
            if ($column) {
                $query->whereNotNull($column);
            }
        } else {
            $selectRaw = "{$groupBy}, {$function}({$column}) as value";
        }

        $results = $query
            ->selectRaw($selectRaw)
            ->groupBy($groupBy)
            ->orderByDesc('value')
            ->get();

        $data = [];
        foreach ($results as $result) {
            $data[] = [
                'group' => $result->{$groupBy} ?? 'Unknown',
                'value' => (float) $result->value,
            ];
        }

        return $this->tableResult($data);
    }

    /**
     * Create a table result with top records by a column.
     */
    protected function topRecords(Request $request, string|Builder $model, string $orderBy, int $limit = 10, array $columns = []): TableResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        $records = $query
            ->orderByDesc($orderBy)
            ->limit($limit)
            ->get();

        $data = $this->transformRecords($records, $columns);

        return $this->tableResult($data);
    }

    /**
     * Create a table result with recent records.
     */
    protected function recentRecords(Request $request, string|Builder $model, int $limit = 10, array $columns = [], ?string $dateColumn = null): TableResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);
        $dateColumn = $dateColumn ?? 'created_at';

        $query = $this->buildQuery($model);
        $this->applyDateRange($query, $range, $timezone);

        $records = $query
            ->orderByDesc($dateColumn)
            ->limit($limit)
            ->get();

        $data = $this->transformRecords($records, $columns);

        return $this->tableResult($data);
    }

    /**
     * Create a table result with custom query logic.
     */
    protected function fromCustomQuery(Request $request, callable $queryCallback, array $columns = []): TableResult
    {
        $range = $this->getSelectedRange($request);
        $timezone = $this->getTimezone($request);

        $records = call_user_func($queryCallback, $request, $range, $timezone);

        if ($records instanceof Collection) {
            $data = $this->transformRecords($records, $columns);
        } else {
            $data = is_array($records) ? $records : [];
        }

        return $this->tableResult($data);
    }

    /**
     * Transform model records to table data.
     */
    protected function transformRecords(Collection $records, array $columns = []): array
    {
        if (empty($columns)) {
            return $records->toArray();
        }

        $data = [];
        foreach ($records as $record) {
            $row = [];
            foreach ($columns as $key => $config) {
                if (is_string($config)) {
                    // Simple column mapping
                    $row[$key] = $record->{$config} ?? null;
                } elseif (is_array($config)) {
                    // Complex column configuration
                    $value = $record->{$config['attribute'] ?? $key} ?? null;

                    // Apply formatter if provided
                    if (isset($config['formatter']) && is_callable($config['formatter'])) {
                        $value = call_user_func($config['formatter'], $value, $record);
                    }

                    $row[$key] = $value;
                } elseif (is_callable($config)) {
                    // Callback for custom value
                    $row[$key] = call_user_func($config, $record);
                }
            }
            $data[] = $row;
        }

        return $data;
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
     * Get the limit from the request.
     */
    protected function getLimit(Request $request): int
    {
        return (int) $request->get('limit', 0);
    }

    /**
     * Apply sorting to the query based on request parameters.
     */
    protected function applySorting(Builder $query, Request $request): void
    {
        $sortBy = $request->get('sort_by');
        $sortDirection = $request->get('sort_direction', 'asc');

        if ($sortBy) {
            $query->orderBy($sortBy, $sortDirection);
        }
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

    /**
     * Format a date value for display.
     */
    protected function formatDate($date, string $format = 'Y-m-d H:i:s'): ?string
    {
        if (! $date) {
            return null;
        }

        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return $date->format($format);
    }

    /**
     * Format a number value for display.
     */
    protected function formatNumber($value, int $decimals = 0): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return number_format((float) $value, $decimals);
    }

    /**
     * Format a currency value for display.
     */
    protected function formatCurrency($value, string $symbol = '$', int $decimals = 2): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        return $symbol.number_format((float) $value, $decimals);
    }

    /**
     * Truncate text to a specific length.
     */
    protected function truncateText(?string $text, int $length = 50): ?string
    {
        if (! $text || strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length).'...';
    }
}
