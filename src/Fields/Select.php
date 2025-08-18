<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Select Field.
 *
 * A select dropdown field with support for options and Enum integration.
 *
 * API aligned with Laravel Nova v5 Select field.
 * - options(array|string|callable): accepts key=>label array, backed enum class string, or a callable returning an array
 * - searchable(): enable searchable select UI
 * - displayUsingLabels(): control display of labels on index/detail (does not alter the stored value)
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
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
     * Whether to display the option labels instead of values on index/detail.
     */
    public bool $displayUsingLabels = false;

    /**
     * Set the available options for the select field.
     *
     * Accepts:
     * - array of [value => label]
     * - string enum class (backed enum) => will map [case->value => case->name]
     * - callable returning array [value => label]
     */
    public function options(array|string|callable $options): static
    {
        if (is_string($options)) {
            // Enum class
            if (! enum_exists($options)) {
                throw new \InvalidArgumentException("Class {$options} is not an enum.");
            }

            $mapped = [];
            foreach ($options::cases() as $case) {
                $mapped[$case->value] = $case->name;
            }
            $this->options = $mapped;

            return $this;
        }

        if (is_callable($options)) {
            $evaluated = $options();
            if (! is_array($evaluated)) {
                throw new \InvalidArgumentException('Select::options(callable) must return an array of [value => label].');
            }
            $this->options = $evaluated;

            return $this;
        }

        // Array of [value => label]
        $this->options = $options;

        return $this;
    }

    /**
     * Make the select field searchable.
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Display using option labels instead of values on index/detail.
     */
    public function displayUsingLabels(bool $displayUsingLabels = true): static
    {
        $this->displayUsingLabels = $displayUsingLabels;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     *
     * Note: Do not mutate value to label here; display-only mapping occurs in the UI
     * when displayUsingLabels() is enabled.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);
        // Keep raw value; UI will decide how to display based on displayUsingLabels
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } elseif ($request->exists($this->attribute)) {
            $model->{$this->attribute} = $request->input($this->attribute);
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
