<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Password Field
 * 
 * A password input field with automatic hashing and confirmation support.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
class Password extends Field
{
    /**
     * The field's component.
     */
    public string $component = 'PasswordField';

    /**
     * Whether to require password confirmation.
     */
    public bool $requireConfirmation = false;

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

        // Password fields should not be shown on index or detail views
        $this->onlyOnForms();
    }

    /**
     * Require password confirmation.
     */
    public function confirmation(bool $requireConfirmation = true): static
    {
        $this->requireConfirmation = $requireConfirmation;

        if ($requireConfirmation) {
            $this->rules('confirmed');
        }

        return $this;
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

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return array_merge(parent::meta(), [
            'requireConfirmation' => $this->requireConfirmation,
            'minLength' => $this->minLength,
            'showStrengthIndicator' => $this->showStrengthIndicator,
        ]);
    }
}
