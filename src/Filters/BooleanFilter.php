<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Boolean Filter
 *
 * Filter that provides true/false options for filtering resources
 * by boolean or boolean-like values.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Filters
 */
class BooleanFilter extends Filter
{
    /**
     * The filter's component.
     */
    public string $component = 'BooleanFilter';

    /**
     * The database column to filter on.
     */
    protected ?string $column = null;

    /**
     * The true value label.
     */
    protected string $trueLabel = 'Yes';

    /**
     * The false value label.
     */
    protected string $falseLabel = 'No';

    /**
     * The true value in the database.
     */
    protected mixed $trueValue = true;

    /**
     * The false value in the database.
     */
    protected mixed $falseValue = false;

    /**
     * Custom filter callback.
     */
    protected ?\Closure $filterCallback = null;

    /**
     * Create a new boolean filter instance.
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
        $filterValue = $value === 'true' || $value === '1' || $value === 1
            ? $this->trueValue
            : $this->falseValue;

        return $query->where($column, $filterValue);
    }

    /**
     * Get the filter's available options.
     */
    public function options(Request $request): array
    {
        return [
            'true' => $this->trueLabel,
            'false' => $this->falseLabel,
        ];
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
     * Set the labels for true/false values.
     */
    public function withLabels(string $trueLabel, string $falseLabel): static
    {
        $this->trueLabel = $trueLabel;
        $this->falseLabel = $falseLabel;

        return $this;
    }

    /**
     * Set the database values for true/false.
     */
    public function withValues(mixed $trueValue, mixed $falseValue): static
    {
        $this->trueValue = $trueValue;
        $this->falseValue = $falseValue;

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
     * Filter for active/inactive status.
     */
    public static function forActiveStatus(): static
    {
        return static::make('Active Status', 'is_active')
            ->withLabels('Active', 'Inactive')
            ->withValues(1, 0);
    }

    /**
     * Filter for published/unpublished status.
     */
    public function forPublishedStatus(): static
    {
        return $this->withLabels('Published', 'Unpublished')
            ->withValues(1, 0)
            ->withColumn('is_published');
    }

    /**
     * Filter for verified/unverified status.
     */
    public function forVerifiedStatus(): static
    {
        return $this->withLabels('Verified', 'Unverified')
            ->withValues(1, 0)
            ->withColumn('is_verified');
    }

    /**
     * Filter for featured/not featured status.
     */
    public function forFeaturedStatus(): static
    {
        return $this->withLabels('Featured', 'Not Featured')
            ->withValues(1, 0)
            ->withColumn('is_featured');
    }

    /**
     * Filter for records with/without a specific relationship.
     */
    public function forRelationshipExists(string $relationship): static
    {
        return $this->withLabels("Has {$relationship}", "No {$relationship}")
            ->withCallback(function (Builder $query, string $value) use ($relationship) {
                $hasRelationship = $value === 'true';

                return $hasRelationship
                    ? $query->has($relationship)
                    : $query->doesntHave($relationship);
            });
    }

    /**
     * Filter for null/not null values.
     */
    public function forNullValues(string $column, string $hasLabel = 'Has Value', string $nullLabel = 'No Value'): static
    {
        return $this->withLabels($hasLabel, $nullLabel)
            ->withCallback(function (Builder $query, string $value) use ($column) {
                $hasValue = $value === 'true';

                return $hasValue
                    ? $query->whereNotNull($column)
                    : $query->whereNull($column);
            });
    }
}
