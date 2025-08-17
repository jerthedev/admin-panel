<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Boolean Field
 *
 * A boolean field for representing boolean / "tiny integer" columns.
 * 100% compatible with Nova's Boolean field API.
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
     * The value to store when the field is true.
     */
    public mixed $trueValue = true;

    /**
     * The value to store when the field is false.
     */
    public mixed $falseValue = false;

    /**
     * Set the value to store when the field is true.
     *
     * @param mixed $value The value to store for true state
     */
    public function trueValue(mixed $value): static
    {
        $this->trueValue = $value;

        return $this;
    }

    /**
     * Set the value to store when the field is false.
     *
     * @param mixed $value The value to store for false state
     */
    public function falseValue(mixed $value): static
    {
        $this->falseValue = $value;

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
        ]);
    }
}
