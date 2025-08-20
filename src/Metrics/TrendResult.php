<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use JsonSerializable;

/**
 * Trend Result Class.
 *
 * Represents the result of a Trend metric calculation, containing time-series data
 * for line chart visualization. This class provides Nova-compatible API for
 * trend metric results with support for formatting and data transformation.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class TrendResult implements JsonSerializable
{
    /**
     * The trend data points.
     */
    protected array $trend = [];

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
     * Whether to show the current value.
     */
    protected bool $showCurrentValue = false;

    /**
     * Whether to show the trend sum.
     */
    protected bool $showTrendSum = false;

    /**
     * Value transformation callback.
     */
    protected $transformer = null;

    /**
     * Create a new trend result instance.
     */
    public function __construct(array $trend = [])
    {
        $this->trend = $trend;
    }

    /**
     * Create a new trend result instance.
     */
    public static function make(array $trend = []): static
    {
        return new static($trend);
    }

    /**
     * Set the trend data.
     */
    public function trend(array $trend): static
    {
        $this->trend = $trend;

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
     * Show the current value (latest data point).
     */
    public function showCurrentValue(): static
    {
        $this->showCurrentValue = true;

        return $this;
    }

    /**
     * Show the trend sum (sum of all data points).
     */
    public function showTrendSum(): static
    {
        $this->showTrendSum = true;

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
     * Get the trend data.
     */
    public function getTrend(): array
    {
        if (! $this->transformer) {
            return $this->trend;
        }

        $transformed = [];
        foreach ($this->trend as $key => $value) {
            $transformed[$key] = call_user_func($this->transformer, $value);
        }

        return $transformed;
    }

    /**
     * Get the current value (latest data point).
     */
    public function getCurrentValue(): mixed
    {
        $trend = $this->getTrend();

        if (empty($trend)) {
            return null;
        }

        return end($trend);
    }

    /**
     * Get the trend sum.
     */
    public function getTrendSum(): mixed
    {
        $trend = $this->getTrend();

        if (empty($trend)) {
            return 0;
        }

        return array_sum($trend);
    }

    /**
     * Check if the result has no data.
     */
    public function hasNoData(): bool
    {
        return empty($this->trend);
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
     * Get chart data formatted for frontend consumption.
     */
    public function getChartData(): array
    {
        $trend = $this->getTrend();
        $chartData = [];

        foreach ($trend as $key => $value) {
            $chartData[] = [
                'label' => $key,
                'value' => $value,
                'formatted_value' => $this->getFormattedValue($value),
            ];
        }

        return $chartData;
    }

    /**
     * Convert the result to an array for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        $data = [
            'trend' => $this->getTrend(),
            'chart_data' => $this->getChartData(),
            'has_no_data' => $this->hasNoData(),
        ];

        if ($this->showCurrentValue) {
            $currentValue = $this->getCurrentValue();
            $data['current_value'] = $currentValue;
            $data['formatted_current_value'] = $this->getFormattedValue($currentValue);
        }

        if ($this->showTrendSum) {
            $trendSum = $this->getTrendSum();
            $data['trend_sum'] = $trendSum;
            $data['formatted_trend_sum'] = $this->getFormattedValue($trendSum);
        }

        return $data;
    }
}
