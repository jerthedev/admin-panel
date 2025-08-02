<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Date Filter
 *
 * Filter that provides date selection for filtering resources
 * by specific dates or date comparisons.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Filters
 */
class DateFilter extends Filter
{
    /**
     * The filter's component.
     */
    public string $component = 'DateFilter';

    /**
     * The database column to filter on.
     */
    protected ?string $column = null;

    /**
     * The comparison operator.
     */
    protected string $operator = '=';

    /**
     * Whether to compare only the date part.
     */
    protected bool $dateOnly = true;

    /**
     * Custom filter callback.
     */
    protected ?\Closure $filterCallback = null;

    /**
     * Create a new date filter instance.
     */
    public static function make(string $name, ?string $key = null): static
    {
        return new static($name, $key);
    }

    /**
     * Apply the filter to the given query.
     */
    public function apply(Builder $query, mixed $value): Builder
    {
        if ($value === null || $value === '') {
            return $query;
        }

        if ($this->filterCallback) {
            return call_user_func($this->filterCallback, $query, $value);
        }

        $column = $this->column ?? $this->key;

        try {
            $date = Carbon::parse($value);

            if ($this->dateOnly) {
                return match ($this->operator) {
                    '=' => $query->whereDate($column, $date->toDateString()),
                    '>' => $query->whereDate($column, '>', $date->toDateString()),
                    '>=' => $query->whereDate($column, '>=', $date->toDateString()),
                    '<' => $query->whereDate($column, '<', $date->toDateString()),
                    '<=' => $query->whereDate($column, '<=', $date->toDateString()),
                    default => $query->whereDate($column, $date->toDateString()),
                };
            } else {
                return $query->where($column, $this->operator, $date);
            }
        } catch (\Exception $e) {
            return $query;
        }
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
     * Set the comparison operator.
     */
    public function withOperator(string $operator): static
    {
        $this->operator = $operator;

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
     * Filter for dates after the given date.
     */
    public function after(): static
    {
        return $this->withOperator('>');
    }

    /**
     * Filter for dates on or after the given date.
     */
    public function afterOrOn(): static
    {
        return $this->withOperator('>=');
    }

    /**
     * Filter for dates before the given date.
     */
    public function before(): static
    {
        return $this->withOperator('<');
    }

    /**
     * Filter for dates on or before the given date.
     */
    public function beforeOrOn(): static
    {
        return $this->withOperator('<=');
    }

    /**
     * Filter for exact date matches.
     */
    public function exactDate(): static
    {
        return $this->withOperator('=')->withDateOnly(true);
    }

    /**
     * Filter for exact datetime matches.
     */
    public function exactDateTime(): static
    {
        return $this->withOperator('=')->withDateOnly(false);
    }

    /**
     * Get the filter's metadata for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'operator' => $this->operator,
            'dateOnly' => $this->dateOnly,
            'column' => $this->column ?? $this->key,
        ]);
    }
}
