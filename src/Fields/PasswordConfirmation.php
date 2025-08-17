<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;

/**
 * PasswordConfirmation Field.
 *
 * A password confirmation input field that doesn't store data but validates
 * password confirmation. Used alongside Password fields for verification.
 *
 * This field provides an input that can be used for password confirmation
 * and is typically used alongside a Password field with the 'confirmed'
 * validation rule.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PasswordConfirmation extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'PasswordConfirmationField';

    /**
     * Create a new field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Password confirmation fields should only be shown on forms by default
        $this->onlyOnForms();
    }

    /**
     * Resolve the field's value (always return null for security).
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        // Never resolve password confirmation values for security
        $this->value = null;
    }

    /**
     * Hydrate the given attribute on the model based on the incoming request.
     */
    public function fill(Request $request, $model): void
    {
        // Password confirmation fields don't fill the model
        // They are only used for validation purposes
        // The actual validation happens through Laravel's 'confirmed' rule
        // on the main password field
    }
}
