<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Boolean Group Field
 *
 * A field for grouping a set of Boolean checkboxes, which are stored as JSON key-values
 * in the database column they represent. 100% compatible with Nova's BooleanGroup field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class BooleanGroup extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'BooleanGroupField';

    /**
     * The options for the boolean group.
     */
    public array $options = [];

    /**
     * Whether to hide false values from display.
     */
    public bool $hideFalseValues = false;

    /**
     * Whether to hide true values from display.
     */
    public bool $hideTrueValues = false;

    /**
     * The text to display when no values are selected.
     */
    public string $noValueText = 'No Data';

    /**
     * Set the options for the boolean group.
     *
     * @param array $options Array of key-value pairs for the checkboxes
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Hide false values from display to avoid cluttering.
     */
    public function hideFalseValues(): static
    {
        $this->hideFalseValues = true;

        return $this;
    }

    /**
     * Hide true values from display to avoid cluttering.
     */
    public function hideTrueValues(): static
    {
        $this->hideTrueValues = true;

        return $this;
    }

    /**
     * Set the text to display when no values are selected.
     *
     * @param string $text The text to display for empty state
     */
    public function noValueText(string $text): static
    {
        $this->noValueText = $text;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } else {
            // Get the submitted values for this field
            $values = $request->input($this->attribute, []);

            // Ensure we have an array
            if (!is_array($values)) {
                $values = [];
            }

            // Convert to boolean values and ensure all options are represented
            $result = [];
            foreach ($this->options as $key => $label) {
                $result[$key] = isset($values[$key]) && $values[$key];
            }

            $model->{$this->attribute} = $result;
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'options' => $this->options,
            'hideFalseValues' => $this->hideFalseValues,
            'hideTrueValues' => $this->hideTrueValues,
            'noValueText' => $this->noValueText,
        ]);
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // Ensure the value is an array
        if (!is_array($this->value)) {
            $this->value = [];
        }

        // Ensure all options are represented with boolean values
        $result = [];
        foreach ($this->options as $key => $label) {
            $result[$key] = isset($this->value[$key]) ? (bool) $this->value[$key] : false;
        }

        $this->value = $result;
    }

    /**
     * Get the displayable value for the field.
     */
    public function getDisplayValue(): array
    {
        if (!is_array($this->value)) {
            return [];
        }

        $display = [];

        foreach ($this->value as $key => $value) {
            // Skip based on hide settings
            if ($this->hideTrueValues && $value) {
                continue;
            }

            if ($this->hideFalseValues && !$value) {
                continue;
            }

            // Add to display if we have a label for this key
            if (isset($this->options[$key])) {
                $display[$key] = [
                    'label' => $this->options[$key],
                    'value' => $value,
                ];
            }
        }

        return $display;
    }

    /**
     * Check if the field has any values to display.
     */
    public function hasDisplayValues(): bool
    {
        $display = $this->getDisplayValue();
        return !empty($display);
    }

    /**
     * Get the text to display when no values are present.
     */
    public function getNoValueText(): string
    {
        return $this->noValueText;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fillAttributeFromRequest(Request $request, string $requestAttribute, object $model, string $attribute): void
    {
        // Get the submitted values for this field
        $values = $request->input($requestAttribute, []);

        // Ensure we have an array
        if (!is_array($values)) {
            $values = [];
        }

        // Convert to boolean values and ensure all options are represented
        $result = [];
        foreach ($this->options as $key => $label) {
            $result[$key] = isset($values[$key]) && $values[$key];
        }

        $model->{$attribute} = $result;
    }
}
