<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * Email Field
 * 
 * An email input field with built-in validation and formatting.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Email extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'EmailField';

    /**
     * Whether to show a clickable mailto link on detail view.
     */
    public bool $clickable = true;

    /**
     * Create a new field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Add default email validation
        $this->rules('email');
    }

    /**
     * Make the email clickable or not.
     */
    public function clickable(bool $clickable = true): static
    {
        $this->clickable = $clickable;

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
            
            // Normalize email: trim and convert to lowercase
            if (is_string($value)) {
                $value = trim(strtolower($value));
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
            'clickable' => $this->clickable,
        ]);
    }
}
