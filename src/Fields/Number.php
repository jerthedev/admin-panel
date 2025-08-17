<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Number Field.
 *
 * A numeric input field with support for min/max validation and step controls.
 * 100% compatible with Laravel Nova Number field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Number extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'NumberField';

    /**
     * The minimum value allowed.
     */
    public ?float $min = null;

    /**
     * The maximum value allowed.
     */
    public ?float $max = null;

    /**
     * The step increment for the input.
     */
    public ?float $step = null;

    /**
     * Set the minimum value allowed.
     */
    public function min(float $min): static
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Set the maximum value allowed.
     */
    public function max(float $max): static
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Set the step increment for the input.
     */
    public function step(float $step): static
    {
        $this->step = $step;

        return $this;
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

            // Convert to appropriate numeric type
            if ($value !== null && $value !== '') {
                if ($this->step && fmod($this->step, 1) !== 0.0) {
                    // Has decimal step, use float
                    $value = (float) $value;
                } else {
                    // Integer step or no step, use integer
                    $value = (int) $value;
                }
            } else {
                // Convert empty strings to null for numeric fields
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
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
        ]);
    }
}
