<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * KeyValue Field.
 *
 * A field for editing flat, key-value data stored inside JSON column types.
 * 100% compatible with Nova's KeyValue field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class KeyValue extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'KeyValueField';

    /**
     * The label for the key column.
     */
    public string $keyLabel = 'Key';

    /**
     * The label for the value column.
     */
    public string $valueLabel = 'Value';

    /**
     * The text for the "add row" action button.
     */
    public string $actionText = 'Add row';

    /**
     * Set the label for the key column.
     *
     * @param string $label The label for the key column
     */
    public function keyLabel(string $label): static
    {
        $this->keyLabel = $label;

        return $this;
    }

    /**
     * Set the label for the value column.
     *
     * @param string $label The label for the value column
     */
    public function valueLabel(string $label): static
    {
        $this->valueLabel = $label;

        return $this;
    }

    /**
     * Set the text for the "add row" action button.
     *
     * @param string $text The text for the action button
     */
    public function actionText(string $text): static
    {
        $this->actionText = $text;

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
            // Get the submitted key-value pairs for this field
            $keyValuePairs = $request->input($this->attribute, []);

            // Ensure we have an array
            if (! is_array($keyValuePairs)) {
                $keyValuePairs = [];
            }

            // Convert array of key-value objects to associative array
            $result = [];
            foreach ($keyValuePairs as $pair) {
                if (is_array($pair) && isset($pair['key']) && isset($pair['value'])) {
                    $key = trim($pair['key']);
                    $value = $pair['value'];

                    // Only add non-empty keys
                    if ($key !== '') {
                        $result[$key] = $value;
                    }
                }
            }

            $model->{$this->attribute} = $result;
        }
    }

    /**
     * Resolve the field's value for display and editing.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // Ensure the value is an array
        if (! is_array($this->value)) {
            $this->value = [];
        }

        // Convert associative array to array of key-value objects for the frontend
        $keyValuePairs = [];
        foreach ($this->value as $key => $value) {
            $keyValuePairs[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        $this->value = $keyValuePairs;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'keyLabel' => $this->keyLabel,
            'valueLabel' => $this->valueLabel,
            'actionText' => $this->actionText,
        ]);
    }

    /**
     * Get the displayable value for the field.
     */
    public function getDisplayValue(): array
    {
        if (! is_array($this->value)) {
            return [];
        }

        return $this->value;
    }

    /**
     * Check if the field has any values to display.
     */
    public function hasDisplayValues(): bool
    {
        return ! empty($this->value);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     * This method provides compatibility with Nova's fillAttributeFromRequest pattern.
     */
    public function fillAttributeFromRequest(Request $request, string $requestAttribute, object $model, string $attribute): void
    {
        // Get the submitted key-value pairs for this field
        $keyValuePairs = $request->input($requestAttribute, []);

        // Ensure we have an array
        if (! is_array($keyValuePairs)) {
            $keyValuePairs = [];
        }

        // Convert array of key-value objects to associative array
        $result = [];
        foreach ($keyValuePairs as $pair) {
            if (is_array($pair) && isset($pair['key']) && isset($pair['value'])) {
                $key = trim($pair['key']);
                $value = $pair['value'];

                // Only add non-empty keys
                if ($key !== '') {
                    $result[$key] = $value;
                }
            }
        }

        $model->{$attribute} = $result;
    }
}
