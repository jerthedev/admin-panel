<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Metrics;

use JsonSerializable;

/**
 * Partition Result Class.
 *
 * Represents the result of a Partition metric calculation, containing categorical
 * data for pie chart visualization. This class provides Nova-compatible API for
 * partition metric results with support for custom labels and colors.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PartitionResult implements JsonSerializable
{
    /**
     * The partition data.
     */
    protected array $partitions = [];

    /**
     * Custom labels for partitions.
     */
    protected array $labels = [];

    /**
     * Custom colors for partitions.
     */
    protected array $colors = [];

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
     * Value transformation callback.
     */
    protected $transformer = null;

    /**
     * Create a new partition result instance.
     */
    public function __construct(array $partitions = [])
    {
        $this->partitions = $partitions;
    }

    /**
     * Create a new partition result instance.
     */
    public static function make(array $partitions = []): static
    {
        return new static($partitions);
    }

    /**
     * Set the partition data.
     */
    public function partitions(array $partitions): static
    {
        $this->partitions = $partitions;

        return $this;
    }

    /**
     * Set custom labels for partitions.
     */
    public function label(string $key, string $label): static
    {
        $this->labels[$key] = $label;

        return $this;
    }

    /**
     * Set multiple custom labels.
     */
    public function labels(array $labels): static
    {
        $this->labels = array_merge($this->labels, $labels);

        return $this;
    }

    /**
     * Set custom color for a partition.
     */
    public function color(string $key, string $color): static
    {
        $this->colors[$key] = $color;

        return $this;
    }

    /**
     * Set multiple custom colors.
     */
    public function colors(array $colors): static
    {
        $this->colors = array_merge($this->colors, $colors);

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
     * Transform values before display.
     */
    public function transform(callable $callback): static
    {
        $this->transformer = $callback;

        return $this;
    }

    /**
     * Get the partition data.
     */
    public function getPartitions(): array
    {
        if (! $this->transformer) {
            return $this->partitions;
        }

        $transformed = [];
        foreach ($this->partitions as $key => $value) {
            $transformed[$key] = call_user_func($this->transformer, $value);
        }

        return $transformed;
    }

    /**
     * Get the total value of all partitions.
     */
    public function getTotal(): mixed
    {
        $partitions = $this->getPartitions();

        return array_sum($partitions);
    }

    /**
     * Check if the result has no data.
     */
    public function hasNoData(): bool
    {
        return empty($this->partitions) || $this->getTotal() == 0;
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
        $partitions = $this->getPartitions();
        $total = $this->getTotal();
        $chartData = [];
        $defaultColors = $this->getDefaultColors();
        $colorIndex = 0;

        foreach ($partitions as $key => $value) {
            $percentage = $total > 0 ? ($value / $total) * 100 : 0;

            $chartData[] = [
                'key' => $key,
                'label' => $this->labels[$key] ?? $key,
                'value' => $value,
                'formatted_value' => $this->getFormattedValue($value),
                'percentage' => round($percentage, 1),
                'color' => $this->colors[$key] ?? $defaultColors[$colorIndex % count($defaultColors)],
            ];

            $colorIndex++;
        }

        return $chartData;
    }

    /**
     * Get default colors for partitions.
     */
    protected function getDefaultColors(): array
    {
        return [
            '#3B82F6', // Blue
            '#10B981', // Green
            '#F59E0B', // Yellow
            '#EF4444', // Red
            '#8B5CF6', // Purple
            '#F97316', // Orange
            '#06B6D4', // Cyan
            '#84CC16', // Lime
            '#EC4899', // Pink
            '#6B7280', // Gray
        ];
    }

    /**
     * Convert the result to an array for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return [
            'partitions' => $this->getPartitions(),
            'chart_data' => $this->getChartData(),
            'total' => $this->getTotal(),
            'formatted_total' => $this->getFormattedValue($this->getTotal()),
            'has_no_data' => $this->hasNoData(),
        ];
    }
}
