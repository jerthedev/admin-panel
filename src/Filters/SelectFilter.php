<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Select Filter
 *
 * Filter that provides a dropdown selection with predefined options
 * for filtering resources by specific values.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Filters
 */
class SelectFilter extends Filter
{
    /**
     * The filter's component.
     */
    public string $component = 'SelectFilter';

    /**
     * The filter's options.
     */
    protected array $filterOptions = [];

    /**
     * The database column to filter on.
     */
    protected ?string $column = null;

    /**
     * Custom filter callback.
     */
    protected ?\Closure $filterCallback = null;

    /**
     * Create a new select filter instance.
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

        return $query->where($column, $value);
    }

    /**
     * Get the filter's available options.
     */
    public function options(Request $request): array
    {
        return $this->filterOptions;
    }

    /**
     * Set the filter options.
     */
    public function withOptions(array $options): static
    {
        $this->filterOptions = $options;

        return $this;
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
     * Set a custom filter callback.
     */
    public function withCallback(\Closure $callback): static
    {
        $this->filterCallback = $callback;

        return $this;
    }

    /**
     * Create options from an Enum class.
     */
    public function withEnum(string $enumClass): static
    {
        if (! enum_exists($enumClass)) {
            throw new \InvalidArgumentException("Enum class {$enumClass} does not exist.");
        }

        $options = [];
        foreach ($enumClass::cases() as $case) {
            $options[$case->value] = $case->name;
        }

        return $this->withOptions($options);
    }

    /**
     * Create options from a model's distinct values.
     */
    public function withDistinctValues(string $modelClass, string $column): static
    {
        $model = new $modelClass();
        $values = $model->distinct($column)
            ->whereNotNull($column)
            ->pluck($column)
            ->sort()
            ->mapWithKeys(function ($value) {
                return [$value => ucfirst(str_replace('_', ' ', $value))];
            })
            ->toArray();

        return $this->withOptions($values);
    }

    /**
     * Create options from a relationship.
     */
    public function withRelationship(string $modelClass, string $relationship, string $labelColumn = 'name'): static
    {
        $model = new $modelClass();
        $related = $model->{$relationship}()->getModel();

        $options = $related->all()
            ->pluck($labelColumn, $related->getKeyName())
            ->toArray();

        return $this->withOptions($options);
    }

    /**
     * Filter by status values.
     */
    public function withStatusOptions(): static
    {
        return $this->withOptions([
            'active' => 'Active',
            'inactive' => 'Inactive',
            'pending' => 'Pending',
            'suspended' => 'Suspended',
        ]);
    }

    /**
     * Filter by boolean-like values.
     */
    public function withBooleanOptions(string $trueLabel = 'Yes', string $falseLabel = 'No'): static
    {
        return $this->withOptions([
            '1' => $trueLabel,
            '0' => $falseLabel,
        ]);
    }

    /**
     * Filter by date periods.
     */
    public function withDatePeriods(): static
    {
        return $this->withOptions([
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_year' => 'This Year',
            'last_year' => 'Last Year',
        ])->withCallback(function (Builder $query, string $period) {
            $column = $this->column ?? 'created_at';

            return match ($period) {
                'today' => $query->whereDate($column, today()),
                'yesterday' => $query->whereDate($column, today()->subDay()),
                'this_week' => $query->whereBetween($column, [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ]),
                'last_week' => $query->whereBetween($column, [
                    now()->subWeek()->startOfWeek(),
                    now()->subWeek()->endOfWeek()
                ]),
                'this_month' => $query->whereBetween($column, [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ]),
                'last_month' => $query->whereBetween($column, [
                    now()->subMonth()->startOfMonth(),
                    now()->subMonth()->endOfMonth()
                ]),
                'this_year' => $query->whereBetween($column, [
                    now()->startOfYear(),
                    now()->endOfYear()
                ]),
                'last_year' => $query->whereBetween($column, [
                    now()->subYear()->startOfYear(),
                    now()->subYear()->endOfYear()
                ]),
                default => $query
            };
        });
    }
}
