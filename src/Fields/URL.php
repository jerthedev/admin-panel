<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

/**
 * URL Field.
 *
 * The URL field renders URLs as clickable links instead of plain text.
 * Compatible with Laravel Nova's URL field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class URL extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'URLField';

    /**
     * Create a new URL field instance.
     *
     * @param string $name The field name
     * @param string|callable|null $attribute The attribute name or resolver callback
     * @param callable|null $resolveCallback The resolve callback
     */
    public static function make(string $name, $attribute = null, ?callable $resolveCallback = null): static
    {
        // Handle Nova-style computed values where closure is passed as second parameter
        if (is_callable($attribute)) {
            $field = new static($name);
            $field->resolveCallback = $attribute;

            return $field;
        }

        return parent::make($name, $attribute, $resolveCallback);
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(\Illuminate\Http\Request $request, $model): void
    {
        if ($this->fillCallback) {
            call_user_func($this->fillCallback, $request, $model, $this->attribute);
        } elseif ($request->exists($this->attribute)) {
            $value = $request->input($this->attribute);

            // Convert empty strings to null for URL fields
            if ($value === '') {
                $value = null;
            }

            $model->{$this->attribute} = $value;
        }
    }
}
