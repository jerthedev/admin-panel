<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * MultiSelect Field.
 *
 * A multi-select dropdown field with support for multiple selections,
 * tagging interface, and searchable options.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class MultiSelect extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'MultiSelectField';

    /**
     * The available options for the multi-select field.
     */
    public array $options = [];

    /**
     * Whether the multi-select should be searchable.
     */
    public bool $searchable = false;

    /**
     * Whether the multi-select should allow creating new tags.
     */
    public bool $taggable = false;

    /**
     * The maximum number of selections allowed.
     */
    public ?int $maxSelections = null;

    /**
     * Set the available options for the multi-select field.
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
     * Make the multi-select field searchable.
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Allow creating new tags in the multi-select field.
     */
    public function taggable(bool $taggable = true): static
    {
        $this->taggable = $taggable;

        return $this;
    }

    /**
     * Set the maximum number of selections allowed.
     */
    public function maxSelections(int $maxSelections): static
    {
        $this->maxSelections = $maxSelections;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // Ensure the value is always an array
        if ($this->value !== null) {
            // If it's a JSON string, decode it
            if (is_string($this->value)) {
                $decoded = json_decode($this->value, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->value = $decoded;
                } else {
                    // If it's a comma-separated string, split it
                    $this->value = array_filter(array_map('trim', explode(',', $this->value)));
                }
            }

            // Ensure it's an array
            if (! is_array($this->value)) {
                $this->value = [$this->value];
            }
        } else {
            $this->value = [];
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

            // Ensure the value is an array
            if (! is_array($value)) {
                $value = $value ? [$value] : [];
            }

            // Validate selections against options if we have them
            if (! empty($this->options) && ! $this->taggable) {
                $value = array_filter($value, function ($item) {
                    return array_key_exists($item, $this->options);
                });
            }

            // Enforce max selections limit
            if ($this->maxSelections !== null && count($value) > $this->maxSelections) {
                $value = array_slice($value, 0, $this->maxSelections);
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
            'taggable' => $this->taggable,
            'maxSelections' => $this->maxSelections,
        ]);
    }
}
