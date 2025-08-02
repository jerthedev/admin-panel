<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Text Filter
 *
 * Filter that provides text input for filtering resources
 * by text-based searches and comparisons.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Filters
 */
class TextFilter extends Filter
{
    /**
     * The filter's component.
     */
    public string $component = 'TextFilter';

    /**
     * The database column to filter on.
     */
    protected ?string $column = null;

    /**
     * The comparison operator.
     */
    protected string $operator = 'LIKE';

    /**
     * Whether to add wildcards around the search term.
     */
    protected bool $wildcards = true;

    /**
     * Whether the search is case sensitive.
     */
    protected bool $caseSensitive = false;

    /**
     * Multiple columns to search in.
     */
    protected array $columns = [];

    /**
     * Custom filter callback.
     */
    protected ?\Closure $filterCallback = null;

    /**
     * Create a new text filter instance.
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

        $searchValue = $this->prepareSearchValue((string) $value);
        $columns = $this->getSearchColumns();

        if (count($columns) === 1) {
            return $query->where($columns[0], $this->operator, $searchValue);
        }

        return $query->where(function (Builder $q) use ($columns, $searchValue) {
            foreach ($columns as $column) {
                $q->orWhere($column, $this->operator, $searchValue);
            }
        });
    }

    /**
     * Get the filter's available options.
     */
    public function options(Request $request): array
    {
        return [
            'placeholder' => "Search {$this->name}...",
            'operator' => $this->operator,
            'wildcards' => $this->wildcards,
            'caseSensitive' => $this->caseSensitive,
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
     * Set multiple columns to search in.
     */
    public function withColumns(array $columns): static
    {
        $this->columns = $columns;

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
     * Set whether to add wildcards.
     */
    public function withWildcards(bool $wildcards = true): static
    {
        $this->wildcards = $wildcards;

        return $this;
    }

    /**
     * Set case sensitivity.
     */
    public function withCaseSensitive(bool $caseSensitive = true): static
    {
        $this->caseSensitive = $caseSensitive;

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
     * Configure for exact matches.
     */
    public function exactMatch(): static
    {
        return $this->withOperator('=')->withWildcards(false);
    }

    /**
     * Configure for starts with search.
     */
    public function startsWith(): static
    {
        return $this->withOperator('LIKE')->withCallback(function (Builder $query, string $value) {
            $columns = $this->getSearchColumns();
            $searchValue = $value . '%';

            if (count($columns) === 1) {
                return $query->where($columns[0], 'LIKE', $searchValue);
            }

            return $query->where(function (Builder $q) use ($columns, $searchValue) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', $searchValue);
                }
            });
        });
    }

    /**
     * Configure for ends with search.
     */
    public function endsWith(): static
    {
        return $this->withOperator('LIKE')->withCallback(function (Builder $query, string $value) {
            $columns = $this->getSearchColumns();
            $searchValue = '%' . $value;

            if (count($columns) === 1) {
                return $query->where($columns[0], 'LIKE', $searchValue);
            }

            return $query->where(function (Builder $q) use ($columns, $searchValue) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', $searchValue);
                }
            });
        });
    }

    /**
     * Configure for full-text search.
     */
    public function fullText(): static
    {
        return $this->withCallback(function (Builder $query, string $value) {
            $columns = $this->getSearchColumns();

            if (count($columns) === 1) {
                return $query->whereRaw("MATCH({$columns[0]}) AGAINST(? IN BOOLEAN MODE)", [$value]);
            }

            $columnList = implode(',', $columns);
            return $query->whereRaw("MATCH({$columnList}) AGAINST(? IN BOOLEAN MODE)", [$value]);
        });
    }

    /**
     * Prepare the search value based on configuration.
     */
    protected function prepareSearchValue(string $value): string
    {
        if ($this->operator === 'LIKE' && $this->wildcards) {
            return "%{$value}%";
        }

        return $value;
    }

    /**
     * Get the columns to search in.
     */
    protected function getSearchColumns(): array
    {
        if (! empty($this->columns)) {
            return $this->columns;
        }

        return [$this->column ?? $this->key];
    }

    /**
     * Get the filter's metadata for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge(parent::jsonSerialize(), [
            'operator' => $this->operator,
            'wildcards' => $this->wildcards,
            'caseSensitive' => $this->caseSensitive,
            'columns' => $this->getSearchColumns(),
        ]);
    }
}
