<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use JsonSerializable;

/**
 * Value Result Class.
 *
 * Represents the result of a Value metric calculation, including the current value,
 * previous value for comparison, and formatting options. This class provides
 * Nova-compatible API for value metric results.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ValueResult implements JsonSerializable
{
    /**
     * The current value.
     */
    protected mixed $value;

    /**
     * The previous value for comparison.
     */
    protected mixed $previous = null;

    /**
     * The value prefix.
     */
    protected ?string $prefix = null;

    /**
     * The value suffix.
     */
    protected ?string $suffix = null;

    /**
     * The currency symbol.
     */
    protected ?string $currency = null;

    /**
     * The format configuration.
     */
    protected ?array $format = null;

    /**
     * Whether zero results are allowed.
     */
    protected bool $allowZeroResult = false;

    /**
     * Value transformation callback.
     */
    protected $transformer = null;

    /**
     * Create a new value result instance.
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Create a new value result instance.
     */
    public static function make(mixed $value): static
    {
        return new static($value);
    }

    /**
     * Set the previous value for comparison.
     */
    public function previous(mixed $previous): static
    {
        $this->previous = $previous;

        return $this;
    }

    /**
     * Set the value prefix.
     */
    public function prefix(string $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Set the value suffix.
     */
    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Set the currency symbol.
     */
    public function currency(?string $symbol = '$'): static
    {
        $this->currency = $symbol;

        return $this;
    }

    /**
     * Set the format configuration.
     */
    public function format(array $format): static
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Allow zero results.
     */
    public function allowZeroResult(): static
    {
        $this->allowZeroResult = true;

        return $this;
    }

    /**
     * Transform the value before display.
     */
    public function transform(callable $callback): static
    {
        $this->transformer = $callback;

        return $this;
    }

    /**
     * Get the current value.
     */
    public function getValue(): mixed
    {
        $value = $this->value;

        if ($this->transformer) {
            $value = call_user_func($this->transformer, $value);
        }

        return $value;
    }

    /**
     * Get the previous value.
     */
    public function getPrevious(): mixed
    {
        $previous = $this->previous;

        if ($this->transformer && $previous !== null) {
            $previous = call_user_func($this->transformer, $previous);
        }

        return $previous;
    }

    /**
     * Calculate the percentage change.
     */
    public function getPercentageChange(): ?float
    {
        $current = $this->getValue();
        $previous = $this->getPrevious();

        if ($previous === null || $previous == 0) {
            return null;
        }

        return (($current - $previous) / $previous) * 100;
    }

    /**
     * Get the change direction.
     */
    public function getChangeDirection(): ?string
    {
        $change = $this->getPercentageChange();

        if ($change === null) {
            return null;
        }

        return $change >= 0 ? 'increase' : 'decrease';
    }

    /**
     * Check if the result has no data.
     */
    public function hasNoData(): bool
    {
        $value = $this->getValue();

        if ($this->allowZeroResult) {
            return $value === null;
        }

        return $value === null || $value === 0;
    }

    /**
     * Get the formatted value for display.
     */
    public function getFormattedValue(): string
    {
        $value = $this->getValue();

        if ($value === null) {
            return '';
        }

        $formatted = $this->formatValue($value);

        if ($this->prefix) {
            $formatted = $this->prefix.$formatted;
        }

        if ($this->suffix) {
            $formatted = $formatted.$this->suffix;
        }

        if ($this->currency) {
            $formatted = $this->currency.$formatted;
        }

        return $formatted;
    }

    /**
     * Format a value based on the configuration.
     */
    protected function formatValue(mixed $value): string
    {
        if (! is_numeric($value)) {
            return (string) $value;
        }

        if ($this->format) {
            // Apply custom formatting based on format configuration
            // This would integrate with a formatting library like Numbro
            return $this->applyCustomFormat($value, $this->format);
        }

        // Default number formatting
        return number_format((float) $value);
    }

    /**
     * Apply custom formatting.
     */
    protected function applyCustomFormat(mixed $value, array $format): string
    {
        // Basic implementation - in a real scenario, this would integrate
        // with a JavaScript-compatible formatting library
        $formatted = (float) $value;

        if (isset($format['thousandSeparated']) && $format['thousandSeparated']) {
            $decimals = $format['mantissa'] ?? 0;

            return number_format($formatted, $decimals);
        }

        return (string) $formatted;
    }

    /**
     * Convert the result to an array for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        $data = [
            'value' => $this->getValue(),
            'formatted_value' => $this->getFormattedValue(),
            'has_no_data' => $this->hasNoData(),
        ];

        if ($this->previous !== null) {
            $data['previous'] = $this->getPrevious();
            $data['percentage_change'] = $this->getPercentageChange();
            $data['change_direction'] = $this->getChangeDirection();
        }

        return $data;
    }
}
