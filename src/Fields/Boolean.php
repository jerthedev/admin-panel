<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Boolean Field
 *
 * A boolean toggle/checkbox field with customizable true/false values.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Boolean extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'BooleanField';

    /**
     * The display mode (checkbox, switch, button).
     */
    public string $displayMode = 'checkbox';

    /**
     * The color theme for the field.
     */
    public string $color = 'primary';

    /**
     * The size of the field.
     */
    public string $size = 'medium';

    /**
     * The value to store when the field is true.
     */
    public mixed $trueValue = true;

    /**
     * The value to store when the field is false.
     */
    public mixed $falseValue = false;

    /**
     * The text to display for the true value.
     */
    public string $trueText = 'Yes';

    /**
     * The text to display for the false value.
     */
    public string $falseText = 'No';

    /**
     * Whether to display as a toggle switch instead of checkbox.
     */
    public bool $asToggle = true;

    /**
     * Set the values to store for true/false states.
     */
    public function values(mixed $trueValue, mixed $falseValue): static
    {
        $this->trueValue = $trueValue;
        $this->falseValue = $falseValue;

        return $this;
    }

    /**
     * Set the value to store when the field is true.
     */
    public function trueValue(mixed $value): static
    {
        $this->trueValue = $value;

        return $this;
    }

    /**
     * Set the value to store when the field is false.
     */
    public function falseValue(mixed $value): static
    {
        $this->falseValue = $value;

        return $this;
    }

    /**
     * Set the text to display for true/false states.
     */
    public function labels(string $trueText, string $falseText): static
    {
        $this->trueText = $trueText;
        $this->falseText = $falseText;

        return $this;
    }

    /**
     * Display as a toggle switch.
     */
    public function asToggle(bool $asToggle = true): static
    {
        $this->asToggle = $asToggle;

        return $this;
    }

    /**
     * Display as a checkbox.
     */
    public function asCheckbox(): static
    {
        $this->asToggle = false;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // Convert the stored value to a boolean for the frontend
        if ($this->value !== null) {
            $this->value = [
                'value' => $this->value == $this->trueValue,
                'display' => $this->value == $this->trueValue ? $this->trueText : $this->falseText,
            ];
        }
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } else {
            // Boolean fields are always present in the request (as false if unchecked)
            $value = $request->boolean($this->attribute);
            $model->{$this->attribute} = $value ? $this->trueValue : $this->falseValue;
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'trueValue' => $this->trueValue,
            'falseValue' => $this->falseValue,
            'trueText' => $this->trueText,
            'falseText' => $this->falseText,
            'asToggle' => $this->asToggle,
            'displayMode' => $this->displayMode,
            'color' => $this->color,
            'size' => $this->size,
        ]);
    }

    /**
     * Display as a switch.
     */
    public function displayAsSwitch(): static
    {
        $this->displayMode = 'switch';
        $this->asToggle = true;

        return $this;
    }

    /**
     * Display as a button.
     */
    public function displayAsButton(): static
    {
        $this->displayMode = 'button';
        $this->asToggle = false;

        return $this;
    }

    /**
     * Display as a checkbox.
     */
    public function displayAsCheckbox(): static
    {
        $this->displayMode = 'checkbox';
        $this->asToggle = false;

        return $this;
    }

    /**
     * Set the color theme.
     */
    public function color(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Set the size.
     */
    public function size(string $size): static
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Resolve the display value for a boolean.
     */
    public function resolveDisplayValue($value): string
    {
        if ($value) {
            return $this->trueText;
        }

        return $this->falseText;
    }
}
