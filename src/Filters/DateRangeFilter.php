<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Date Range Filter
 * 
 * Filter that provides date range selection for filtering resources
 * between two dates.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Filters
 */
class DateRangeFilter extends Filter
{
    /**
     * The filter's component.
     */
    public string $component = 'DateRangeFilter';

    /**
     * The database column to filter on.
     */
    protected ?string $column = null;

    /**
     * Whether to compare only the date part.
     */
    protected bool $dateOnly = true;

    /**
     * Custom filter callback.
     */
    protected ?\Closure $filterCallback = null;

    /**
     * Apply the filter to the given query.
     */
    public function apply(Builder $query, mixed $value): Builder
    {
        if ($value === null || $value === '' || ! is_array($value)) {
            return $query;
        }

        if ($this->filterCallback) {
            return call_user_func($this->filterCallback, $query, $value);
        }

        $column = $this->column ?? $this->key;
        $startDate = $value['start'] ?? null;
        $endDate = $value['end'] ?? null;

        if (! $startDate && ! $endDate) {
            return $query;
        }

        try {
            if ($startDate && $endDate) {
                $start = Carbon::parse($startDate);
                $end = Carbon::parse($endDate);

                if ($this->dateOnly) {
                    return $query->whereBetween($column, [
                        $start->startOfDay(),
                        $end->endOfDay()
                    ]);
                } else {
                    return $query->whereBetween($column, [$start, $end]);
                }
            } elseif ($startDate) {
                $start = Carbon::parse($startDate);
                
                if ($this->dateOnly) {
                    return $query->whereDate($column, '>=', $start->toDateString());
                } else {
                    return $query->where($column, '>=', $start);
                }
            } elseif ($endDate) {
                $end = Carbon::parse($endDate);
                
                if ($this->dateOnly) {
                    return $query->whereDate($column, '<=', $end->toDateString());
                } else {
                    return $query->where($column, '<=', $end);
                }
            }
        } catch (\Exception $e) {
            return $query;
        }

        return $query;
    }

    /**
     * Get the filter's available options.
     */
    public function options(Request $request): array
    {
        return [];
    }

    /**
     * Set the database column to filter on.
     */
    public function withColumn(string $column): static
    {
        $this->column = $column;
        
        return $this;
    }

    /**
     * Set whether to compare only the date part.
     */
    public function withDateOnly(bool $dateOnly = true): static
    {
        $this->dateOnly = $dateOnly;
        
        return $this;
    }

    /**
     * Set a custom filter callback.
     */
    public function withCallback(\Closure $callback): static
    {
        $this->filterCallback = $callback;
        
        return $this;
    }

    /**
     * Filter for date ranges only (ignore time).
     */
    public function dateOnly(): static
    {
        return $this->withDateOnly(true);
    }

    /**
     * Filter for datetime ranges (include time).
     */
    public function dateTime(): static
    {
        return $this->withDateOnly(false);
    }

    /**
     * Get the filter's metadata for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'dateOnly' => $this->dateOnly,
            'column' => $this->column ?? $this->key,
        ]);
    }
}
