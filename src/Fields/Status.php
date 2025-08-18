<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * Status Field.
 *
 * A status field for displaying progress states with customizable loading,
 * failed, and success states. 100% compatible with Nova's Status field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Status extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'StatusField';

    /**
     * Values that indicate a loading state.
     */
    public array $loadingWhen = [];

    /**
     * Values that indicate a failed state.
     */
    public array $failedWhen = [];

    /**
     * Values that indicate a success state.
     */
    public array $successWhen = [];

    /**
     * Built-in status types and their CSS classes.
     */
    public array $builtInTypes = [
        'loading' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'default' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
    ];

    /**
     * Built-in status icons.
     */
    public array $builtInIcons = [
        'loading' => 'spinner',
        'failed' => 'exclamation-circle',
        'success' => 'check-circle',
        'default' => 'information-circle',
    ];

    /**
     * Custom status types and their CSS classes.
     */
    public array $customTypes = [];

    /**
     * Custom icon mapping for different status types.
     */
    public array $customIcons = [];

    /**
     * Whether to show icons in status indicators.
     */
    public bool $withIcons = true;

    /**
     * Custom label function.
     */
    public $labelCallback = null;

    /**
     * Label mapping for different values.
     */
    public array $labelMap = [];

    /**
     * Set values that indicate a loading state.
     *
     * @param array $values Array of values that indicate loading
     */
    public function loadingWhen(array $values): static
    {
        $this->loadingWhen = $values;

        return $this;
    }

    /**
     * Set values that indicate a failed state.
     *
     * @param array $values Array of values that indicate failure
     */
    public function failedWhen(array $values): static
    {
        $this->failedWhen = $values;

        return $this;
    }

    /**
     * Set values that indicate a success state.
     *
     * @param array $values Array of values that indicate success
     */
    public function successWhen(array $values): static
    {
        $this->successWhen = $values;

        return $this;
    }

    /**
     * Replace built-in status types with custom CSS classes.
     *
     * @param array $types Array mapping status types to CSS classes
     */
    public function types(array $types): static
    {
        $this->customTypes = $types;

        return $this;
    }

    /**
     * Set custom icons for different status types.
     *
     * @param array $icons Array mapping status types to icon names
     */
    public function icons(array $icons): static
    {
        $this->customIcons = $icons;

        return $this;
    }

    /**
     * Enable or disable icons in status indicators.
     */
    public function withIcons(bool $withIcons = true): static
    {
        $this->withIcons = $withIcons;

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
     * Resolve the status type for a given value.
     */
    public function resolveStatusType($value): string
    {
        if (in_array($value, $this->loadingWhen)) {
            return 'loading';
        }

        if (in_array($value, $this->failedWhen)) {
            return 'failed';
        }

        if (in_array($value, $this->successWhen)) {
            return 'success';
        }

        return 'default';
    }

    /**
     * Resolve the CSS classes for a given status type.
     */
    public function resolveStatusClasses(string $statusType): string
    {
        // Use custom types if defined, otherwise fall back to built-in types
        if (! empty($this->customTypes)) {
            return $this->customTypes[$statusType] ?? $this->builtInTypes[$statusType] ?? $this->builtInTypes['default'];
        }

        return $this->builtInTypes[$statusType] ?? $this->builtInTypes['default'];
    }

    /**
     * Resolve the icon for a given status type.
     */
    public function resolveIcon(string $statusType): ?string
    {
        if (! $this->withIcons) {
            return null;
        }

        // Use custom icons if defined, otherwise fall back to built-in icons
        if (! empty($this->customIcons)) {
            return $this->customIcons[$statusType] ?? $this->builtInIcons[$statusType] ?? $this->builtInIcons['default'];
        }

        return $this->builtInIcons[$statusType] ?? $this->builtInIcons['default'];
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

        // Default to the value itself, formatted nicely
        return ucfirst(str_replace(['_', '-'], ' ', (string) $value));
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // Prepare the status information for the frontend
        if ($this->value !== null) {
            $statusType = $this->resolveStatusType($this->value);
            $this->value = [
                'value' => $this->value,
                'label' => $this->resolveLabel($this->value),
                'type' => $statusType,
                'classes' => $this->resolveStatusClasses($statusType),
                'icon' => $this->resolveIcon($statusType),
            ];
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'loadingWhen' => $this->loadingWhen,
            'failedWhen' => $this->failedWhen,
            'successWhen' => $this->successWhen,
            'builtInTypes' => $this->builtInTypes,
            'builtInIcons' => $this->builtInIcons,
            'customTypes' => $this->customTypes,
            'customIcons' => $this->customIcons,
            'withIcons' => $this->withIcons,
            'labelMap' => $this->labelMap,
        ]);
    }
}
