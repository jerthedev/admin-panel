<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * DateTime Field
 * 
 * A datetime input field with formatting, timezone, and time interval support.
 * Extends the Date field to add time-specific functionality.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class DateTime extends Date
{
    /**
     * The field's component.
     */
    public string $component = 'DateTimeField';

    /**
     * The format to use when displaying the datetime.
     */
    public string $displayFormat = 'Y-m-d H:i:s';

    /**
     * The format to use when storing the datetime.
     */
    public string $storageFormat = 'Y-m-d H:i:s';

    /**
     * The timezone for the datetime field.
     */
    public string $timezone = 'UTC';

    /**
     * The step interval in minutes for time input.
     */
    public int $step = 1;

    /**
     * The minimum datetime allowed.
     */
    public ?string $minDateTime = null;

    /**
     * The maximum datetime allowed.
     */
    public ?string $maxDateTime = null;

    /**
     * Set the format for displaying and storing the datetime.
     */
    public function format(string $format): static
    {
        $this->displayFormat = $format;
        $this->storageFormat = $format;

        return $this;
    }

    /**
     * Set the format for displaying the datetime.
     */
    public function displayFormat(string $format): static
    {
        $this->displayFormat = $format;

        return $this;
    }

    /**
     * Set the timezone for the datetime field.
     */
    public function timezone(string $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    /**
     * Set the step interval in minutes for time input.
     */
    public function step(int $minutes): static
    {
        $this->step = $minutes;

        return $this;
    }

    /**
     * Set the minimum datetime allowed.
     */
    public function min(string $datetime): static
    {
        $this->minDateTime = $datetime;
        // Also set the parent minDate for compatibility
        $this->minDate = $datetime;

        return $this;
    }

    /**
     * Set the maximum datetime allowed.
     */
    public function max(string $datetime): static
    {
        $this->maxDateTime = $datetime;
        // Also set the parent maxDate for compatibility
        $this->maxDate = $datetime;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        // Call the base Field resolve method (skip Date's resolve)
        Field::resolve($resource, $attribute);

        // Format the datetime for display
        if ($this->value && ! is_string($this->value)) {
            try {
                $carbon = $this->value instanceof Carbon ? $this->value : Carbon::parse($this->value);
                
                // Set timezone if specified
                if ($this->timezone !== 'UTC') {
                    $carbon = $carbon->setTimezone($this->timezone);
                }
                
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
                    // Parse the datetime and format it for storage
                    $carbon = Carbon::createFromFormat($this->displayFormat, $value, $this->timezone);
                    
                    // Convert to UTC for storage if timezone is specified
                    if ($this->timezone !== 'UTC') {
                        $carbon = $carbon->utc();
                    }
                    
                    $model->{$this->attribute} = $carbon->format($this->storageFormat);
                } catch (\Exception $e) {
                    // If parsing fails, try a more flexible approach
                    try {
                        $carbon = Carbon::parse($value, $this->timezone);
                        
                        if ($this->timezone !== 'UTC') {
                            $carbon = $carbon->utc();
                        }
                        
                        $model->{$this->attribute} = $carbon->format($this->storageFormat);
                    } catch (\Exception $e2) {
                        // If all parsing fails, store as-is
                        $model->{$this->attribute} = $value;
                    }
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
            'timezone' => $this->timezone,
            'step' => $this->step,
            'minDateTime' => $this->minDateTime,
            'maxDateTime' => $this->maxDateTime,
        ]);
    }
}
