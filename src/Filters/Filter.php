<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Base Filter Class
 * 
 * Abstract base class for all admin panel filters providing common
 * functionality and interface for filtering resources.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Filters
 */
abstract class Filter
{
    /**
     * The filter's component.
     */
    public string $component = 'SelectFilter';

    /**
     * The filter's name.
     */
    public string $name;

    /**
     * The filter's key.
     */
    public string $key;

    /**
     * The filter's default value.
     */
    public mixed $default = null;

    /**
     * Create a new filter instance.
     */
    public function __construct(string $name, ?string $key = null)
    {
        $this->name = $name;
        $this->key = $key ?? $this->generateKey($name);
    }

    /**
     * Apply the filter to the given query.
     */
    abstract public function apply(Builder $query, mixed $value): Builder;

    /**
     * Get the filter's available options.
     */
    abstract public function options(Request $request): array;

    /**
     * Determine if the filter should be displayed.
     */
    public function authorize(Request $request): bool
    {
        return true;
    }

    /**
     * Get the default value for the filter.
     */
    public function default(): mixed
    {
        return $this->default;
    }

    /**
     * Set the default value for the filter.
     */
    public function withDefault(mixed $default): static
    {
        $this->default = $default;
        
        return $this;
    }

    /**
     * Get the filter's component.
     */
    public function component(): string
    {
        return $this->component;
    }

    /**
     * Set the filter's component.
     */
    public function withComponent(string $component): static
    {
        $this->component = $component;
        
        return $this;
    }

    /**
     * Generate a key from the filter name.
     */
    protected function generateKey(string $name): string
    {
        return strtolower(str_replace(' ', '_', $name));
    }

    /**
     * Get the filter's metadata for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'key' => $this->key,
            'component' => $this->component(),
            'options' => $this->options(request()),
            'default' => $this->default(),
        ];
    }

    /**
     * Create a new select filter.
     */
    public static function select(string $name, ?string $key = null): SelectFilter
    {
        return new SelectFilter($name, $key);
    }

    /**
     * Create a new boolean filter.
     */
    public static function boolean(string $name, ?string $key = null): BooleanFilter
    {
        return new BooleanFilter($name, $key);
    }

    /**
     * Create a new date filter.
     */
    public static function date(string $name, ?string $key = null): DateFilter
    {
        return new DateFilter($name, $key);
    }

    /**
     * Create a new date range filter.
     */
    public static function dateRange(string $name, ?string $key = null): DateRangeFilter
    {
        return new DateRangeFilter($name, $key);
    }

    /**
     * Create a new number range filter.
     */
    public static function numberRange(string $name, ?string $key = null): NumberRangeFilter
    {
        return new NumberRangeFilter($name, $key);
    }

    /**
     * Create a new text filter.
     */
    public static function text(string $name, ?string $key = null): TextFilter
    {
        return new TextFilter($name, $key);
    }
}
