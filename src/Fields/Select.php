<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Select Field
 *
 * A select dropdown field with support for options and Enum integration.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Select extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'SelectField';

    /**
     * The available options for the select field.
     */
    public array $options = [];

    /**
     * Whether the select should be searchable.
     */
    public bool $searchable = false;

    /**
     * Whether to display the option keys instead of values.
     */
    public bool $displayUsingLabels = true;

    /**
     * Set the available options for the select field.
     */
    public function options(array $options): static
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Set options from an Enum class.
     */
    public function enum(string $enumClass): static
    {
        if (! enum_exists($enumClass)) {
            throw new \InvalidArgumentException("Class {$enumClass} is not an enum.");
        }

        $options = [];
        foreach ($enumClass::cases() as $case) {
            $options[$case->value] = $case->name;
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Display using option keys instead of values.
     */
    public function displayUsingLabels(bool $displayUsingLabels = true): static
    {
        $this->displayUsingLabels = $displayUsingLabels;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // If we have options and should display using labels, convert the value
        if ($this->displayUsingLabels && ! empty($this->options) && $this->value !== null) {
            $this->value = [
                'value' => $this->value,
                'label' => $this->options[$this->value] ?? $this->value,
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
        } elseif ($request->exists($this->attribute)) {
            $value = $request->input($this->attribute);

            // Ensure the value is valid if we have options
            if (! empty($this->options) && $value !== null && ! array_key_exists($value, $this->options)) {
                $value = null;
            }

            $model->{$this->attribute} = $value;
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'options' => $this->options,
            'searchable' => $this->searchable,
            'displayUsingLabels' => $this->displayUsingLabels,
        ]);
    }
}
