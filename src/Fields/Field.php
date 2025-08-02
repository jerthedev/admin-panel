<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Fields;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Base Field Class
 *
 * Abstract base class for all admin panel fields. Provides common
 * functionality for field visibility, validation, and data handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @package JTD\AdminPanel\Fields
 */
abstract class Field
{
    /**
     * The field's component.
     */
    public string $component;

    /**
     * The displayable name of the field.
     */
    public string $name;

    /**
     * The attribute / column name of the field.
     */
    public string $attribute;

    /**
     * The field's resolved value.
     */
    public mixed $value = null;

    /**
     * The callback used to resolve the field's value.
     */
    public $resolveCallback;

    /**
     * The callback used to hydrate the model attribute.
     */
    public $fillCallback;

    /**
     * The callback used to format the field for display.
     */
    public $displayCallback;

    /**
     * The validation rules for the field.
     */
    public array $rules = [];

    /**
     * The creation validation rules for the field.
     */
    public array $creationRules = [];

    /**
     * The update validation rules for the field.
     */
    public array $updateRules = [];

    /**
     * Indicates if the field should be shown on the index view.
     */
    public bool $showOnIndex = true;

    /**
     * Indicates if the field should be shown on the detail view.
     */
    public bool $showOnDetail = true;

    /**
     * Indicates if the field should be shown on the creation view.
     */
    public bool $showOnCreation = true;

    /**
     * Indicates if the field should be shown on the update view.
     */
    public bool $showOnUpdate = true;

    /**
     * Indicates if the field is sortable.
     */
    public bool $sortable = false;

    /**
     * Indicates if the field is nullable.
     */
    public bool $nullable = false;

    /**
     * The field's help text.
     */
    public ?string $helpText = null;

    /**
     * The field's placeholder text.
     */
    public ?string $placeholder = null;

    /**
     * Additional meta information for the field.
     */
    public array $meta = [];

    /**
     * The field's default value.
     */
    public mixed $default = null;

    /**
     * Whether the field is readonly.
     */
    public bool $readonly = false;

    /**
     * Whether the field is searchable.
     */
    public bool $searchable = false;

    /**
     * Create a new field instance.
     */
    public function __construct(string $name, ?string $attribute = null, ?callable $resolveCallback = null)
    {
        $this->name = $name;
        $this->attribute = $attribute ?? str_replace(' ', '_', Str::lower($name));
        $this->resolveCallback = $resolveCallback;
    }

    /**
     * Create a new field instance.
     */
    public static function make(string $name, ?string $attribute = null, ?callable $resolveCallback = null): static
    {
        return new static($name, $attribute, $resolveCallback);
    }

    /**
     * Set the validation rules for the field.
     */
    public function rules(mixed ...$rules): static
    {
        $this->rules = is_array($rules[0] ?? null) ? $rules[0] : $rules;

        return $this;
    }

    /**
     * Set the creation validation rules for the field.
     */
    public function creationRules(mixed ...$rules): static
    {
        $this->creationRules = is_array($rules[0] ?? null) ? $rules[0] : $rules;

        return $this;
    }

    /**
     * Set the update validation rules for the field.
     */
    public function updateRules(mixed ...$rules): static
    {
        $this->updateRules = is_array($rules[0] ?? null) ? $rules[0] : $rules;

        return $this;
    }

    /**
     * Specify that the field should be sortable.
     */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;

        return $this;
    }

    /**
     * Specify that the field should be nullable.
     */
    public function nullable(bool $nullable = true): static
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * Set the field's help text.
     */
    public function help(string $helpText): static
    {
        $this->helpText = $helpText;

        return $this;
    }

    /**
     * Set the field's placeholder text.
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Hide the field from the index view.
     */
    public function hideFromIndex(bool $hide = true): static
    {
        $this->showOnIndex = ! $hide;

        return $this;
    }

    /**
     * Show the field on the index view.
     */
    public function showOnIndex(bool $show = true): static
    {
        $this->showOnIndex = $show;

        return $this;
    }

    /**
     * Hide the field from the detail view.
     */
    public function hideFromDetail(bool $hide = true): static
    {
        $this->showOnDetail = ! $hide;

        return $this;
    }

    /**
     * Show the field on the detail view.
     */
    public function showOnDetail(bool $show = true): static
    {
        $this->showOnDetail = $show;

        return $this;
    }

    /**
     * Hide the field when creating.
     */
    public function hideWhenCreating(bool $hide = true): static
    {
        $this->showOnCreation = ! $hide;

        return $this;
    }

    /**
     * Show the field when creating.
     */
    public function showOnCreating(bool $show = true): static
    {
        $this->showOnCreation = $show;

        return $this;
    }

    /**
     * Hide the field when updating.
     */
    public function hideWhenUpdating(bool $hide = true): static
    {
        $this->showOnUpdate = ! $hide;

        return $this;
    }

    /**
     * Show the field when updating.
     */
    public function showOnUpdating(bool $show = true): static
    {
        $this->showOnUpdate = $show;

        return $this;
    }

    /**
     * Show the field only on the index view.
     */
    public function onlyOnIndex(): static
    {
        $this->showOnIndex = true;
        $this->showOnDetail = false;
        $this->showOnCreation = false;
        $this->showOnUpdate = false;

        return $this;
    }

    /**
     * Show the field only on the detail view.
     */
    public function onlyOnDetail(): static
    {
        $this->showOnIndex = false;
        $this->showOnDetail = true;
        $this->showOnCreation = false;
        $this->showOnUpdate = false;

        return $this;
    }

    /**
     * Show the field only on forms.
     */
    public function onlyOnForms(): static
    {
        $this->showOnIndex = false;
        $this->showOnDetail = false;
        $this->showOnCreation = true;
        $this->showOnUpdate = true;

        return $this;
    }

    /**
     * Hide the field from forms.
     */
    public function exceptOnForms(): static
    {
        $this->showOnIndex = true;
        $this->showOnDetail = true;
        $this->showOnCreation = false;
        $this->showOnUpdate = false;

        return $this;
    }

    /**
     * Resolve the field's value.
     */
    public function resolve($resource, ?string $attribute = null): void
    {
        $attribute = $attribute ?? $this->attribute;

        if ($this->resolveCallback) {
            $this->value = call_user_func($this->resolveCallback, $resource, $attribute);
        } else {
            $this->value = data_get($resource, $attribute);
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
            $model->{$this->attribute} = $request->input($this->attribute);
        }
    }

    /**
     * Set the callback used to resolve the field's value.
     */
    public function resolveUsing(callable $callback): static
    {
        $this->resolveCallback = $callback;

        return $this;
    }

    /**
     * Set the callback used to hydrate the model attribute.
     */
    public function fillUsing(callable $callback): static
    {
        $this->fillCallback = $callback;

        return $this;
    }

    /**
     * Set the callback used to format the field for display only.
     */
    public function displayUsing(callable $callback): static
    {
        $this->displayCallback = $callback;

        return $this;
    }

    /**
     * Determine if the field is authorized for the given request.
     */
    public function authorize(Request $request): bool
    {
        return true;
    }

    /**
     * Get additional meta information to merge with the field payload.
     */
    public function meta(): array
    {
        return $this->meta;
    }

    /**
     * Set additional meta information for the field.
     */
    public function withMeta(array $meta): static
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    /**
     * Set the default value for the field.
     */
    public function default(mixed $value): static
    {
        $this->default = $value;

        return $this;
    }

    /**
     * Resolve the field's value for display.
     */
    public function resolveValue($resource): mixed
    {
        $this->resolve($resource);

        $value = $this->value ?? $this->default;

        // Apply display callback if set (for display formatting only)
        if ($this->displayCallback) {
            $value = call_user_func($this->displayCallback, $value, $resource, $this->attribute);
        }

        return $value;
    }

    /**
     * Determine if the field is shown on the index view.
     */
    public function isShownOnIndex(): bool
    {
        return $this->showOnIndex;
    }

    /**
     * Determine if the field is shown on the detail view.
     */
    public function isShownOnDetail(): bool
    {
        return $this->showOnDetail;
    }

    /**
     * Determine if the field is shown on forms.
     */
    public function isShownOnForms(): bool
    {
        return $this->showOnCreation || $this->showOnUpdate;
    }

    /**
     * Set the field as readonly.
     */
    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    /**
     * Make the field searchable.
     */
    public function searchable(bool $searchable = true): static
    {
        $this->searchable = $searchable;

        return $this;
    }

    /**
     * Make the field required.
     */
    public function required(bool $required = true): static
    {
        if ($required) {
            // Add 'required' to rules if not already present
            if (!in_array('required', $this->rules)) {
                $this->rules[] = 'required';
            }
        } else {
            // Remove 'required' from rules
            $this->rules = array_filter($this->rules, fn($rule) => $rule !== 'required');
        }

        return $this;
    }

    /**
     * Prepare the field for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'component' => $this->component,
            'name' => $this->name,
            'attribute' => $this->attribute,
            'value' => $this->value,
            'sortable' => $this->sortable,
            'searchable' => $this->searchable,
            'nullable' => $this->nullable,
            'readonly' => $this->readonly,
            'helpText' => $this->helpText,
            'placeholder' => $this->placeholder,
            'default' => $this->default,
            'rules' => $this->rules,
        ], $this->meta());
    }
}
