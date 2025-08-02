<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Number Range Filter
 * 
 * Filter that provides number range selection for filtering resources
 * between minimum and maximum numeric values.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Filters
 */
class NumberRangeFilter extends Filter
{
    /**
     * The filter's component.
     */
    public string $component = 'NumberRangeFilter';

    /**
     * The database column to filter on.
     */
    protected ?string $column = null;

    /**
     * The minimum allowed value.
     */
    protected ?float $min = null;

    /**
     * The maximum allowed value.
     */
    protected ?float $max = null;

    /**
     * The step value for the range input.
     */
    protected float $step = 1;

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
        $minValue = $value['min'] ?? null;
        $maxValue = $value['max'] ?? null;

        if ($minValue === null && $maxValue === null) {
            return $query;
        }

        if ($minValue !== null && $maxValue !== null) {
            return $query->whereBetween($column, [(float) $minValue, (float) $maxValue]);
        } elseif ($minValue !== null) {
            return $query->where($column, '>=', (float) $minValue);
        } elseif ($maxValue !== null) {
            return $query->where($column, '<=', (float) $maxValue);
        }

        return $query;
    }

    /**
     * Get the filter's available options.
     */
    public function options(Request $request): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
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
     * Set the minimum and maximum values.
     */
    public function withRange(?float $min, ?float $max): static
    {
        $this->min = $min;
        $this->max = $max;
        
        return $this;
    }

    /**
     * Set the step value.
     */
    public function withStep(float $step): static
    {
        $this->step = $step;
        
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
     * Configure for price ranges.
     */
    public function forPrices(): static
    {
        return $this->withStep(0.01)
            ->withRange(0, null);
    }

    /**
     * Configure for age ranges.
     */
    public function forAges(): static
    {
        return $this->withStep(1)
            ->withRange(0, 120);
    }

    /**
     * Configure for rating ranges.
     */
    public function forRatings(): static
    {
        return $this->withStep(0.1)
            ->withRange(0, 5);
    }

    /**
     * Configure for percentage ranges.
     */
    public function forPercentages(): static
    {
        return $this->withStep(1)
            ->withRange(0, 100);
    }

    /**
     * Auto-detect range from database values.
     */
    public function withAutoRange(string $modelClass): static
    {
        $model = new $modelClass();
        $column = $this->column ?? $this->key;
        
        $min = $model->min($column);
        $max = $model->max($column);
        
        return $this->withRange($min, $max);
    }

    /**
     * Get the filter's metadata for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
            'column' => $this->column ?? $this->key,
        ]);
    }
}
