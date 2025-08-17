<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Hidden Field.
 *
 * A hidden input field for storing values that should not be visible
 * to users but need to be included in forms (IDs, tokens, CSRF, etc.).
 *
 * Compatible with Nova's Hidden field API including callable defaults.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Hidden extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'HiddenField';

    /**
     * Create a new hidden field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Hidden fields should not be shown on index or detail by default
        $this->showOnIndex = false;
        $this->showOnDetail = false;
        // But should be included in forms
        $this->showOnCreation = true;
        $this->showOnUpdate = true;
    }

    /**
     * Resolve the field's value for display.
     *
     * Handles callable defaults as per Nova API specification.
     */
    public function resolveValue($resource): mixed
    {
        $this->resolve($resource);

        $value = $this->value;

        // If no value, use default (which may be callable)
        if ($value === null) {
            $value = $this->resolveDefaultValue();
        }

        // Apply display callback if set (for display formatting only)
        if ($this->displayCallback) {
            $value = call_user_func($this->displayCallback, $value, $resource, $this->attribute);
        }

        return $value;
    }

    /**
     * Resolve the default value, handling callable defaults.
     */
    protected function resolveDefaultValue(): mixed
    {
        if (is_callable($this->default)) {
            // Nova-style callable defaults receive the request as parameter
            $request = app(Request::class);

            return call_user_func($this->default, $request);
        }

        return $this->default;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     *
     * Handles callable defaults during form submission.
     */
    public function fill(Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } elseif ($request->exists($this->attribute)) {
            $model->{$this->attribute} = $request->input($this->attribute);
        } else {
            // If no value in request, use default value (may be callable)
            $defaultValue = $this->resolveDefaultValue();
            if ($defaultValue !== null) {
                $model->{$this->attribute} = $defaultValue;
            }
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'showOnIndex' => $this->showOnIndex,
            'showOnDetail' => $this->showOnDetail,
            'showOnCreation' => $this->showOnCreation,
            'showOnUpdate' => $this->showOnUpdate,
        ]);
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * Resolves callable defaults for client-side use.
     */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        // Resolve callable defaults for client serialization
        if (is_callable($this->default)) {
            $data['default'] = $this->resolveDefaultValue();
        }

        return $data;
    }
}
