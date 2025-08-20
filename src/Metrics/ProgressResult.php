<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use JsonSerializable;

/**
 * Progress Result Class.
 *
 * Represents the result of a Progress metric calculation, containing progress
 * data with target values for progress bar visualization. This class provides
 * Nova-compatible API for progress metric results.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class ProgressResult implements JsonSerializable
{
    /**
     * The current progress value.
     */
    protected mixed $value;

    /**
     * The target value.
     */
    protected mixed $target;

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
     * Whether to avoid unwanted progress (values exceeding target).
     */
    protected bool $avoidUnwantedProgress = false;

    /**
     * Value transformation callback.
     */
    protected $transformer = null;

    /**
     * Create a new progress result instance.
     */
    public function __construct(mixed $value, mixed $target)
    {
        $this->value = $value;
        $this->target = $target;
    }

    /**
     * Create a new progress result instance.
     */
    public static function make(mixed $value, mixed $target): static
    {
        return new static($value, $target);
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
     * Avoid unwanted progress (cap at 100%).
     */
    public function avoidUnwantedProgress(): static
    {
        $this->avoidUnwantedProgress = true;

        return $this;
    }

    /**
     * Transform values before display.
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
     * Get the target value.
     */
    public function getTarget(): mixed
    {
        $target = $this->target;

        if ($this->transformer) {
            $target = call_user_func($this->transformer, $target);
        }

        return $target;
    }

    /**
     * Calculate the progress percentage.
     */
    public function getPercentage(): float
    {
        $value = $this->getValue();
        $target = $this->getTarget();

        if ($target == 0) {
            return 0;
        }

        $percentage = ($value / $target) * 100;

        if ($this->avoidUnwantedProgress && $percentage > 100) {
            return 100;
        }

        return round($percentage, 1);
    }

    /**
     * Check if the progress is complete.
     */
    public function isComplete(): bool
    {
        return $this->getPercentage() >= 100;
    }

    /**
     * Check if the progress exceeds the target.
     */
    public function exceedsTarget(): bool
    {
        return $this->getValue() > $this->getTarget();
    }

    /**
     * Get the remaining value to reach the target.
     */
    public function getRemaining(): mixed
    {
        $value = $this->getValue();
        $target = $this->getTarget();

        $remaining = $target - $value;

        return max(0, $remaining);
    }

    /**
     * Check if the result has no data.
     */
    public function hasNoData(): bool
    {
        return $this->getValue() === null || $this->getTarget() === null;
    }

    /**
     * Get the formatted value for display.
     */
    public function getFormattedValue(mixed $value): string
    {
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
            return $this->applyCustomFormat($value, $this->format);
        }

        return number_format((float) $value);
    }

    /**
     * Apply custom formatting.
     */
    protected function applyCustomFormat(mixed $value, array $format): string
    {
        $formatted = (float) $value;

        if (isset($format['thousandSeparated']) && $format['thousandSeparated']) {
            $decimals = $format['mantissa'] ?? 0;

            return number_format($formatted, $decimals);
        }

        return (string) $formatted;
    }

    /**
     * Get the progress bar color based on percentage.
     */
    public function getProgressColor(): string
    {
        $percentage = $this->getPercentage();

        if ($percentage >= 100) {
            return '#10B981'; // Green
        } elseif ($percentage >= 75) {
            return '#3B82F6'; // Blue
        } elseif ($percentage >= 50) {
            return '#F59E0B'; // Yellow
        } else {
            return '#EF4444'; // Red
        }
    }

    /**
     * Convert the result to an array for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        $value = $this->getValue();
        $target = $this->getTarget();
        $remaining = $this->getRemaining();

        return [
            'value' => $value,
            'target' => $target,
            'remaining' => $remaining,
            'formatted_value' => $this->getFormattedValue($value),
            'formatted_target' => $this->getFormattedValue($target),
            'formatted_remaining' => $this->getFormattedValue($remaining),
            'percentage' => $this->getPercentage(),
            'is_complete' => $this->isComplete(),
            'exceeds_target' => $this->exceedsTarget(),
            'progress_color' => $this->getProgressColor(),
            'has_no_data' => $this->hasNoData(),
        ];
    }
}
