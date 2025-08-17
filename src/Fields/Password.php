<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Password Field.
 *
 * A password input field with automatic hashing. Compatible with Nova's Password field API.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class Password extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'PasswordField';

    /**
     * Create a new field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Password fields should not be shown on index or detail views by default
        $this->onlyOnForms();
    }

    /**
     * Resolve the field's value (always return null for security).
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        // Never resolve password values for security
        $this->value = null;
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

            // Only hash and set if a value is provided
            if (! empty($value)) {
                $model->{$this->attribute} = Hash::make($value);
            }
        }
    }
}
