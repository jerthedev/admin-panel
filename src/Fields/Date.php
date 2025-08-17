<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Date Field.
 *
 * A date input field with formatting and timezone support.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Date extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'DateField';

    /**
     * The format to use when displaying the date.
     */
    public string $displayFormat = 'Y-m-d';

    /**
     * The format to use when storing the date.
     */
    public string $storageFormat = 'Y-m-d';

    /**
     * The minimum date allowed.
     */
    public ?string $minDate = null;

    /**
     * The maximum date allowed.
     */
    public ?string $maxDate = null;

    /**
     * Whether to show a date picker.
     */
    public bool $showPicker = true;

    /**
     * The format to use for the date picker.
     */
    public ?string $pickerFormat = null;

    /**
     * The display format for the date picker.
     */
    public ?string $pickerDisplayFormat = null;

    /**
     * The first day of the week (0 = Sunday, 1 = Monday, etc.).
     */
    public int $firstDayOfWeek = 0;

    /**
     * Set the format for displaying the date.
     */
    public function format(string $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    /**
     * Set the format for storing the date.
     */
    public function storageFormat(string $format): static
    {
        $this->storageFormat = $format;

        return $this;
    }

    /**
     * Set the minimum date allowed.
     */
    public function min(string $date): static
    {
        $this->minDate = $date;

        return $this;
    }

    /**
     * Set the maximum date allowed.
     */
    public function max(string $date): static
    {
        $this->maxDate = $date;

        return $this;
    }

    /**
     * Show or hide the date picker.
     */
    public function showPicker(bool $show = true): static
    {
        $this->showPicker = $show;

        return $this;
    }

    /**
     * Set the format for the date picker.
     */
    public function pickerFormat(string $format): static
    {
        $this->pickerFormat = $format;

        return $this;
    }

    /**
     * Set the display format for the date picker.
     */
    public function pickerDisplayFormat(string $format): static
    {
        $this->pickerDisplayFormat = $format;

        return $this;
    }

    /**
     * Set the first day of the week for the date picker.
     */
    public function firstDayOfWeek(int $day): static
    {
        $this->firstDayOfWeek = $day;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        parent::resolve($resource, $attribute);

        // Format the date for display
        if ($this->value && ! is_string($this->value)) {
            try {
                $carbon = $this->value instanceof Carbon ? $this->value : Carbon::parse($this->value);
                $this->value = $carbon->format($this->displayFormat);
            } catch (\Exception $e) {
                // If parsing fails, keep the original value
            }
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

            if ($value) {
                try {
                    // Parse the date and format it for storage
                    $carbon = Carbon::createFromFormat($this->displayFormat, $value);
                    $model->{$this->attribute} = $carbon->format($this->storageFormat);
                } catch (\Exception $e) {
                    // If parsing fails, store as-is
                    $model->{$this->attribute} = $value;
                }
            } else {
                $model->{$this->attribute} = null;
            }
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'displayFormat' => $this->displayFormat,
            'storageFormat' => $this->storageFormat,
            'minDate' => $this->minDate,
            'maxDate' => $this->maxDate,
            'showPicker' => $this->showPicker,
            'pickerFormat' => $this->pickerFormat,
            'pickerDisplayFormat' => $this->pickerDisplayFormat,
            'firstDayOfWeek' => $this->firstDayOfWeek,
        ]);
    }
}
