<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Text Field
 * 
 * A basic text input field with support for suggestions and validation.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
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
    public ?int $maxLength = null;

    /**
     * Whether the field should be displayed as a password.
     */
    public bool $asPassword = false;

    /**
     * Set suggestions for the field.
     */
    public function suggestions(array $suggestions): static
    {
        $this->suggestions = $suggestions;

        return $this;
    }

    /**
     * Set the maximum length for the field.
     */
    public function maxLength(int $maxLength): static
    {
        $this->maxLength = $maxLength;

        return $this;
    }

    /**
     * Display the field as a password input.
     */
    public function asPassword(bool $asPassword = true): static
    {
        $this->asPassword = $asPassword;

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
            
            // Trim whitespace unless it's a password field
            if (! $this->asPassword && is_string($value)) {
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
            'maxLength' => $this->maxLength,
            'asPassword' => $this->asPassword,
        ]);
    }
}
