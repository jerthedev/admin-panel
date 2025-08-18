<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Line Field.
 *
 * A field for displaying formatted text lines within Stack fields.
 * Provides additional formatting features and display options.
 * 100% compatible with Nova v5 Line field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Line extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'LineField';

    /**
     * Whether the line should be displayed as small text.
     */
    public bool $asSmall = false;

    /**
     * Whether the line should be displayed as heading text.
     */
    public bool $asHeading = false;

    /**
     * Whether the line should be displayed as sub text.
     */
    public bool $asSubText = false;

    /**
     * Create a new line field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Line fields are typically display-only
        $this->readonly = true;
    }

    /**
     * Display the line as small text.
     */
    public function asSmall(): static
    {
        $this->asSmall = true;
        $this->asHeading = false;
        $this->asSubText = false;

        return $this;
    }

    /**
     * Display the line as heading text.
     */
    public function asHeading(): static
    {
        $this->asHeading = true;
        $this->asSmall = false;
        $this->asSubText = false;

        return $this;
    }

    /**
     * Display the line as sub text.
     */
    public function asSubText(): static
    {
        $this->asSubText = true;
        $this->asSmall = false;
        $this->asHeading = false;

        return $this;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     * Line fields don't store data, so this is a no-op.
     */
    public function fill(Request $request, $model): void
    {
        // Line fields don't store data, so we don't fill anything
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolveForDisplay($resource, ?string $attribute = null): void
    {
        if ($this->resolveCallback) {
            $callbackValue = call_user_func($this->resolveCallback, $resource, $attribute ?? $this->attribute);
            // Only fall back to field name if callback returns null or empty string
            $this->value = ($callbackValue !== null && $callbackValue !== '') ? $callbackValue : $this->name;
        } else {
            // For line fields, we can resolve from the resource or use the field name
            $resolvedValue = data_get($resource, $attribute ?? $this->attribute);
            $this->value = ($resolvedValue !== null && $resolvedValue !== '') ? $resolvedValue : $this->name;
        }
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'asSmall' => $this->asSmall,
            'asHeading' => $this->asHeading,
            'asSubText' => $this->asSubText,
            'isLine' => true,
        ]);
    }
}
