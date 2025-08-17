<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Badge Field
 *
 * A badge field for displaying status indicators with customizable colors,
 * icons, and labels. 100% compatible with Nova's Badge field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Badge extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'BadgeField';

    /**
     * Built-in badge types and their CSS classes.
     */
    public array $builtInTypes = [
        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    ];

    /**
     * The value to badge type mapping.
     */
    public array $valueMap = [];

    /**
     * Custom badge types and their CSS classes.
     */
    public array $customTypes = [];

    /**
     * Whether to show icons in badges.
     */
    public bool $withIcons = false;

    /**
     * The icon mapping for different badge types.
     */
    public array $iconMap = [];

    /**
     * Custom label function.
     */
    public $labelCallback = null;

    /**
     * Label mapping for different values.
     */
    public array $labelMap = [];

    /**
     * Map field values to badge types.
     *
     * @param array $map Array mapping values to badge types (info, success, danger, warning)
     */
    public function map(array $map): static
    {
        $this->valueMap = $map;

        return $this;
    }

    /**
     * Replace built-in badge types with custom CSS classes.
     *
     * @param array $types Array mapping badge types to CSS classes
     */
    public function types(array $types): static
    {
        $this->customTypes = $types;

        return $this;
    }

    /**
     * Supplement built-in badge types with additional custom types.
     *
     * @param array $types Array mapping badge types to CSS classes
     */
    public function addTypes(array $types): static
    {
        $this->customTypes = array_merge($this->customTypes, $types);

        return $this;
    }

    /**
     * Enable icons in badges.
     */
    public function withIcons(): static
    {
        $this->withIcons = true;

        return $this;
    }

    /**
     * Set the icon mapping for different badge types.
     *
     * @param array $icons Array mapping badge types to icon names
     */
    public function icons(array $icons): static
    {
        $this->iconMap = $icons;

        return $this;
    }

    /**
     * Set a custom label function.
     *
     * @param callable $callback Function to transform the value into a label
     */
    public function label(callable $callback): static
    {
        $this->labelCallback = $callback;

        return $this;
    }

    /**
     * Set label mapping for different values.
     *
     * @param array $labels Array mapping values to display labels
     */
    public function labels(array $labels): static
    {
        $this->labelMap = $labels;

        return $this;
    }

    /**
     * Resolve the badge type for a given value.
     */
    public function resolveBadgeType($value): string
    {
        return $this->valueMap[$value] ?? 'info';
    }

    /**
     * Resolve the CSS classes for a given badge type.
     */
    public function resolveBadgeClasses(string $badgeType): string
    {
        // Use custom types if defined, otherwise fall back to built-in types
        if (!empty($this->customTypes)) {
            return $this->customTypes[$badgeType] ?? $this->builtInTypes[$badgeType] ?? $this->builtInTypes['info'];
        }

        return $this->builtInTypes[$badgeType] ?? $this->builtInTypes['info'];
    }

    /**
     * Resolve the icon for a given badge type.
     */
    public function resolveIcon(string $badgeType): ?string
    {
        return $this->iconMap[$badgeType] ?? null;
    }

    /**
     * Resolve the display label for a given value.
     */
    public function resolveLabel($value): string
    {
        // Use custom label callback if defined
        if ($this->labelCallback) {
            return call_user_func($this->labelCallback, $value);
        }

        // Use label mapping if defined
        if (isset($this->labelMap[$value])) {
            return $this->labelMap[$value];
        }

        // Default to the value itself
        return (string) $value;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'builtInTypes' => $this->builtInTypes,
            'valueMap' => $this->valueMap,
            'customTypes' => $this->customTypes,
            'withIcons' => $this->withIcons,
            'iconMap' => $this->iconMap,
            'labelMap' => $this->labelMap,
        ]);
    }
}
