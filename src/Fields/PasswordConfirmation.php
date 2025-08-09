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
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class PasswordConfirmation extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'PasswordConfirmationField';

    /**
     * The minimum password length.
     */
    public ?int $minLength = null;

    /**
     * Whether to show password strength indicator.
     */
    public bool $showStrengthIndicator = false;

    /**
     * Create a new field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Password confirmation fields should only be shown on forms
        $this->onlyOnForms();
    }

    /**
     * Set the minimum password length.
     */
    public function minLength(int $minLength): static
    {
        $this->minLength = $minLength;
        $this->rules("min:{$minLength}");

        return $this;
    }

    /**
     * Show password strength indicator.
     */
    public function showStrengthIndicator(bool $show = true): static
    {
        $this->showStrengthIndicator = $show;

        return $this;
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

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'minLength' => $this->minLength,
            'showStrengthIndicator' => $this->showStrengthIndicator,
        ]);
    }
}
