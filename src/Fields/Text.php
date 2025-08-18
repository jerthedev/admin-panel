<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Text Field.
 *
 * A basic text input field with support for suggestions and validation.
 * 100% compatible with Laravel Nova Text Field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Text extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'TextField';

    /**
     * The field's suggestions.
     */
    public array $suggestions = [];

    /**
     * The field's maximum length.
     */
    public ?int $maxlength = null;

    /**
     * Whether to enforce maximum length client-side.
     */
    public bool $enforceMaxlength = false;

    /**
     * Whether the field content should be rendered as encoded HTML.
     */
    public bool $asEncodedHtml = false;

    /**
     * Set suggestions for the field.
     */
    public function suggestions(array $suggestions): static
    {
        $this->suggestions = $suggestions;

        return $this;
    }

    /**
     * Set the maximum length for the field (Nova API compatible).
     */
    public function maxlength(int $maxlength): static
    {
        $this->maxlength = $maxlength;

        return $this;
    }

    /**
     * Enforce maximum length client-side.
     */
    public function enforceMaxlength(bool $enforceMaxlength = true): static
    {
        $this->enforceMaxlength = $enforceMaxlength;

        return $this;
    }

    /**
     * Render the field content as encoded HTML.
     */
    public function asEncodedHtml(bool $asEncodedHtml = true): static
    {
        $this->asEncodedHtml = $asEncodedHtml;

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

            // Trim whitespace for text fields
            if (is_string($value)) {
                $value = trim($value);
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
            'suggestions' => $this->suggestions,
            'maxlength' => $this->maxlength,
            'enforceMaxlength' => $this->enforceMaxlength,
            'asEncodedHtml' => $this->asEncodedHtml,
        ]);
    }
}
